<?php

declare(strict_types=1);

namespace OliTheme\Admin;

use OliTheme\Container;

/**
 * Module d'administration unifiée : enregistre la page hôte unique et le
 * registre d'onglets partagé entre modules.
 *
 * Doit être enregistré AVANT les modules qui publient des onglets, afin que le
 * registre existe dans le conteneur au moment où ils s'y abonnent.
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
final class AdminModule
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        if (! $this->container->has(AdminTabRegistry::class)) {
            $this->container->factory(
                AdminTabRegistry::class,
                static fn (): AdminTabRegistry => new AdminTabRegistry(),
            );
        }

        if (! $this->container->has(ThemeAdminPage::class)) {
            $this->container->factory(
                ThemeAdminPage::class,
                static fn (Container $c): ThemeAdminPage => new ThemeAdminPage(
                    $c->get(AdminTabRegistry::class),
                ),
            );
        }

        add_action('admin_menu', function (): void {
            $page = $this->container->get(ThemeAdminPage::class);
            \assert($page instanceof ThemeAdminPage);
            $page->register();
        }, 20);
    }
}
