<?php

declare(strict_types=1);

namespace OliTheme;

use OliTheme\Core\AssetManager;
use OliTheme\Core\CacheDirectoryEnsurer;
use OliTheme\Core\HookRegistrar;
use OliTheme\Core\RequestContext;
use OliTheme\Core\TemplateRouter;
use OliTheme\Core\ViewRenderer;

/**
 * Bootstrap principal du thème oli-theme.
 *
 * Singleton applicatif : la première invocation de boot() crée le conteneur,
 * enregistre les services Core et branche les hooks WordPress fondateurs.
 * Les invocations suivantes sont idempotentes.
 *
 * @package OliTheme
 *
 * @since 1.0.0
 */
final class Theme
{
    /**
     * Clé d'option WP qui mémorise l'erreur cache éventuelle pour admin_notice.
     */
    public const CACHE_ERROR_OPTION = 'oli_theme_cache_error';

    private static ?Container $container = null;

    /**
     * Démarre le thème. Appelé depuis functions.php.
     *
     * @param string $themePath Chemin absolu du thème (généralement __DIR__).
     */
    public static function boot(string $themePath): void
    {
        if (self::$container !== null) {
            return;
        }

        self::$container = self::buildContainer($themePath);
        self::registerCoreHooks(self::$container);
    }

    /**
     * Retourne le conteneur applicatif (à appeler après boot()).
     *
     * @throws \LogicException Si boot() n'a pas encore été appelé.
     */
    public static function container(): Container
    {
        if (self::$container === null) {
            throw new \LogicException('Theme::boot() doit être appelé avant Theme::container().');
        }

        return self::$container;
    }

    /**
     * Réinitialise l'état statique. Réservé aux tests.
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$container = null;
    }

    /**
     * Hook 'after_switch_theme' : initialisation à l'activation du thème.
     *
     * Crée (ou met à jour) la table `oli_redirects` via dbDelta.
     */
    public static function onActivation(): void
    {
        flush_rewrite_rules();

        // Pré-création du cache compilé : on échoue silencieusement avec un
        // admin_notice plutôt qu'avec un fatal au premier rendu.
        $ensurer   = new CacheDirectoryEnsurer();
        $cachePath = self::resolveThemePath() . '/.cache/templates';
        if (!$ensurer->ensure($cachePath)) {
            self::recordCacheError((string) $ensurer->getError());
        } else {
            delete_option(self::CACHE_ERROR_OPTION);
        }

        global $wpdb;

        /** @phpstan-var \wpdb $wpdb */
        $installer = new \OliTheme\Seo\RedirectInstaller($wpdb);
        $installer->install();
        update_option(\OliTheme\Seo\RedirectInstaller::OPTION_KEY, \OliTheme\Seo\RedirectInstaller::DB_VERSION);
    }

    /**
     * Affiche un admin_notice si une erreur cache a été enregistrée.
     *
     * Branché sur 'admin_notices'. Public pour permettre aux tests de l'invoquer.
     */
    public static function renderCacheAdminNotice(): void
    {
        $error = get_option(self::CACHE_ERROR_OPTION, '');
        if (!\is_string($error) || $error === '') {
            return;
        }

        printf(
            '<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
            esc_html__('oli-theme — cache compilé indisponible :', 'oli-theme'),
            esc_html($error),
        );
    }

    /**
     * Hook 'switch_theme' : nettoyage à la désactivation du thème.
     */
    public static function onDeactivation(): void
    {
        flush_rewrite_rules();
    }

    /**
     * Mémorise l'erreur de cache pour qu'un admin_notice la remonte plus tard.
     */
    private static function recordCacheError(string $message): void
    {
        if (!\function_exists('update_option')) {
            return;
        }
        update_option(self::CACHE_ERROR_OPTION, $message);
    }

    /**
     * Retourne le chemin du thème (best-effort hors WP pour les tests).
     */
    private static function resolveThemePath(): string
    {
        if (\function_exists('get_template_directory')) {
            $path = (string) get_template_directory();
            if ($path !== '') {
                return $path;
            }
        }

        return \dirname(__DIR__);
    }

