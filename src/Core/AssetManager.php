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
     * Étendu plus tard par les modules SEO / Settings.
     */
    public function enqueueAdmin(): void
    {
        wp_enqueue_style(
            'oli-theme-admin',
            $this->themeUri . '/assets/css/admin.css',
            [],
            $this->version('assets/css/admin.css'),
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
