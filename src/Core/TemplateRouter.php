<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Aiguille la résolution `template_include` de WordPress vers `theme-bridge/`.
 *
 * WordPress cherche par défaut `front-page.php`, `page.php`, `single.php`, etc.
 * à la racine du thème. oli-theme stocke ces shims dans `theme-bridge/` pour
 * isoler la frontière WP du code MVC. Ce router recolle les deux mondes en
 * branchant un filtre `template_include`. Voir issue #4.
 *
 * @package OliTheme\Core
 *
 * @since 1.0.0
 */
final class TemplateRouter
{
    public function __construct(private readonly string $bridgePath)
    {
    }

    /**
     * Résout le template à charger pour la requête WP courante.
     *
     * Retourne `$original` si aucune condition ne matche ou si le fichier
     * bridge attendu est absent (cas dégradé : on laisse WP continuer son
     * fallback de hiérarchie standard plutôt que de lever un fatal include).
     */
    public function resolve(string $original): string
    {
        $candidate = $this->pickBridgeFile();
        if ($candidate === null) {
            return $original;
        }

        $full = $this->bridgePath . '/' . $candidate;
        if (!is_file($full)) {
            return $original;
        }

        return $full;
    }

    /**
     * Détermine le nom de fichier bridge correspondant à la condition WP active.
     *
     * Ordre du match identique à la hiérarchie WP : front-page → singular CPT
     * → archive CPT → page → single → search → archive → 404. Le premier
     * match remporte.
     */
    private function pickBridgeFile(): ?string
    {
        if (
            \function_exists('is_front_page')
            && is_front_page()
            && (int) get_option('page_on_front', 0) > 0
        ) {
            return 'front-page.php';
        }

        if (\function_exists('is_singular') && is_singular('oli_event')) {
            return 'single-oli_event.php';
        }

        if (\function_exists('is_post_type_archive') && is_post_type_archive('oli_event')) {
            return 'archive-oli_event.php';
        }

        if (\function_exists('is_page') && is_page()) {
            return 'page.php';
        }

        if (\function_exists('is_single') && is_single()) {
            return 'single.php';
        }

        if (\function_exists('is_search') && is_search()) {
            return 'search.php';
        }

        if (\function_exists('is_archive') && is_archive()) {
            return 'archive.php';
        }

        if (\function_exists('is_404') && is_404()) {
            return '404.php';
        }

        return null;
    }
}
