<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Lifecycle;

/**
 * Accesseur typé des postmeta `_oli_meta_*` qui pilotent la synchro Meta
 * par post WP (opt-in, ID externe, statut, hash).
 *
 * @package OliTheme\MetaSync\Lifecycle
 *
 * @since 1.3.0
 */
final class MetaPostState
{
    public const META_ENABLED         = '_oli_meta_sync_enabled';
    public const META_TARGETS         = '_oli_meta_sync_targets';
    public const META_FB_ID           = '_oli_meta_fb_post_id';
    public const META_IG_ID           = '_oli_meta_ig_media_id';
    public const META_FB_URL          = '_oli_meta_fb_url';
    public const META_IG_URL          = '_oli_meta_ig_url';
    public const META_LAST_SYNC_AT    = '_oli_meta_last_sync_at';
    public const META_LAST_SYNC_STATUS = '_oli_meta_last_sync_status';
    public const META_LAST_SYNC_ERROR = '_oli_meta_last_sync_error';
    public const META_CONTENT_HASH    = '_oli_meta_content_hash';

    public function isEnabled(int $postId): bool
    {
        return (bool) get_post_meta($postId, self::META_ENABLED, true);
    }

    /**
     * @return list<string> subset of ['facebook', 'instagram']
     */
    public function targets(int $postId): array
    {
        $raw = get_post_meta($postId, self::META_TARGETS, true);
        if (!\is_array($raw)) {
            return ['facebook', 'instagram'];
        }
        return array_values(array_intersect(['facebook', 'instagram'], array_map('strval', $raw)));
    }

    public function fbPostId(int $postId): string
    {
        return (string) get_post_meta($postId, self::META_FB_ID, true);
    }

    public function igMediaId(int $postId): string
    {
        return (string) get_post_meta($postId, self::META_IG_ID, true);
    }

    public function contentHash(int $postId): string
    {
        return (string) get_post_meta($postId, self::META_CONTENT_HASH, true);
    }

    public function recordCreate(int $postId, string $platform, string $externalId, string $url = ''): void
    {
        if ($platform === 'facebook') {
            update_post_meta($postId, self::META_FB_ID, $externalId);
            if ($url !== '') {
                update_post_meta($postId, self::META_FB_URL, $url);
            }
        }
        if ($platform === 'instagram') {
            update_post_meta($postId, self::META_IG_ID, $externalId);
            if ($url !== '') {
                update_post_meta($postId, self::META_IG_URL, $url);
            }
        }
        $this->stampSync($postId, 'synced', '');
    }

    public function recordEdit(int $postId, string $platform, string $newExternalId): void
    {
        if ($platform === 'facebook') {
            update_post_meta($postId, self::META_FB_ID, $newExternalId);
        }
        if ($platform === 'instagram') {
            update_post_meta($postId, self::META_IG_ID, $newExternalId);
        }
        $this->stampSync($postId, 'synced', '');
    }

    public function recordDelete(int $postId, string $platform): void
    {
        if ($platform === 'facebook') {
            delete_post_meta($postId, self::META_FB_ID);
            delete_post_meta($postId, self::META_FB_URL);
        }
        if ($platform === 'instagram') {
            delete_post_meta($postId, self::META_IG_ID);
            delete_post_meta($postId, self::META_IG_URL);
        }
        $this->stampSync($postId, 'synced', '');
    }

    public function recordError(int $postId, string $message): void
    {
        $this->stampSync($postId, 'error', $message);
    }

    public function setContentHash(int $postId, string $hash): void
    {
        update_post_meta($postId, self::META_CONTENT_HASH, $hash);
    }

    private function stampSync(int $postId, string $status, string $error): void
    {
        update_post_meta($postId, self::META_LAST_SYNC_AT, gmdate('c'));

        // Si une opération précédente du même cycle a écrit une erreur, on ne
        // la « blanchit » pas avec un status `synced` — on passe à `partial` et
        // on conserve le message d'erreur pour qu'Olivier le voie.
        $previous = (string) get_post_meta($postId, self::META_LAST_SYNC_STATUS, true);
        if ($status === 'synced' && ($previous === 'error' || $previous === 'partial')) {
            update_post_meta($postId, self::META_LAST_SYNC_STATUS, 'partial');
            return;
        }

        update_post_meta($postId, self::META_LAST_SYNC_STATUS, $status);
        if ($status === 'error') {
            update_post_meta($postId, self::META_LAST_SYNC_ERROR, $error);
        } else {
            delete_post_meta($postId, self::META_LAST_SYNC_ERROR);
        }
    }
}
