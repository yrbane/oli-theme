<?php

declare(strict_types=1);

namespace OliTheme\Appearance;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;

/**
 * Module Appearance : variations CSS du thème.
 *
 * Découvre les fichiers `assets/css/variations/*.css`, expose un sélecteur
 * dans Apparence > Variations CSS, et enqueue la variation choisie après
 * `main.css` pour l'overrider.
 *
 * @package OliTheme\Appearance
 *
 * @since 1.0.0
 */
final class AppearanceModule implements ModuleInterface
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function register(): void
    {
        $themePath = '';
        if (\function_exists('get_template_directory')) {
            // get_template_directory peut être stubé via Brain Monkey à la
            // racine du projet ; sinon on retombe silencieusement sur ''.
            try {
                $themePath = (string) get_template_directory();
            } catch (\Throwable) {
                $themePath = '';
            }
        }

        if (! $this->container->has(ThemeVariationRegistry::class)) {
            $this->container->factory(
                ThemeVariationRegistry::class,
                static fn (): ThemeVariationRegistry => new ThemeVariationRegistry(
                    $themePath . '/assets/css/variations',
                ),
            );
        }

        if (! $this->container->has(ThemeVariationPage::class)) {
            $this->container->factory(
                ThemeVariationPage::class,
                static fn (Container $c): ThemeVariationPage => new ThemeVariationPage(
                    $c->get(ThemeVariationRegistry::class),
                ),
            );
        }

        add_action('admin_menu', function (): void {
            $this->container->get(ThemeVariationPage::class)->register();
        });

        add_action('admin_init', function (): void {
            $this->container->get(ThemeVariationPage::class)->registerSettings();
        });
    }
}