    /**
     * Construit le conteneur et y enregistre les services Core.
     */
    private static function buildContainer(string $themePath): Container
    {
        $container = new Container();
        $themeUri = get_template_directory_uri();

        $container->set(RequestContext::class, RequestContext::fromGlobals());
        $container->set(HookRegistrar::class, new HookRegistrar());
        $container->set(CacheDirectoryEnsurer::class, new CacheDirectoryEnsurer());
        $container->factory(
            ViewRenderer::class,
            static function (Container $c) use ($themePath): ViewRenderer {
                $tplPath   = $themePath . '/templates';
                $cachePath = $themePath . '/.cache/templates';

                $ensurer = $c->get(CacheDirectoryEnsurer::class);
                \assert($ensurer instanceof CacheDirectoryEnsurer);

                if (!$ensurer->ensure($cachePath)) {
                    self::recordCacheError((string) $ensurer->getError());
                    // Fallback : on essaie le tmpdir système, qui est presque
                    // toujours writable. Le cache sera moins efficace mais le
                    // site répondra (au lieu d'un fatal Lunar).
                    $fallback = sys_get_temp_dir() . '/oli-theme-cache';
                    if ($ensurer->ensure($fallback)) {
                        $cachePath = $fallback;
                    }
                }

                return new ViewRenderer($tplPath, $cachePath);
            },
        );
        $container->factory(
            AssetManager::class,
            static fn (): AssetManager => new AssetManager($themePath, $themeUri),
        );
        $container->set(TemplateRouter::class, new TemplateRouter($themePath . '/theme-bridge'));

        // Module I18n : services et orchestration.
        $container->factory(
            \OliTheme\I18n\LanguageRegistry::class,
            static fn (): \OliTheme\I18n\LanguageRegistry => new \OliTheme\I18n\LanguageRegistry(),
        );
        $container->factory(
            \OliTheme\I18n\LanguageResolver::class,
            static fn (Container $c): \OliTheme\I18n\LanguageResolver => new \OliTheme\I18n\LanguageResolver(
                $c->get(\OliTheme\I18n\LanguageRegistry::class),
                $c->get(RequestContext::class),
            ),
        );
        $container->factory(
            \OliTheme\I18n\TranslationModel::class,
            static fn (): \OliTheme\I18n\TranslationModel => new \OliTheme\I18n\TranslationModel(),
        );
        $container->factory(
            \OliTheme\I18n\LanguageTaxonomy::class,
            static fn (Container $c): \OliTheme\I18n\LanguageTaxonomy => new \OliTheme\I18n\LanguageTaxonomy(
                $c->get(\OliTheme\I18n\LanguageRegistry::class),
            ),
        );
        $container->factory(
            \OliTheme\I18n\RewriteRules::class,
            static fn (Container $c): \OliTheme\I18n\RewriteRules => new \OliTheme\I18n\RewriteRules(
                $c->get(\OliTheme\I18n\LanguageRegistry::class),
            ),
        );
        $container->factory(
            \OliTheme\I18n\LanguageUrlFilter::class,
            static fn (Container $c): \OliTheme\I18n\LanguageUrlFilter => new \OliTheme\I18n\LanguageUrlFilter(
                $c->get(\OliTheme\I18n\LanguageRegistry::class),
                $c->get(\OliTheme\I18n\LanguageResolver::class),
            ),
        );
        $container->factory(
            \OliTheme\I18n\LanguageSwitcherController::class,
            static fn (Container $c): \OliTheme\I18n\LanguageSwitcherController => new \OliTheme\I18n\LanguageSwitcherController(
                $c->get(\OliTheme\I18n\LanguageRegistry::class),
                $c->get(\OliTheme\I18n\LanguageResolver::class),
                $c->get(\OliTheme\I18n\TranslationModel::class),
            ),
        );
        $container->factory(
            \OliTheme\I18n\LanguageMetabox::class,
            static fn (Container $c): \OliTheme\I18n\LanguageMetabox => new \OliTheme\I18n\LanguageMetabox(
                $c->get(\OliTheme\I18n\LanguageRegistry::class),
                $c->get(\OliTheme\I18n\TranslationModel::class),
                $c->get(ViewRenderer::class),
            ),
        );

        // Alias interfaces → implémentations concrètes (requis par PostsModule et futurs modules).
        $container->factory(
            \OliTheme\Core\RendererInterface::class,
            static fn (Container $c): \OliTheme\Core\RendererInterface => $c->get(ViewRenderer::class),
        );
        $container->factory(
            \OliTheme\I18n\LanguageRegistryInterface::class,
            static fn (Container $c): \OliTheme\I18n\LanguageRegistryInterface => $c->get(\OliTheme\I18n\LanguageRegistry::class),
        );
        $container->factory(
            \OliTheme\I18n\LanguageResolverInterface::class,
            static fn (Container $c): \OliTheme\I18n\LanguageResolverInterface => $c->get(\OliTheme\I18n\LanguageResolver::class),
        );
        $container->factory(
            \OliTheme\I18n\LanguageSwitcherControllerInterface::class,
            static fn (Container $c): \OliTheme\I18n\LanguageSwitcherControllerInterface => $c->get(\OliTheme\I18n\LanguageSwitcherController::class),
        );
        $container->factory(
            \OliTheme\I18n\TranslationModelInterface::class,
            static fn (Container $c): \OliTheme\I18n\TranslationModelInterface => $c->get(\OliTheme\I18n\TranslationModel::class),
        );

        return $container;
    }

