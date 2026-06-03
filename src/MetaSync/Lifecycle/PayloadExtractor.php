<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Lifecycle;

use OliTheme\MetaSync\Publisher\PublishPayload;

/**
 * Construit un {@see PublishPayload} à partir d'un post WordPress.
 *
 * @package OliTheme\MetaSync\Lifecycle
 *
 * @since 1.3.0
 */
final class PayloadExtractor implements PayloadExtractorInterface
{
    public function fromPost(int $postId): ?PublishPayload
    {
        $post = get_post($postId);
        if ($post === null || ($post->post_status ?? '') !== 'publish') {
            return null;
        }

        $title     = (string) ($post->post_title ?? '');
        $excerpt   = (string) ($post->post_excerpt ?? '');
        $content   = wp_strip_all_tags((string) ($post->post_content ?? ''));
        $permalink = (string) get_permalink($postId);

        $imageUrl  = '';
        if (\function_exists('get_post_thumbnail_id')) {
            $thumbId = (int) get_post_thumbnail_id($postId);
            if ($thumbId > 0 && \function_exists('wp_get_attachment_image_url')) {
                $imageUrl = (string) (wp_get_attachment_image_url($thumbId, 'large') ?: '');
            }
        }

        $language = 'fr';
        if (\function_exists('wp_get_post_terms')) {
            $terms = wp_get_post_terms($postId, 'language');
            if (\is_array($terms) && !empty($terms) && isset($terms[0]->slug)) {
                $language = (string) $terms[0]->slug;
            }
        }

        return new PublishPayload(
            postId:           $postId,
            title:            $title,
            excerpt:          $excerpt,
            contentText:      $content,
            permalink:        $permalink,
            featuredImageUrl: $imageUrl,
            hashtags:         [],
            language:         $language,
        );
    }
}
