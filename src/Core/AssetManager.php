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
     * Police Google Fonts requise par une variation (slug => spec `family`).
     * Chargée via wp_enqueue_style (parallèle) plutôt qu'un @import bloquant.
     *
     * @var array<string, string>
     */
    private const VARIATION_FONTS = [
        'olikalari' => 'Manrope:wght@400;500;700',
    ];

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

        // Police personnalisée pour les titres (Google Fonts) si configurée.
        $this->injectTitlesFontOverride('oli-theme-admin-bar');

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

        // Charge la feuille de style des bulles d'aide et de l'onglet Aide
        // sur toutes les pages de réglages du thème.
        if ($hookSuffix === 'appearance_page_oli-theme-settings') {
            wp_enqueue_style(
                'oli-theme-admin-help',
                $this->themeUri . '/assets/css/admin-help.css',
                ['oli-theme-admin'],
                $this->version('assets/css/admin-help.css'),
            );
        }

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
     * Active les styles de l'éditeur de blocs (typographie front reflétée).
     * À brancher sur `after_setup_theme`.
     */
    public function registerEditorStyles(): void
    {
        if (!\function_exists('add_theme_support') || !\function_exists('add_editor_style')) {
            return;
        }
        add_theme_support('editor-styles');
        add_editor_style('assets/css/editor-style.css');
    }

    /**
     * Active les supports thématiques globaux : image mise en avant, titre dynamique.
     * À brancher sur `after_setup_theme`.
     *
     * Sans `post-thumbnails`, WordPress n'affiche pas la metabox « Image mise en
     * avant » même si un CPT déclare `'thumbnail'` dans `supports` — c'est ce qui
     * a empêché Olivier de pouvoir choisir l'image d'une slide.
     */
    public function registerThemeSupports(): void
    {
        if (!\function_exists('add_theme_support')) {
            return;
        }
        add_theme_support('post-thumbnails');
        add_theme_support('title-tag');
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

        // Police Google Fonts de la variation : enqueue parallèle + preconnect
        // (remplace l'ancien @import bloquant et sériel dans le CSS).
        if (isset(self::VARIATION_FONTS[$variation]) && \function_exists('wp_enqueue_style')) {
            $url = 'https://fonts.googleapis.com/css2?family='
                . self::VARIATION_FONTS[$variation] . '&display=swap';
            wp_enqueue_style('oli-theme-variation-font', $url, [], null);
            $this->preconnectGoogleFonts();
        }

        return true;
    }

    /**
     * Ajoute des resource hints `preconnect` vers les hôtes Google Fonts pour
     * réduire la latence de chargement de la police.
     */
    private function preconnectGoogleFonts(): void
    {
        if (!\function_exists('add_filter')) {
            return;
        }
        add_filter('wp_resource_hints', static function (array $hints, string $relation): array {
            if ($relation === 'preconnect') {
                $hints[] = 'https://fonts.googleapis.com';
                $hints[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin'];
            }
            return $hints;
        }, 10, 2);
    }

    /**
     * Injecte une CSS custom-property `--oli-internal-banner-url` qui pilote
     * l'image du bandeau des pages internes.
     *
     * Trois sources de priorité décroissante :
     *  1. `oli_theme_settings[banner][bannerDesktopId]` / `bannerMobileId` :
     *     médiathèque, deux fichiers (desktop & mobile). Si les deux sont
     *     renseignés, on émet la version mobile par défaut + un media query
     *     `(min-width: 768px)` qui bascule sur la version desktop. Si un seul
     *     est renseigné, il s'applique partout.
     *  2. `oli_internal_banner_image` (legacy : URL unique, sélecteur
     *     historique sur l'écran Apparence > Variations CSS).
     *  3. Image par défaut du thème (codée dans la variation CSS active).
     */
    private function injectInternalBannerOverride(string $handle): void
    {
        if (!\function_exists('get_option') || !\function_exists('wp_add_inline_style')) {
            return;
        }

        [$desktopUrl, $mobileUrl] = $this->resolveResponsiveBannerUrls();

        $css = $this->buildBannerCss($desktopUrl, $mobileUrl);
        if ($css === '') {
            // Fallback legacy : URL unique côté Apparence.
            $legacy = (string) get_option('oli_internal_banner_image', '');
            if ($legacy === '') {
                return;
            }
            $legacy = \function_exists('esc_url') ? (string) esc_url($legacy) : $legacy;
            if ($legacy === '') {
                return;
            }
            $css = \sprintf("html{--oli-internal-banner-url:url('%s');}", $legacy);
        }

        wp_add_inline_style($handle, $css);
    }

    /**
     * Lit les IDs de bannière responsive depuis les Settings et résout les URLs.
     *
     * @return array{0:string,1:string} desktopUrl, mobileUrl (vide si absent)
     */
    private function resolveResponsiveBannerUrls(): array
    {
        $settings = get_option('oli_theme_settings', []);
        if (!\is_array($settings)) {
            return ['', ''];
        }
        $banner = isset($settings['banner']) && \is_array($settings['banner']) ? $settings['banner'] : [];

        $desktopId = isset($banner['bannerDesktopId']) ? (int) $banner['bannerDesktopId'] : 0;
        $mobileId  = isset($banner['bannerMobileId']) ? (int) $banner['bannerMobileId'] : 0;

        $resolve = static function (int $id): string {
            if ($id <= 0 || !\function_exists('wp_get_attachment_image_url')) {
                return '';
            }
            $url = wp_get_attachment_image_url($id, 'full');
            if (!\is_string($url) || $url === '') {
                return '';
            }

            return \function_exists('esc_url') ? (string) esc_url($url) : $url;
        };

        return [$resolve($desktopId), $resolve($mobileId)];
    }

    /**
     * Construit la déclaration CSS de la custom-property selon les URLs disponibles.
     */
    private function buildBannerCss(string $desktopUrl, string $mobileUrl): string
    {
        if ($desktopUrl === '' && $mobileUrl === '') {
            return '';
        }

        $base = $mobileUrl !== '' ? $mobileUrl : $desktopUrl;
        $css  = \sprintf("html{--oli-internal-banner-url:url('%s');}", $base);

        if ($desktopUrl !== '' && $mobileUrl !== '' && $desktopUrl !== $mobileUrl) {
            $css .= \sprintf(
                "@media(min-width:768px){html{--oli-internal-banner-url:url('%s');}}",
                $desktopUrl,
            );
        }

        return $css;
    }

    /**
     * Police personnalisée pour les titres (h1–h6, .banner__title,
     * .carousel-fullscreen__title) configurée via Apparence > Variations CSS.
     * Charge la stylesheet Google Fonts et injecte un override !important pour
     * gagner la cascade sur les variations.
     */
    private function injectTitlesFontOverride(string $handle): void
    {
        if (!\function_exists('get_option')) {
            return;
        }

        $family = (string) get_option('oli_theme_titles_font', '');
        if ($family === '') {
            return;
        }

        // Sécurité : la sanitize côté admin a déjà validé contre la liste
        // blanche, mais on re-nettoie pour le cas où l'option serait éditée
        // directement en base. On accepte uniquement [A-Za-z0-9 ].
        $family = trim((string) preg_replace('~[^A-Za-z0-9 ]~', '', $family));
        if ($family === '') {
            return;
        }

        if (!\function_exists('wp_enqueue_style') || !\function_exists('wp_add_inline_style')) {
            return;
        }

        // Google Fonts CSS (sans dépendances explicites — pas de race avec le thème).
        $url = 'https://fonts.googleapis.com/css2?family='
            . str_replace('%20', '+', rawurlencode($family))
            . ':wght@400;500;700&display=swap';

        wp_enqueue_style('oli-theme-titles-font', $url, [], null);

        // Override CSS sur les sélecteurs cibles (chargé en dernier via $handle).
        $css = \sprintf(
            "h1,h2,h3,h4,h5,h6,.banner__title,.carousel-fullscreen__title{font-family:'%s',system-ui,sans-serif !important;}",
            addslashes($family),
        );
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
