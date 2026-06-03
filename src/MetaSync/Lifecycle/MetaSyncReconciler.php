<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Lifecycle;

use OliTheme\MetaSync\Http\GraphApiClient;
use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\TokenStore;

/**
 * Cron quotidien qui réconcilie les metas locales avec l'état côté Meta.
 *
 * Pour chaque post WP avec un `_oli_meta_fb_post_id` ou `_oli_meta_ig_media_id`,
 * on ping Graph API. Si la cible renvoie 404 / code 803 (gone), on nettoie
 * la meta locale correspondante pour que le post puisse être re-synchronisé
 * proprement (la prochaine édition créera de nouveau côté Meta).
 *
 * @package OliTheme\MetaSync\Lifecycle
 *
 * @since 1.3.0
 */
final class MetaSyncReconciler
{
    public const CRON_HOOK = 'oli_meta_sync_reconcile';

    public function __construct(
        private readonly GraphApiClient $client,
        private readonly TokenStore $tokens,
        private readonly MetaPostState $state,
    ) {
    }

    /**
     * @return array{checked:int, cleaned:int}
     */
    public function run(): array
    {
        $creds = $this->tokens->load();
        if (!$creds->isConnected()) {
            return ['checked' => 0, 'cleaned' => 0];
        }
        if (!\function_exists('get_posts')) {
            return ['checked' => 0, 'cleaned' => 0];
        }

        $checked = 0;
        $cleaned = 0;

        // Récupère les posts WP avec une meta _oli_meta_fb_post_id non vide.
        $postsWithFb = get_posts([
            'post_type'   => 'any',
            'post_status' => 'any',
            'numberposts' => -1,
            'fields'      => 'ids',
            'meta_query'  => [['key' => MetaPostState::META_FB_ID, 'value' => '', 'compare' => '!=']],
        ]);
        foreach ((array) $postsWithFb as $postId) {
            $postId = (int) $postId;
            $fbId   = $this->state->fbPostId($postId);
            if ($fbId === '') {
                continue;
            }
            $checked++;
            $result = $this->client->get('/' . $fbId, ['fields' => 'id'], $creds->accessToken);
            if ($this->isMissing($result)) {
                $this->state->recordDelete($postId, 'facebook');
                $cleaned++;
            }
        }

        $postsWithIg = get_posts([
            'post_type'   => 'any',
            'post_status' => 'any',
            'numberposts' => -1,
            'fields'      => 'ids',
            'meta_query'  => [['key' => MetaPostState::META_IG_ID, 'value' => '', 'compare' => '!=']],
        ]);
        foreach ((array) $postsWithIg as $postId) {
            $postId = (int) $postId;
            $igId   = $this->state->igMediaId($postId);
            if ($igId === '') {
                continue;
            }
            $checked++;
            $result = $this->client->get('/' . $igId, ['fields' => 'id'], $creds->accessToken);
            if ($this->isMissing($result)) {
                $this->state->recordDelete($postId, 'instagram');
                $cleaned++;
            }
        }

        return ['checked' => $checked, 'cleaned' => $cleaned];
    }

    /**
     * @param array<string, mixed>|GraphApiError $result
     */
    private function isMissing(array|GraphApiError $result): bool
    {
        if (!$result instanceof GraphApiError) {
            return false;
        }
        return $result->httpStatus === 404 || $result->graphCode === 803;
    }
}
