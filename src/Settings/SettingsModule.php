<?php

declare(strict_types=1);

namespace OliTheme\Settings;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;

/**
 * Module Settings : enregistre les services de paramétrage du thème
 * et branche les hooks d'administration WordPress.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final class SettingsModule implements ModuleInterface
{
    /**
     * @param Container $container Container de services du thème.
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Enregistre les services Settings et branche les hooks admin_menu / admin_init.
     */
    public function register(): void
    {
        if (! $this->container->has(ThemeSettingsModel::class)) {
            $this->container->factory(
                ThemeSettingsModel::class,
                static fn (): ThemeSettingsModel => new ThemeSettingsModel(),
            );
            $this->container->factory(
                ThemeSettingsModelInterface::class,
                static fn (Container $c): ThemeSettingsModelInterface => $c->get(ThemeSettingsModel::class),
            );
        }

        if (! $this->container->has(ThemeSettingsPage::class)) {
            $this->container->factory(
                ThemeSettingsPage::class,
                static fn (Container $c): ThemeSettingsPage => new ThemeSettingsPage(
                    $c->get(ThemeSettingsModelInterface::class),
                ),
            );
        }

        add_action('admin_menu', function (): void {
            $page = $this->container->get(ThemeSettingsPage::class);
            \assert($page instanceof ThemeSettingsPage);

            $registry = $this->container->get(\OliTheme\Admin\AdminTabRegistry::class);
            \assert($registry instanceof \OliTheme\Admin\AdminTabRegistry);

            $registry->add(new SettingsTab($page, 'banner', 'identite', 'banner', __('Identité visuelle', 'oli-theme')));
            $registry->add(new SettingsTab($page, 'languages', 'identite', 'languages', __('Langues', 'oli-theme')));
            $registry->add(new SettingsTab($page, 'footer', 'identite', 'footer', __('Pied de page', 'oli-theme')));
            $registry->add(new SettingsTab($page, 'contact', 'contact', 'contact', __('Contact', 'oli-theme')));
            $registry->add(new SettingsTab($page, 'seo', 'seo', 'reglages', __('Réglages SEO', 'oli-theme')));
        }, 10);

        add_action('admin_init', function (): void {
            $page = $this->container->get(ThemeSettingsPage::class);
            \assert($page instanceof ThemeSettingsPage);
            $page->registerSettings();
        });
    }
}
