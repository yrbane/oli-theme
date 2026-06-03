<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Publisher;

use OliTheme\MetaSync\Http\GraphApiClient;
use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\TokenStore;

/**
 * Publisher dédié aux events Facebook (CPT `oli_event`).
 *
 * L'API Events de Facebook a été **restreinte par Meta** depuis 2018+ :
 * l'endpoint `/{page-id}/events` (POST) renvoie souvent une erreur de
 * permission (`#200`) sauf pour les comptes spécifiquement approuvés.
 *
 * Stratégie en 2 temps :
 *  1. Tente la création native via `/{page-id}/events`.
 *  2. Si échec en permission (graph code 200/100) ou si non disponible,
 *     fallback vers une publication standard `/feed` avec un message
 *     enrichi (date + lieu + lien). On préfixe par 🗓 pour la visibilité.
 *
 * Renvoie toujours un ID externe utilisable (event-id natif ou post-id
 * fallback). En base postmeta, l'origine est tracée dans _oli_meta_fb_event_origin.
 *
 * @package OliTheme\MetaSync\Publisher
 *
 * @since 1.3.0
 */
final class FacebookEventPublisher implements PublisherInterface
{
    public const ORIGIN_NATIVE   = 'native';
    public const ORIGIN_FALLBACK = 'fallback';

    public function __construct(
        private readonly GraphApiClient $client,
        private readonly TokenStore $tokens,
    ) {
    }

    public function create(PublishPayload $payload): string|GraphApiError
    {
        $creds = $this->tokens->load();
        if (!$creds->isConnected()) {
            return new GraphApiError(401, 'not_connected', 'Meta credentials not configured.');
        }
        $startTimeIso = $this->extractEventStart($payload);
        if ($startTimeIso === null) {
            // Pas de date détectée → on retombe direct sur le post standard.
            return $this->fallback($payload, $creds->pageId, $creds->accessToken, 'no_event_date');
        }

        // Étape 1 : tente la création native.
        $result = $this->client->post('/' . $creds->pageId . '/events', [
            'name'        => $payload->title,
            'description' => $payload->contentText,
            'start_time'  => $startTimeIso,
        ], $creds->accessToken);

        if ($result instanceof GraphApiError) {
            if ($result->isPermissionError() || $result->httpStatus === 400) {
                return $this->fallback($payload, $creds->pageId, $creds->accessToken, 'api_restricted');
            }
            return $result;
        }
        $id = (string) ($result['id'] ?? '');
        return $id !== '' ? $id : $this->fallback($payload, $creds->pageId, $creds->accessToken, 'no_id');
    }

    public function edit(string $externalId, PublishPayload $payload): string|GraphApiError
    {
        if ($externalId === '') {
            return new GraphApiError(400, 'invalid_id', 'External id is required.');
        }
        $creds = $this->tokens->load();
        if (!$creds->isConnected()) {
            return new GraphApiError(401, 'not_connected', 'Meta credentials not configured.');
        }
        // FB events API edit + feed post edit utilisent tous deux POST /{id}.
        $result = $this->client->post('/' . $externalId, [
            'message' => $this->fallbackMessage($payload),
        ], $creds->accessToken);
        if ($result instanceof GraphApiError) {
            return $result;
        }
        return $externalId;
    }

    public function delete(string $externalId): bool|GraphApiError
    {
        if ($externalId === '') {
            return new GraphApiError(400, 'invalid_id', 'External id is required.');
        }
        $creds = $this->tokens->load();
        if (!$creds->isConnected()) {
            return new GraphApiError(401, 'not_connected', 'Meta credentials not configured.');
        }
        $result = $this->client->delete('/' . $externalId, $creds->accessToken);
        if ($result instanceof GraphApiError) {
            if ($result->httpStatus === 404 || $result->graphCode === 803) {
                return true;
            }
            return $result;
        }
        return (bool) ($result['success'] ?? true);
    }

    /**
     * Détecte la date de début de l'event via la meta `_oli_event_start` si elle
     * existe côté WP (CPT `oli_event` du thème). Sinon retourne null.
     */
    private function extractEventStart(PublishPayload $payload): ?string
    {
        if (!\function_exists('get_post_meta')) {
            return null;
        }
        $raw = (string) get_post_meta($payload->postId, '_oli_event_start', true);
        if ($raw === '') {
            return null;
        }
        $ts = strtotime($raw);
        return $ts !== false ? gmdate('Y-m-d\TH:i:sO', $ts) : null;
    }

    private function fallback(PublishPayload $payload, string $pageId, string $accessToken, string $reason): string|GraphApiError
    {
        $message = $this->fallbackMessage($payload);
        $result = $this->client->post('/' . $pageId . '/feed', [
            'message' => $message,
            'link'    => $payload->permalink,
        ], $accessToken);
        if ($result instanceof GraphApiError) {
            return $result;
        }
        return (string) ($result['id'] ?? '');
    }

    private function fallbackMessage(PublishPayload $payload): string
    {
        $prefix = '🗓 ' . $payload->title;
        $location = '';
        if (\function_exists('get_post_meta')) {
            $location = (string) get_post_meta($payload->postId, '_oli_event_location', true);
        }
        $dateRaw = \function_exists('get_post_meta')
            ? (string) get_post_meta($payload->postId, '_oli_event_start', true)
            : '';

        $parts   = [$prefix];
        if ($dateRaw !== '') {
            $ts = strtotime($dateRaw);
            if ($ts !== false) {
                $parts[] = '📅 ' . date('d/m/Y H:i', $ts);
            }
        }
        if ($location !== '') {
            $parts[] = '📍 ' . $location;
        }
        $parts[] = '';
        $parts[] = $payload->excerpt !== '' ? $payload->excerpt : $payload->contentText;
        $parts[] = '';
        $parts[] = $payload->permalink;

        return implode("\n", array_filter($parts, static fn (string $p): bool => $p !== '' || $p === ''));
    }
}
