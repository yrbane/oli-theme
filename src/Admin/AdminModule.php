<?php

declare(strict_types=1);

namespace OliTheme\Admin;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;

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
final class AdminModule implements ModuleInterface
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

        // Priorité 20 : après les modules qui publient leurs onglets (priorité 10).
        add_action('admin_menu', function (): void {
            $page = $this->container->get(ThemeAdminPage::class);
            \assert($page instanceof ThemeAdminPage);
            $page->register();
        }, 20);

        // Les anciens slugs d'admin ne sont plus enregistrés : WordPress lève un
        // 403 dans menu.php (avant admin_init) via admin_page_access_denied. On
        // intercepte ce hook pour rediriger 301 vers le nouvel onglet équivalent.
        add_action('admin_page_access_denied', function (): void {
            if (!isset($_GET['page']) || !\is_string($_GET['page'])) {
                return;
            }
            $slug  = sanitize_key((string) $_GET['page']);
            $extra = array_map(
                static fn ($v): string => \is_scalar($v) ? (string) $v : '',
                $_GET,
            );
            $target = (new LegacySlugRedirector())->targetFor($slug, $extra);
            if ($target !== null) {
                wp_safe_redirect($target, 301);
                exit;
            }
        });
    }
}