    /**
     * Branche les hooks WordPress fondateurs (assets, activation/désactivation).
     */
    private static function registerCoreHooks(Container $container): void
    {
        // Variables globales et macros WP injectées dans le moteur de templates.
        self::bootstrapViewRenderer($container);

        // Enqueue hooks enregistrés directement pour satisfaire la signature
        // à 2 arguments attendue dans les tests Brain Monkey.
        add_action('wp_enqueue_scripts', static function () use ($container): void {
            $assets = $container->get(AssetManager::class);
            \assert($assets instanceof AssetManager);
            $assets->enqueueFront();
        });

        add_action('admin_enqueue_scripts', static function () use ($container): void {
            $assets = $container->get(AssetManager::class);
            \assert($assets instanceof AssetManager);
            $assets->enqueueAdmin();
        });

        add_action('after_setup_theme', static function () use ($container): void {
            $assets = $container->get(AssetManager::class);
            \assert($assets instanceof AssetManager);
            $assets->registerThemeSupports();
            $assets->registerEditorStyles();
        });

        add_action('after_switch_theme', [self::class, 'onActivation']);
        add_action('switch_theme', [self::class, 'onDeactivation']);
        add_action('admin_notices', [self::class, 'renderCacheAdminNotice']);

        // Aiguillage `template_include` vers theme-bridge/ (issue #4).
        add_filter(
            'template_include',
            static function (string $original) use ($container): string {
                $router = $container->get(TemplateRouter::class);
                \assert($router instanceof TemplateRouter);

                return $router->resolve($original);
            },
        );

        // Modules fonctionnels.
        (new \OliTheme\Admin\AdminModule($container))->register();
        (new \OliTheme\Settings\SettingsModule($container))->register();
        (new \OliTheme\I18n\I18nModule($container))->register();
        (new \OliTheme\Navigation\NavigationModule($container))->register();
        (new \OliTheme\Slides\SlidesModule($container))->register();
        (new \OliTheme\Seo\SeoModule($container))->register();
        (new \OliTheme\Events\EventsModule($container))->register();
        (new \OliTheme\Contact\ContactModule($container))->register();
        (new \OliTheme\Posts\PostsModule($container))->register();
        (new \OliTheme\Appearance\AppearanceModule($container))->register();
        (new \OliTheme\Gallery\GalleryModule($container))->register();
        (new \OliTheme\Social\SocialModule($container))->register();
        (new \OliTheme\Help\HelpModule($container))->register();
        (new \OliTheme\Calendar\CalendarModule($container))->register();
    }

