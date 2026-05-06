<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;
use OliTheme\Seo\BreadcrumbsControllerInterface;
use OliTheme\Seo\SeoControllerInterface;

/**
 * Module Posts : enregistre le modèle générique et les controllers
 * page/post/404 dans le container, sans s'accrocher directement aux hooks
 * WordPress (les théma-bridges du dossier theme-bridge/ y répondent).
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class PostsModule implements ModuleInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        $container = $this->container;

        if (! $container->has(PostModel::class)) {
            $container->factory(
                PostModel::class,
                static fn (Container $c): PostModel => new PostModel(
                    $c->get(LanguageResolverInterface::class),
                    $c->get(LanguageRegistryInterface::class),
                ),
            );
        }

        if (! $container->has(PostModelInterface::class)) {
            $container->factory(
                PostModelInterface::class,
                static fn (Container $c): PostModelInterface => $c->get(PostModel::class),
            );
        }

        if (! $container->has(PageController::class)) {
            $container->factory(
                PageController::class,
                static fn (Container $c): PageController => new PageController(
                    $c->get(PostModel::class),
                    $c->get(LanguageResolverInterface::class),
                    $c->get(LanguageSwitcherControllerInterface::class),
                    $c->get(\OliTheme\Navigation\MenuControllerInterface::class),
                    $c->get(\OliTheme\Slides\HomeCarouselControllerInterface::class),
                    $c->get(SeoControllerInterface::class),
                    $c->get(BreadcrumbsControllerInterface::class),
                    $c->get(RendererInterface::class),
                    new CoverExtractor(),
                    $c->has(\OliTheme\Gallery\GalleryRepository::class)
                        ? $c->get(\OliTheme\Gallery\GalleryRepository::class)
                        : null,
                ),
            );
        }

        if (! $container->has(PostController::class)) {
            $container->factory(
                PostController::class,
                static fn (Container $c): PostController => new PostController(
                    $c->get(PostModel::class),
                    $c->get(LanguageResolverInterface::class),
                    $c->get(LanguageSwitcherControllerInterface::class),
                    $c->get(\OliTheme\Navigation\MenuControllerInterface::class),
                    $c->get(SeoControllerInterface::class),
                    $c->get(BreadcrumbsControllerInterface::class),
                    $c->get(RendererInterface::class),
                ),
            );
        }

        if (! $container->has(NotFoundController::class)) {
            $container->factory(
                NotFoundController::class,
                static fn (Container $c): NotFoundController => new NotFoundController(
                    $c->get(LanguageResolverInterface::class),
                    $c->get(LanguageSwitcherControllerInterface::class),
                    $c->get(\OliTheme\Navigation\MenuControllerInterface::class),
                    $c->get(SeoControllerInterface::class),
                    $c->get(BreadcrumbsControllerInterface::class),
                    $c->get(RendererInterface::class),
                ),
            );
        }
    }
}
