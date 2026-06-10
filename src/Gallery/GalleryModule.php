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

        if (!$c->has(YoutubeChannelFetcher::class)) {
            $c->factory(
                YoutubeChannelFetcher::class,
                static fn (): YoutubeChannelFetcher => new YoutubeChannelFetcher(),
            );
        }

        if (!$c->has(GalleryRepository::class)) {
            $c->factory(
                GalleryRepository::class,
                static fn (Container $c): GalleryRepository => new GalleryRepository(
                    $c->get(YoutubeChannelFetcher::class),
                ),
            );
        }

        if (!$c->has(GalleryPagesInstaller::class)) {
            $c->factory(
                GalleryPagesInstaller::class,
                static fn (Container $c): GalleryPagesInstaller => new GalleryPagesInstaller(
                    $c->get(\OliTheme\I18n\LanguageRegistryInterface::class),
                    $c->get(\OliTheme\I18n\TranslationModelInterface::class),
                ),
            );
        }

        if (!$c->has(GalleryAdminPage::class)) {
            $c->factory(
                GalleryAdminPage::class,
                static fn (Container $c): GalleryAdminPage => new GalleryAdminPage(
                    $c->get(GalleryRepository::class),
                    $c->get(GalleryPagesInstaller::class),
                ),
            );
        }

        add_action('admin_menu', function () use ($c): void {
            $registry = $c->get(\OliTheme\Admin\AdminTabRegistry::class);
            \assert($registry instanceof \OliTheme\Admin\AdminTabRegistry);
            $page = $c->get(GalleryAdminPage::class);
            \assert($page instanceof GalleryAdminPage);
            $registry->add($page);
        }, 10);

        // Shortcode + bloc `[oli_video id="..."]` pour insérer une vidéo
        // YouTube dans n'importe quelle page/post.
        if (!$c->has(\OliTheme\Gallery\Frontend\VideoShortcode::class)) {
            $c->factory(
                \OliTheme\Gallery\Frontend\VideoShortcode::class,
                static fn (Container $cc): \OliTheme\Gallery\Frontend\VideoShortcode
                    => new \OliTheme\Gallery\Frontend\VideoShortcode($cc->get(GalleryRepository::class)),
            );
        }
        add_action('init', static function () use ($c): void {
            $c->get(\OliTheme\Gallery\Frontend\VideoShortcode::class)->register();
        });

        // CSS du shortcode/bloc vidéo en frontend (wrapper responsive).
        add_action('wp_enqueue_scripts', static function (): void {
            $themeUri = \function_exists('get_template_directory_uri') ? (string) get_template_directory_uri() : '';
            wp_enqueue_style('oli-video', $themeUri . '/assets/css/video.css', [], '1.6.0');
        });
    }
}
