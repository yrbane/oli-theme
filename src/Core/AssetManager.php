<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Gestionnaire d'enregistrement des assets CSS / JS du thème.
 *
 * Versionne automatiquement les fichiers via filemtime() pour que les
 * navigateurs invalident leur cache à chaque modification du fichier.
 * À enregistrer sur les hooks 'wp_enqueue_scripts' (front) et
 * 'admin_enqueue_scripts' (admin).
 *
 * @package OliTheme\Core
 *
 * @since 1.0.0
 */
final class AssetManager
{
    /**
     * @param string $themePath Chemin absolu du thème (sans slash final).
     * @param string $themeUri URL absolue du thème (sans slash final).
     */
    public function __construct(
        private readonly string $themePath,
        private readonly string $themeUri,
    ) {
    }

    /**
     * Enregistre les feuilles de styles et modules JS frontaux.
     */
    public function enqueueFront(): void
    {
        wp_enqueue_style(
            'oli-theme',
            $this->themeUri . '/assets/css/main.css',
            [],
            $this->version('assets/css/main.css'),
        );

        $variationEnqueued = $this->enqueueVariation();

        // Admin bar : chargé en dernier pour gagner la cascade sur la variation.
        wp_enqueue_style(
            'oli-theme-admin-bar',
            $this->themeUri . '/assets/css/admin-bar.css',
            [$variationEnqueued ? 'oli-theme-variation' : 'oli-theme'],
            $this->version('assets/css/admin-bar.css'),
        );

        // Surcharge admin de l'image bandeau (pages internes), via custom-property.
        $this->injectInternalBannerOverride($variationEnqueued ? 'oli-theme-variation' : 'oli-theme');

        wp_enqueue_script_module(
            'oli-theme',
            $this->themeUri . '/assets/js/main.js',
            [],
            $this->version('assets/js/main.js'),
        );
    }

    /**
     * Enregistre les assets de l'administration WordPress.
     *
     * Les assets SEO (compteurs live, preview SERP, gauge) sont chargés
     * uniquement sur les écrans d'édition de contenu et les pages SEO.
     *
     * @param string $hookSuffix Suffixe de hook passé par admin_enqueue_scripts.
     */
    public function enqueueAdmin(string $hookSuffix = ''): void
    {
        wp_enqueue_style(
            'oli-theme-admin',
            $this->themeUri . '/assets/css/admin.css',
            [],
            $this->version('assets/css/admin.css'),
        );

        // Charge les assets SEO admin uniquement sur les écrans d'édition de contenu et les pages SEO.
        $isEditScreen = \in_array($hookSuffix, ['post.php', 'post-new.php'], true);
        $isSeoPage = str_starts_with($hookSuffix, 'tools_page_oli-seo-');
        if (! $isEditScreen && ! $isSeoPage) {
            return;
        }

        wp_enqueue_style(
            'oli-theme-seo-admin',
            $this->themeUri . '/assets/css/seo-admin.css',
            [],
            $this->version('assets/css/seo-admin.css'),
        );
        wp_enqueue_script_module(
            'oli-theme-seo-admin',
            $this->themeUri . '/assets/js/seo-metabox.js',
            [],
            $this->version('assets/js/seo-metabox.js'),
        );
    }

    /**
     * Enqueue la variation CSS sélectionnée (après main.css pour l'overrider).
     * Retourne true si une variation a été enqueuée, false sinon — utilisé
     * par {@see self::enqueueFront()} pour calculer les bonnes dépendances.
     */
    private function enqueueVariation(): bool
    {
        if (!\function_exists('get_option')) {
            return false;
        }

        $variation = (string) get_option('oli_theme_variation', '');
        if ($variation === '') {
            return false;
        }

        // Sécurité : sanitize_key pour éviter le path-traversal (ex. "../../wp-config").
        if (\function_exists('sanitize_key')) {
            $variation = sanitize_key($variation);
        }
        if ($variation === '') {
            return false;
        }

        $relative = 'assets/css/variations/' . $variation . '.css';
        $absolute = $this->themePath . '/' . $relative;
        if (!file_exists($absolute)) {
            return false;
        }

        wp_enqueue_style(
            'oli-theme-variation',
            $this->themeUri . '/' . $relative,
            ['oli-theme'],
            $this->version($relative),
        );

        return true;
    }

    /**
     * Injecte une CSS custom-property `--oli-internal-banner-url` quand
     * l'admin a choisi une image personnalisée pour le bandeau des pages
     * internes (option `oli_internal_banner_image`). Les variations qui
     * supportent cette property l'utilisent à la place de l'image par défaut.
     */
    private function injectInternalBannerOverride(string $handle): void
    {
        if (!\function_exists('get_option')) {
            return;
        }

        $url = (string) get_option('oli_internal_banner_image', '');
        if ($url === '') {
            return;
        }

        if (\function_exists('esc_url')) {
            $url = esc_url($url);
        }
        if ($url === '') {
            return;
        }

        if (!\function_exists('wp_add_inline_style')) {
            return;
        }

        $css = \sprintf("html{--oli-internal-banner-url:url('%s');}", $url);
        wp_add_inline_style($handle, $css);
    }

    /**
     * Calcule la version d'un fichier à partir de son mtime
     * pour invalider le cache navigateur lors d'une modification.
     */
    private function version(string $relativePath): string
    {
        $absolute = $this->themePath . '/' . $relativePath;

        return file_exists($absolute) ? (string) filemtime($absolute) : '1.0.0';
    }
}
