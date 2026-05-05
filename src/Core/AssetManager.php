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
        $isSeoPage = \str_starts_with($hookSuffix, 'tools_page_oli-seo-');
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
     * Calcule la version d'un fichier à partir de son mtime
     * pour invalider le cache navigateur lors d'une modification.
     */
    private function version(string $relativePath): string
    {
        $absolute = $this->themePath . '/' . $relativePath;

        return file_exists($absolute) ? (string) filemtime($absolute) : '1.0.0';
    }
}
