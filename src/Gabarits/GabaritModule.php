<?php

declare(strict_types=1);

namespace OliTheme\Gabarits;

use OliTheme\Admin\AdminTabRegistry;
use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\Gabarits\Admin\GabaritAdminPage;
use OliTheme\Gabarits\Admin\GabaritMetabox;

/**
 * Module Gabarits — styles de présentation interchangeables pour les
 * posts / pages / events.
 *
 * Branche :
 *  - le scanner du dossier `assets/gabarits/` (DI).
 *  - la metabox sur l'éditeur (admin).
 *  - le sous-onglet « Gabarits » de l'onglet Apparence.
 *  - le filtre `body_class` qui ajoute `gabarit-{id}`.
 *  - l'enqueue automatique du CSS (et JS optionnel) du gabarit actif.
 *
 * @package OliTheme\Gabarits
 *
 * @since 1.4.0
 */
final class GabaritModule implements ModuleInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        $c = $this->container;

        if (!$c->has(GabaritRegistry::class)) {
            $c->factory(GabaritRegistry::class, static function (): GabaritRegistry {
                $themeDir = \function_exists('get_template_directory')      ? (string) get_template_directory()     : __DIR__ . '/../..';
                $themeUri = \function_exists('get_template_directory_uri')  ? (string) get_template_directory_uri() : '';
                return new GabaritRegistry($themeDir . '/assets/gabarits', $themeUri . '/assets/gabarits');
            });
        }
        if (!$c->has(GabaritRegistryInterface::class)) {
            $c->factory(
                GabaritRegistryInterface::class,
                static fn (Container $cc): GabaritRegistryInterface => $cc->get(GabaritRegistry::class),
            );
        }
        if (!$c->has(GabaritResolver::class)) {
            $c->factory(
                GabaritResolver::class,
                static fn (Container $cc): GabaritResolver => new GabaritResolver($cc->get(GabaritRegistryInterface::class)),
            );
        }
        if (!$c->has(ZoneContentRepository::class)) {
            $c->factory(ZoneContentRepository::class, static fn (): ZoneContentRepository => new ZoneContentRepository());
        }
        if (!$c->has(GabaritRenderer::class)) {
            $c->factory(GabaritRenderer::class, static fn (): GabaritRenderer => new GabaritRenderer());
        }
        if (!$c->has(GabaritMetabox::class)) {
            $c->factory(
                GabaritMetabox::class,
                static fn (Container $cc): GabaritMetabox => new GabaritMetabox(
                    $cc->get(GabaritRegistryInterface::class),
                    $cc->get(ZoneContentRepository::class),
                ),
            );
        }
        if (!$c->has(GabaritAdminPage::class)) {
            $c->factory(
                GabaritAdminPage::class,
                static fn (Container $cc): GabaritAdminPage => new GabaritAdminPage($cc->get(GabaritRegistryInterface::class)),
            );
        }

        // Metabox éditeur.
        add_action('init', static function () use ($c): void {
            $c->get(GabaritMetabox::class)->register();
        });

        // Sous-onglet admin Apparence > Gabarits.
        add_action('admin_menu', static function () use ($c): void {
            $registry = $c->get(AdminTabRegistry::class);
            \assert($registry instanceof AdminTabRegistry);
            $registry->add($c->get(GabaritAdminPage::class));
        });

        // body_class : ajoute gabarit-{id} sur les singular si le post a un gabarit.
        add_filter('body_class', static function (array $classes) use ($c): array {
            if (!\function_exists('is_singular') || !is_singular()) {
                return $classes;
            }
            $postId = \function_exists('get_queried_object_id') ? (int) get_queried_object_id() : 0;
            if ($postId <= 0) {
                return $classes;
            }
            $resolver = $c->get(GabaritResolver::class);
            \assert($resolver instanceof GabaritResolver);
            $gabarit = $resolver->forPost($postId);
            if ($gabarit !== null) {
                $classes[] = 'gabarit-' . $gabarit->id;
            }
            return $classes;
        });

        // Enqueue CSS + JS du gabarit actif.
        add_action('wp_enqueue_scripts', static function () use ($c): void {
            if (!\function_exists('is_singular') || !is_singular()) {
                return;
            }
            $postId = \function_exists('get_queried_object_id') ? (int) get_queried_object_id() : 0;
            if ($postId <= 0) {
                return;
            }
            $resolver = $c->get(GabaritResolver::class);
            \assert($resolver instanceof GabaritResolver);
            $gabarit = $resolver->forPost($postId);
            if ($gabarit === null) {
                return;
            }
            $handle = 'oli-gabarit-' . $gabarit->id;
            wp_enqueue_style($handle, $gabarit->cssPath, ['oli-theme'], '1.4.0');
            if ($gabarit->jsPath !== null) {
                wp_enqueue_script_module($handle, $gabarit->jsPath, [], '1.4.0');
            }
        }, 20);
    }
}