    /**
     * Injecte les variables globales WP et enregistre les macros wpHead/wpFooter
     * dans le ViewRenderer au démarrage du thème.
     */
    private static function bootstrapViewRenderer(Container $container): void
    {
        $renderer = $container->get(ViewRenderer::class);
        \assert($renderer instanceof ViewRenderer);

        // Variables disponibles dès le boot (fonctions WP synchrones).
        $footerLogoUrl = '';
        $footerText    = '';
        $rawSettings   = function_exists('get_option') ? get_option('oli_theme_settings', []) : [];
        if (is_array($rawSettings) && isset($rawSettings['footer']) && is_array($rawSettings['footer'])) {
            $logoId = isset($rawSettings['footer']['logoId']) ? (int) $rawSettings['footer']['logoId'] : 0;
            if ($logoId > 0 && function_exists('wp_get_attachment_image_url')) {
                $url = wp_get_attachment_image_url($logoId, 'medium');
                if (is_string($url)) {
                    $footerLogoUrl = $url;
                }
            }
            $footerText = (string) ($rawSettings['footer']['text'] ?? '');
        }

        $renderer->setDefaultVariables([
            'siteName'      => get_bloginfo('name'),
            'siteTagline'   => get_bloginfo('description'),
            'siteUrl'       => home_url(),
            'homeUrl'       => home_url(),
            'themeUri'      => get_template_directory_uri(),
            'charset'       => get_bloginfo('charset'),
            'currentYear'   => date('Y'),
            'footerLogoUrl' => $footerLogoUrl,
            'footerText'    => $footerText,
        ]);

        // Macros lazy : wp_head() et wp_footer() sont capturés au moment du rendu
        // via output buffering pour garantir que tous les hooks WP sont déjà branchés.
        $renderer->registerMacro('wpHead', static function (): string {
            ob_start();
            wp_head();

            return (string) ob_get_clean();
        });

        $renderer->registerMacro('wpFooter', static function (): string {
            ob_start();
            wp_footer();

            return (string) ob_get_clean();
        });

        // Classes <body> dynamiques injectées par WP au moment du rendu, ex.
        // `admin-bar` quand l'utilisateur est connecté. Le bodyClasses figé
        // dans le ViewModel ne peut pas les inclure car il est calculé avant
        // que WP ait fini son init côté front. Une macro lazy résout ça.
        $renderer->registerMacro('extraBodyClass', static function (): string {
            $extras = [];
            if (\function_exists('is_admin_bar_showing') && is_admin_bar_showing()) {
                $extras[] = 'admin-bar';
            }

            return implode(' ', $extras);
        });

        // Réseaux sociaux : rendu inline d'un <ul> avec les icônes SVG
        // intégrées en file_get_contents (pour pouvoir les coloriser via
        // currentColor). Macro lazy pour s'exécuter au moment du rendu, pas
        // au boot (avant que les options WP soient lues).
        $themePath = $container->has(\OliTheme\Core\AssetManager::class)
            ? null  // récupéré dynamiquement
            : null;
        $renderer->registerMacro('socialIcons', static function () use ($container): string {
            if (!$container->has(\OliTheme\Social\SocialLinksRepository::class)) {
                return '';
            }
            $repo   = $container->get(\OliTheme\Social\SocialLinksRepository::class);
            $links  = $repo->active();
            if ($links === []) {
                return '';
            }

            $iconsDir = \function_exists('get_template_directory')
                ? rtrim((string) get_template_directory(), '/') . '/assets/img/icons/social'
                : '';

            $html  = '<ul class="social-links" aria-label="Réseaux sociaux">';
            foreach ($links as $l) {
                $svg = '';
                if ($iconsDir !== '') {
                    $path = $iconsDir . '/' . $l['icon'];
                    if (is_file($path)) {
                        $raw = (string) file_get_contents($path);
                        // On retire les attributs fill du SVG pour pouvoir
                        // colorer via currentColor au CSS.
                        $svg = (string) preg_replace('~\sfill="[^"]*"~', '', $raw);
                    }
                }
                $idAttr = htmlspecialchars($l['id'], \ENT_QUOTES, 'UTF-8');
                $html .= '<li class="social-links__item">';
                $html .= '<a class="social-links__link social-links__link--' . $idAttr . '"';
                $html .= ' href="' . htmlspecialchars($l['url'], \ENT_QUOTES, 'UTF-8') . '"';
                $html .= ' target="_blank" rel="noopener noreferrer"';
                $html .= ' aria-label="' . htmlspecialchars($l['label'], \ENT_QUOTES, 'UTF-8') . '"';
                $html .= ' title="' . htmlspecialchars($l['label'], \ENT_QUOTES, 'UTF-8') . '">';
                $html .= $svg;
                $html .= '</a>';
                $html .= '</li>';
            }
            $html .= '</ul>';
            return $html;
        });
    }
}
