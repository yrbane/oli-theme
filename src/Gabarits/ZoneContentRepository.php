<?php

declare(strict_types=1);

namespace OliTheme\Gabarits;

/**
 * Lit / écrit le contenu des zones d'un post (postmeta `_oli_gabarit_zones`).
 *
 * Format JSON stocké :
 * ```json
 * {
 *   "hero":    {"type": "image",   "imageId": 42},
 *   "intro":   {"type": "text",    "text": "<p>...</p>"},
 *   "gallery": {"type": "gallery", "imageIds": [1, 2, 3]}
 * }
 * ```
 *
 * @package OliTheme\Gabarits
 *
 * @since 1.5.0
 */
final class ZoneContentRepository
{
    public const POSTMETA = '_oli_gabarit_zones';

    /**
     * @return array<string, ZoneContent> zoneId → contenu typé
     */
    public function load(int $postId): array
    {
        if ($postId <= 0 || !\function_exists('get_post_meta')) {
            return [];
        }
        $raw = get_post_meta($postId, self::POSTMETA, true);
        if (!\is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!\is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $zoneId => $payload) {
            if (!\is_string($zoneId) || !\is_array($payload)) {
                continue;
            }
            $content = ZoneContent::fromArray($payload);
            if ($content !== null) {
                $out[$zoneId] = $content;
            }
        }
        return $out;
    }

    /**
     * @param array<string, ZoneContent> $contents
     */
    public function save(int $postId, array $contents): void
    {
        if ($postId <= 0 || !\function_exists('update_post_meta')) {
            return;
        }
        $clean = [];
        foreach ($contents as $zoneId => $content) {
            if (!\is_string($zoneId) || $zoneId === '' || !$content instanceof ZoneContent) {
                continue;
            }
            if ($content->isEmpty()) {
                continue; // Ne pas stocker les zones vides.
            }
            $clean[$zoneId] = $content->toArray();
        }
        if (empty($clean)) {
            \function_exists('delete_post_meta') && delete_post_meta($postId, self::POSTMETA);
            return;
        }
        update_post_meta($postId, self::POSTMETA, (string) json_encode($clean));
    }
}
