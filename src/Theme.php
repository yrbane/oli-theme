<?php

declare(strict_types=1);

namespace OliTheme;

use OliTheme\Core\AssetManager;
use OliTheme\Core\HookRegistrar;
use OliTheme\Core\RequestContext;
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

        global $wpdb;

        /** @phpstan-var \wpdb $wpdb */
        $installer = new \OliTheme\Seo\RedirectInstaller($wpdb);
        $installer->install();
        update_option(\OliTheme\Seo\RedirectInstaller::OPTION_KEY, \OliTheme\Seo\RedirectInstaller::DB_VERSION);
    }

    /**
     * Hook 'switch_theme' : nettoyage à la désactivation du thème.
     */
    public static function onDeactivation(): void
    {
        flush_rewrite_rules();
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
        $container->factory(
            ViewRenderer::class,
            static function () use ($themePath): ViewRenderer {
                // Crée les répertoires requis s'ils n'existent pas encore.
                $tplPath   = $themePath . '/templates';
                $cachePath = $themePath . '/.cache/templates';
                if (!is_dir($tplPath)) {
                    mkdir($tplPath, 0o755, true);
                }
                if (!is_dir($cachePath)) {
                    mkdir($cachePath, 0o755, true);
                }

                return new ViewRenderer($tplPath, $cachePath);
            },
        );
        $container->factory(
            AssetManager::class,
            static fn (): AssetManager => new AssetManager($themePath, $themeUri),
        );

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

        add_action('after_switch_theme', [self::class, 'onActivation']);
        add_action('switch_theme', [self::class, 'onDeactivation']);

        // Modules fonctionnels.
        (new \OliTheme\I18n\I18nModule($container))->register();
        (new \OliTheme\Navigation\NavigationModule($container))->register();
        (new \OliTheme\Slides\SlidesModule($container))->register();
        (new \OliTheme\Seo\SeoModule($container))->register();
        (new \OliTheme\Events\EventsModule($container))->register();
        (new \OliTheme\Posts\PostsModule($container))->register();
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
        $renderer->setDefaultVariables([
            'siteName'    => get_bloginfo('name'),
            'siteUrl'     => home_url(),
            'homeUrl'     => home_url(),
            'themeUri'    => get_template_directory_uri(),
            'charset'     => get_bloginfo('charset'),
            'currentYear' => date('Y'),
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
    }
}
