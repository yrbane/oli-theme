<?php

declare(strict_types=1);

namespace OliTheme\Admin;

/**
 * Calcule l'URL cible vers la page de réglages unifiée pour un ancien slug
 * d'admin (compatibilité des liens et bookmarks après consolidation).
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
final class LegacySlugRedirector
{
    /** @var array<string, array{tab: string, sub: string}> */
    private const MAP = [
        'oli-social-links'     => ['tab' => 'reseaux',   'sub' => 'comptes'],
        'oli-gallery'          => ['tab' => 'contenu',   'sub' => 'galerie'],
        'oli-theme-variations' => ['tab' => 'apparence', 'sub' => 'variations'],
        'oli-seo-dashboard'    => ['tab' => 'seo',       'sub' => 'dashboard'],
        'oli-seo-redirects'    => ['tab' => 'seo',       'sub' => 'redirections'],
    ];

    /**
     * URL cible pour un ancien slug, ou null si le slug n'est pas concerné.
     *
     * @param array<string, scalar> $extra Paramètres GET additionnels à propager
     *                                     (ex. `edit`, `paged`), hors `page`.
     */
    public function targetFor(string $slug, array $extra): ?string
    {
        if (!isset(self::MAP[$slug])) {
            return null;
        }

        $args = [
            'page' => ThemeAdminPage::PAGE_SLUG,
            'tab'  => self::MAP[$slug]['tab'],
            'sub'  => self::MAP[$slug]['sub'],
        ];
        unset($extra['page']);
        $args += $extra;

        return add_query_arg($args, admin_url('themes.php'));
    }
}
