<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Modèle de lecture/écriture des métadonnées SEO via les post metas WordPress.
 *
 * Toutes les clés sont préfixées `_oli_seo_`. Aucun appel WordPress ne fuit
 * hors de cette classe.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class SeoMetaModel implements SeoMetaModelInterface
{
    private const PREFIX = '_oli_seo_';

    /**
     * Charge les métadonnées SEO d'un post depuis les post metas WordPress.
     */
    public function find(int $postId): SeoMeta
    {
        $title = $this->getString($postId, 'title');
        $description = $this->getString($postId, 'description');
        $focusKeyword = $this->getString($postId, 'focus_keyword');

        $keywordsRaw = get_post_meta($postId, self::PREFIX . 'additional_keywords', true);
        $additionalKeywords = \is_array($keywordsRaw) ? $keywordsRaw : [];

        $ogImageIdRaw = $this->getString($postId, 'og_image_id');
        $ogImageId = $ogImageIdRaw !== null ? (int) $ogImageIdRaw : null;

        $twitterCardType = $this->getString($postId, 'twitter_card_type') ?? 'summary_large_image';

        $noindexRaw = get_post_meta($postId, self::PREFIX . 'noindex', true);
        $noindex = $noindexRaw === '1';

        $nofollowRaw = get_post_meta($postId, self::PREFIX . 'nofollow', true);
        $nofollow = $nofollowRaw === '1';

        $canonical = $this->getString($postId, 'canonical');

        $priorityRaw = $this->getString($postId, 'priority');
        $priority = $priorityRaw !== null ? (float) $priorityRaw : null;

        $changefreq = $this->getString($postId, 'changefreq');

        $readabilityRaw = $this->getString($postId, 'readability_score');
        $readabilityScore = $readabilityRaw !== null ? (int) $readabilityRaw : null;

        $seoRaw = $this->getString($postId, 'seo_score');
        $seoScore = $seoRaw !== null ? (int) $seoRaw : null;

        return new SeoMeta(
            title: $title,
            description: $description,
            focusKeyword: $focusKeyword,
            additionalKeywords: $additionalKeywords,
            ogImageId: $ogImageId,
            twitterCardType: $twitterCardType,
            noindex: $noindex,
            nofollow: $nofollow,
            canonical: $canonical,
            priority: $priority,
            changefreq: $changefreq,
            readabilityScore: $readabilityScore,
            seoScore: $seoScore,
        );
    }

    /**
     * Persiste les métadonnées SEO d'un post dans les post metas WordPress.
     */
    public function save(int $postId, SeoMeta $meta): void
    {
        $this->updateOrDelete($postId, 'title', $meta->title);
        $this->updateOrDelete($postId, 'description', $meta->description);
        $this->updateOrDelete($postId, 'focus_keyword', $meta->focusKeyword);

        update_post_meta($postId, self::PREFIX . 'additional_keywords', $meta->additionalKeywords);
        $this->updateOrDelete($postId, 'og_image_id', $meta->ogImageId);

        update_post_meta($postId, self::PREFIX . 'twitter_card_type', $meta->twitterCardType);
        update_post_meta($postId, self::PREFIX . 'noindex', $meta->noindex ? '1' : '');
        update_post_meta($postId, self::PREFIX . 'nofollow', $meta->nofollow ? '1' : '');

        $this->updateOrDelete($postId, 'canonical', $meta->canonical);
        $this->updateOrDelete($postId, 'priority', $meta->priority);
        $this->updateOrDelete($postId, 'changefreq', $meta->changefreq);
        $this->updateOrDelete($postId, 'readability_score', $meta->readabilityScore);
        $this->updateOrDelete($postId, 'seo_score', $meta->seoScore);
    }

    /**
     * Lit une meta brute avec valeur par défaut.
     */
    public function getMeta(int $postId, string $key, mixed $default = null): mixed
    {
        $value = get_post_meta($postId, $key, true);
        if ($value === '' || $value === false || $value === null) {
            return $default;
        }

        return $value;
    }

    /**
     * Lit une meta en tant que chaîne, retourne null si vide ou absente.
     */
    private function getString(int $postId, string $suffix): ?string
    {
        $value = get_post_meta($postId, self::PREFIX . $suffix, true);
        if ($value === '' || $value === false || $value === null) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Met à jour la meta si la valeur est non nulle, sinon la supprime.
     */
    private function updateOrDelete(int $postId, string $suffix, mixed $value): void
    {
        if ($value === null) {
            delete_post_meta($postId, self::PREFIX . $suffix);

            return;
        }

        update_post_meta($postId, self::PREFIX . $suffix, $value);
    }
}
