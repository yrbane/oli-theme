<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Publisher;

use OliTheme\MetaSync\Http\GraphApiClient;
use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\TokenStore;

/**
 * Publisher Facebook (Page) via Graph API v19+.
 *
 * Endpoints :
 *  - create  : POST /{page-id}/feed     {message, link}
 *  - edit    : POST /{post-id}          {message}  (FB autorise PATCH-via-POST)
 *  - delete  : DELETE /{post-id}
 *
 * @package OliTheme\MetaSync\Publisher
 *
 * @since 1.3.0
 */
final class FacebookPublisher implements PublisherInterface
{
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
        $result = $this->client->post('/' . $creds->pageId . '/feed', [
            'message' => $payload->facebookMessage(),
            'link'    => $payload->permalink,
        ], $creds->accessToken);
        if ($result instanceof GraphApiError) {
            return $result;
        }
        $id = (string) ($result['id'] ?? '');
        if ($id === '') {
            return new GraphApiError(500, 'no_id', 'Facebook /feed did not return an id.');
        }
        return $id;
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
        $result = $this->client->post('/' . $externalId, [
            'message' => $payload->facebookMessage(),
        ], $creds->accessToken);
        if ($result instanceof GraphApiError) {
            return $result;
        }
        // Facebook renvoie `success: true` ou `id` selon les versions ; on
        // retourne l'externalId existant car FB n'en crée pas de nouveau.
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
            // 404 = déjà supprimé côté FB → on considère que c'est OK pour la
            // réconciliation locale.
            if ($result->httpStatus === 404 || $result->graphCode === 803) {
                return true;
            }
            return $result;
        }
        return (bool) ($result['success'] ?? true);
    }
}
