<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Publisher;

use OliTheme\MetaSync\Http\GraphApiClient;
use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\TokenStore;

/**
 * Publisher Instagram (compte Business lié à la Page Facebook).
 *
 * Workflow 2 étapes Graph API :
 *  1. POST /{ig-user-id}/media        avec `image_url` + `caption` → creation_id
 *  2. POST /{ig-user-id}/media_publish avec `creation_id`           → media id
 *
 * Édition :
 *  - Strategy `skip`           → no-op, retourne l'externalId existant.
 *  - Strategy `delete_recreate`→ delete puis create avec nouveau payload.
 *
 * @package OliTheme\MetaSync\Publisher
 *
 * @since 1.3.0
 */
final class InstagramPublisher implements PublisherInterface
{
    public function __construct(
        private readonly GraphApiClient $client,
        private readonly TokenStore $tokens,
        private readonly InstagramEditStrategy $editStrategy = InstagramEditStrategy::Skip,
    ) {
    }

    public function create(PublishPayload $payload): string|GraphApiError
    {
        if ($payload->featuredImageUrl === '') {
            return new GraphApiError(400, 'no_image', 'Instagram exige une image (featured image absente).');
        }
        $creds = $this->tokens->load();
        if (!$creds->isConnected() || $creds->igUserId === '') {
            return new GraphApiError(401, 'not_connected', 'Compte Instagram non lié.');
        }

        // Étape 1 : crée le container média.
        $container = $this->client->post('/' . $creds->igUserId . '/media', [
            'image_url' => $payload->featuredImageUrl,
            'caption'   => $payload->instagramCaption(),
        ], $creds->accessToken);
        if ($container instanceof GraphApiError) {
            return $container;
        }
        $containerId = (string) ($container['id'] ?? '');
        if ($containerId === '') {
            return new GraphApiError(500, 'no_creation_id', 'Instagram /media did not return an id.');
        }

        // Étape 2 : publie le container.
        $published = $this->client->post('/' . $creds->igUserId . '/media_publish', [
            'creation_id' => $containerId,
        ], $creds->accessToken);
        if ($published instanceof GraphApiError) {
            return $published;
        }
        $mediaId = (string) ($published['id'] ?? '');
        if ($mediaId === '') {
            return new GraphApiError(500, 'no_media_id', 'Instagram /media_publish did not return an id.');
        }
        return $mediaId;
    }

    public function edit(string $externalId, PublishPayload $payload): string|GraphApiError
    {
        if ($externalId === '') {
            return new GraphApiError(400, 'invalid_id', 'External id is required.');
        }
        if ($this->editStrategy === InstagramEditStrategy::Skip) {
            return $externalId;
        }
        // DeleteRecreate : on supprime puis on recrée. La nouvelle URL IG
        // remplace l'ancienne (les likes/commentaires sont perdus — accepté
        // par Olivier dans les réglages).
        $deleted = $this->delete($externalId);
        if ($deleted instanceof GraphApiError && !$deleted->isAuthError()) {
            // Si delete échoue pour une autre raison qu'auth, on remonte
            // l'erreur pour ne pas créer un doublon.
            return $deleted;
        }
        return $this->create($payload);
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
}
