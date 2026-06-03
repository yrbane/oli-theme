<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Lifecycle;

use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\Publisher\PublisherInterface;
use OliTheme\MetaSync\Publisher\PublishPayload;

/**
 * Orchestre le cycle de vie d'un post WP côté Meta : create / edit / delete
 * sur chaque cible (Facebook, Instagram).
 *
 * Branché sur les hooks `publish_post`, `post_updated`, `before_delete_post`,
 * `wp_trash_post`.
 *
 * @package OliTheme\MetaSync\Lifecycle
 *
 * @since 1.3.0
 */
final class MetaSyncDispatcher
{
    public function __construct(
        private readonly PayloadExtractorInterface $extractor,
        private readonly MetaPostState $state,
        private readonly PublisherInterface $facebook,
        private readonly PublisherInterface $instagram,
    ) {
    }

    public function onPublish(int $postId): void
    {
        if (!$this->state->isEnabled($postId)) {
            return;
        }
        $payload = $this->extractor->fromPost($postId);
        if ($payload === null) {
            return;
        }
        // Évite la double-création (déjà synchronisé).
        if ($this->state->fbPostId($postId) === '' && \in_array('facebook', $this->state->targets($postId), true)) {
            $this->doCreate($payload, 'facebook');
        }
        if ($this->state->igMediaId($postId) === '' && \in_array('instagram', $this->state->targets($postId), true)) {
            $this->doCreate($payload, 'instagram');
        }
        $this->state->setContentHash($postId, $payload->contentHash());
    }

    public function onUpdate(int $postId): void
    {
        if (!$this->state->isEnabled($postId)) {
            return;
        }
        $payload = $this->extractor->fromPost($postId);
        if ($payload === null) {
            return;
        }
        $newHash = $payload->contentHash();
        if ($newHash === $this->state->contentHash($postId)) {
            return; // contenu inchangé, rien à pousser.
        }
        $targets = $this->state->targets($postId);
        if (\in_array('facebook', $targets, true)) {
            $fbId = $this->state->fbPostId($postId);
            if ($fbId === '') {
                $this->doCreate($payload, 'facebook');
            } else {
                $this->doEdit($payload, 'facebook', $fbId);
            }
        }
        if (\in_array('instagram', $targets, true)) {
            $igId = $this->state->igMediaId($postId);
            if ($igId === '') {
                $this->doCreate($payload, 'instagram');
            } else {
                $this->doEdit($payload, 'instagram', $igId);
            }
        }
        $this->state->setContentHash($postId, $newHash);
    }

    public function onDelete(int $postId): void
    {
        $fbId = $this->state->fbPostId($postId);
        $igId = $this->state->igMediaId($postId);
        if ($fbId !== '') {
            $result = $this->facebook->delete($fbId);
            if ($result instanceof GraphApiError) {
                $this->state->recordError($postId, '[FB delete] ' . $result->message);
            } else {
                $this->state->recordDelete($postId, 'facebook');
            }
        }
        if ($igId !== '') {
            $result = $this->instagram->delete($igId);
            if ($result instanceof GraphApiError) {
                $this->state->recordError($postId, '[IG delete] ' . $result->message);
            } else {
                $this->state->recordDelete($postId, 'instagram');
            }
        }
    }

    private function doCreate(PublishPayload $payload, string $platform): void
    {
        $publisher = $platform === 'facebook' ? $this->facebook : $this->instagram;
        $result    = $publisher->create($payload);
        if ($result instanceof GraphApiError) {
            $this->state->recordError($payload->postId, sprintf('[%s create] %s', strtoupper($platform), $result->message));
            return;
        }
        $this->state->recordCreate($payload->postId, $platform, $result);
    }

    private function doEdit(PublishPayload $payload, string $platform, string $externalId): void
    {
        $publisher = $platform === 'facebook' ? $this->facebook : $this->instagram;
        $result    = $publisher->edit($externalId, $payload);
        if ($result instanceof GraphApiError) {
            $this->state->recordError($payload->postId, sprintf('[%s edit] %s', strtoupper($platform), $result->message));
            return;
        }
        if ($result !== $externalId) {
            // ID changé (cas IG delete+recreate) → enregistre comme un create.
            $this->state->recordCreate($payload->postId, $platform, $result);
        } else {
            $this->state->recordEdit($payload->postId, $platform, $result);
        }
    }
}
