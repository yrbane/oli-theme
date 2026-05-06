<?php

declare(strict_types=1);

namespace OliTheme\Events;

use OliTheme\I18n\LanguageRegistryInterface;

/**
 * Rewrite rules de l'archive `oli_event` localisées par langue.
 *
 * Le CPT `oli_event` n'a qu'un seul slug d'archive natif (`evenements`).
 * Pour que `/en/events/`, `/it/eventi/`, `/es/eventos/` rendent l'archive
 * dynamique du CPT (et non la page WP qui partage le même slug), on
 * enregistre ces routes en `top` priority avant les rewrites génériques
 * de `RewriteRules` qui force `pagename=` (donc une page WP).
 *
 * @package OliTheme\Events
 *
 * @since 1.0.0
 */
final class EventArchiveRewriteRules
{
    /**
     * Slug d'archive par langue. La langue par défaut (fr) est gérée
     * nativement par le CPT (`evenements`) — pas de rewrite custom à
     * ajouter pour elle.
     *
     * @var array<string, string>
     */
    private const ARCHIVE_SLUGS = [
        'fr' => 'evenements',
        'en' => 'events',
        'it' => 'eventi',
        'es' => 'eventos',
    ];

    public function __construct(private readonly LanguageRegistryInterface $registry)
    {
    }

    /**
     * Enregistre les rewrites archive par langue. À brancher sur `init`,
     * priorité supérieure à celle de `RewriteRules` (= avant les routes
     * `^xx/(.+)/?$ → pagename=$1`).
     */
    public function register(): void
    {
        $default = $this->registry->default();

        foreach ($this->registry->all() as $language) {
            $slug = self::ARCHIVE_SLUGS[$language->code] ?? null;
            if ($slug === null) {
                continue;
            }

            // Langue par défaut : pas de préfixe d'URL.
            // Le slug `evenements` est déjà géré nativement par le CPT
            // (rewrite `evenements` enregistré dans EventCpt). Pas besoin
            // d'ajouter de règle ici, le CPT s'en occupe.
            if ($language->equals($default)) {
                continue;
            }

            $prefix = $language->code . '/' . $slug;
            $args   = 'oli_lang=' . $language->code . '&post_type=oli_event';

            // /en/events/
            add_rewrite_rule('^' . $prefix . '/?$', 'index.php?' . $args, 'top');
            // /en/events/page/2/
            add_rewrite_rule('^' . $prefix . '/page/([0-9]+)/?$', 'index.php?' . $args . '&paged=$matches[1]', 'top');
            // /en/events/?s=foo (recherche dans l'archive)
            add_rewrite_rule('^' . $prefix . '/feed/?$', 'index.php?' . $args . '&feed=feed', 'top');
        }
    }

    /**
     * Slug d'archive pour une langue donnée (utile aux liens internes).
     */
    public static function slugFor(string $languageCode): ?string
    {
        return self::ARCHIVE_SLUGS[$languageCode] ?? null;
    }
}
