<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\I18n\LanguageRegistryInterface;

/**
 * Module Navigation : enregistre les services et hooke les nav-menu locations.
 *
 * Enregistre dans le Container les factories pour MenuModel, MenuLocations
 * et MenuController (sous clés concrètes et interfaces). Accroche
 * `after_setup_theme` pour appeler `MenuLocations::register()`.
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
final class NavigationModule implements ModuleInterface
{
    /**
     * @param Container $container Conteneur de services de l'application
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Enregistre les services de navigation dans le container et pose le hook WP.
     */
    public function register(): void
    {
        $container = $this->container;

        if (! $container->has(MenuModel::class)) {
            $container->factory(
                MenuModel::class,
                static fn (): MenuModel => new MenuModel(),
            );
            $container->factory(
                MenuModelInterface::class,
                static fn (Container $c): MenuModelInterface => $c->get(MenuModel::class),
            );
        }

        if (! $container->has(MenuLocations::class)) {
            $container->factory(
                MenuLocations::class,
                static fn (Container $c): MenuLocations => new MenuLocations(
                    $c->get(LanguageRegistryInterface::class),
                ),
            );
        }

        if (! $container->has(MenuController::class)) {
            $container->factory(
                MenuController::class,
                static fn (Container $c): MenuController => new MenuController(
                    $c->get(MenuLocations::class),
                    $c->get(MenuModelInterface::class),
                ),
            );
            $container->factory(
                MenuControllerInterface::class,
                static fn (Container $c): MenuControllerInterface => $c->get(MenuController::class),
            );
        }

        add_action('after_setup_theme', function (): void {
            $locations = $this->container->get(MenuLocations::class);
            \assert($locations instanceof MenuLocations);
            $locations->register();
        });
    }
}
