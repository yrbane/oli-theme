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
     */
    public static function onActivation(): void
    {
        flush_rewrite_rules();
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

        return $container;
    }

    /**
     * Branche les hooks WordPress fondateurs (assets, activation/désactivation).
     */
    private static function registerCoreHooks(Container $container): void
    {
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
    }
}
