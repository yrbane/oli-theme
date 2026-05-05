<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * DTO immuable des métadonnées SEO d'un contenu WordPress.
 *
 * Toutes les propriétés sont en lecture seule et initialisées à la construction.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final readonly class SeoMeta
{
    /**
     * @param string|null $title Titre SEO personnalisé.
     * @param string|null $description Description SEO personnalisée.
     * @param string|null $focusKeyword Mot-clé principal ciblé.
     * @param string[] $additionalKeywords Mots-clés secondaires.
     * @param int|null $ogImageId ID de l'image Open Graph.
     * @param string $twitterCardType Type de carte Twitter.
     * @param bool $noindex Empêche l'indexation par les moteurs.
     * @param bool $nofollow Empêche le suivi des liens.
     * @param string|null $canonical URL canonique personnalisée.
     * @param float|null $priority Priorité dans le sitemap (0.0 à 1.0).
     * @param string|null $changefreq Fréquence de changement pour le sitemap.
     * @param int|null $readabilityScore Score de lisibilité (0 à 100).
     * @param int|null $seoScore Score SEO global (0 à 100).
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $focusKeyword = null,
        public array $additionalKeywords = [],
        public ?int $ogImageId = null,
        public string $twitterCardType = 'summary_large_image',
        public bool $noindex = false,
        public bool $nofollow = false,
        public ?string $canonical = null,
        public ?float $priority = null,
        public ?string $changefreq = null,
        public ?int $readabilityScore = null,
        public ?int $seoScore = null,
    ) {
    }
}
