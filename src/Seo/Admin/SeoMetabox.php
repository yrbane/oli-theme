<?php

declare(strict_types=1);

namespace OliTheme\Seo\Admin;

use OliTheme\Core\RendererInterface;
use OliTheme\Seo\SeoMeta;
use OliTheme\Seo\SeoMetaModelInterface;

/**
 * Métabox SEO affichée sur l'écran d'édition de chaque post/page/oli_event.
 *
 * @package OliTheme\Seo\Admin
 *
 * @since 1.0.0
 */
final class SeoMetabox
{
    /** @var string[] */
    private const POST_TYPES = ['post', 'page', 'oli_event'];

    public function __construct(
        private readonly SeoMetaModelInterface $metaModel,
        private readonly RendererInterface $renderer,
    ) {
    }

    public function register(): void
    {
        foreach (self::POST_TYPES as $type) {
            add_meta_box(
                'oli_seo_meta',
                __('SEO', 'oli-theme'),
                [$this, 'render'],
                $type,
                'normal',
                'high',
            );
        }
    }

    public function render(\WP_Post $post): void
    {
        wp_nonce_field('oli_seo_meta', 'oli_seo_meta_nonce');
        $meta = $this->metaModel->find($post->ID);
        echo $this->renderer->render('admin/seo-metabox.html', [
            'meta' => $meta,
            'additionalKeywords' => implode(', ', $meta->additionalKeywords),
        ]);
    }

    /**
     * @param array<string, mixed> $postData
     */
    public function save(int $postId, array $postData): void
    {
        if (! isset($postData['oli_seo_meta_nonce'])
            || ! wp_verify_nonce((string) $postData['oli_seo_meta_nonce'], 'oli_seo_meta')) {
            return;
        }

        $additionalRaw = isset($postData['additional_keywords']) ? (string) $postData['additional_keywords'] : '';
        $additional = array_values(array_filter(array_map('trim', explode(',', $additionalRaw))));

        $meta = new SeoMeta(
            title: $this->stringOrNull($postData, 'seo_title'),
            description: $this->stringOrNull($postData, 'seo_description'),
            focusKeyword: $this->stringOrNull($postData, 'focus_keyword'),
            additionalKeywords: $additional,
            ogImageId: $this->intOrNull($postData, 'og_image_id'),
            twitterCardType: isset($postData['twitter_card_type']) ? (string) $postData['twitter_card_type'] : 'summary_large_image',
            noindex: ! empty($postData['noindex']),
            nofollow: ! empty($postData['nofollow']),
            canonical: $this->stringOrNull($postData, 'canonical'),
            priority: $this->floatOrNull($postData, 'priority'),
            changefreq: $this->stringOrNull($postData, 'changefreq'),
            readabilityScore: null,
            seoScore: null,
        );

        $this->metaModel->save($postId, $meta);
    }

    /** @param array<string, mixed> $data */
    private function stringOrNull(array $data, string $key): ?string
    {
        if (! isset($data[$key])) {
            return null;
        }
        $value = trim((string) $data[$key]);
        return $value === '' ? null : sanitize_text_field($value);
    }

    /** @param array<string, mixed> $data */
    private function intOrNull(array $data, string $key): ?int
    {
        if (! isset($data[$key]) || $data[$key] === '') {
            return null;
        }
        return (int) $data[$key];
    }

    /** @param array<string, mixed> $data */
    private function floatOrNull(array $data, string $key): ?float
    {
        if (! isset($data[$key]) || $data[$key] === '') {
            return null;
        }
        return (float) $data[$key];
    }
}
