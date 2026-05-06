<?php

declare(strict_types=1);

namespace OliTheme\Gallery;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;

/**
 * Module Galerie : page admin photos + vidéos YouTube.
 *
 * @package OliTheme\Gallery
 *
 * @since 1.0.0
 */
final class GalleryModule implements ModuleInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        $c = $this->container;

        if (!$c->has(GalleryRepository::class)) {
            $c->factory(
                GalleryRepository::class,
                static fn (): GalleryRepository => new GalleryRepository(),
            );
        }

        if (!$c->has(GalleryAdminPage::class)) {
            $c->factory(
                GalleryAdminPage::class,
                static fn (Container $c): GalleryAdminPage => new GalleryAdminPage(
                    $c->get(GalleryRepository::class),
                ),
            );
        }

        add_action('admin_menu', function (): void {
            $this->container->get(GalleryAdminPage::class)->register();
        });
    }
}
