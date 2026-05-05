<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * DTO immuable pour les paramètres SEO globaux du thème.
 *
 * Regroupe l'image Open Graph par défaut, le handle Twitter,
 * les informations d'organisation et les options de sitemap/robots.txt.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final readonly class SeoSettings
{
    /**
     * @param int|null $ogImageId Identifiant de l'image Open Graph par défaut.
     * @param string|null $twitterHandle Handle Twitter/X (sans le @).
     * @param string|null $organizationName Nom de l'organisation (données structurées).
     * @param string|null $organizationLogoUrl URL du logo de l'organisation.
     * @param bool $sitemapEnabled Activer la génération du sitemap XML.
     * @param string|null $robotsTxtCustom Contenu personnalisé du fichier robots.txt.
     */
    public function __construct(
        public ?int $ogImageId,
        public ?string $twitterHandle,
        public ?string $organizationName,
        public ?string $organizationLogoUrl,
        public bool $sitemapEnabled,
        public ?string $robotsTxtCustom,
    ) {
    }
}
