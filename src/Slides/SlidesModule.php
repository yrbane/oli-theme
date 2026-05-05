<?php

declare(strict_types=1);

namespace OliTheme\Slides;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\LanguageResolverInterface;

/**
 * Module Slides : enregistre le CPT oli_slide et les services associés
 * (modèle, contrôleur) dans le container.
 *
 * @package OliTheme\Slides
 *
 * @since 1.0.0
 */
final class SlidesModule implements ModuleInterface
{
    /**
     * @param Container $container Container de services du thème.
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Enregistre les services Slides et branche le CPT sur le hook 'init'.
     */
    public function register(): void
    {
        $container = $this->container;

        if (! $container->has(SlideCpt::class)) {
            $container->factory(
                SlideCpt::class,
                static fn (): SlideCpt => new SlideCpt(),
            );
        }

        if (! $container->has(SlideModel::class)) {
            $container->factory(
                SlideModel::class,
                static fn (Container $c): SlideModel => new SlideModel(
                    $c->get(LanguageRegistryInterface::class),
                ),
            );
        }

        if (! $container->has(SlideModelInterface::class)) {
            $container->factory(
                SlideModelInterface::class,
                static fn (Container $c): SlideModelInterface => $c->get(SlideModel::class),
            );
        }

        if (! $container->has(HomeCarouselController::class)) {
            $container->factory(
                HomeCarouselController::class,
                static fn (Container $c): HomeCarouselController => new HomeCarouselController(
                    $c->get(SlideModelInterface::class),
                    $c->get(LanguageResolverInterface::class),
                ),
            );
        }

        if (! $container->has(HomeCarouselControllerInterface::class)) {
            $container->factory(
                HomeCarouselControllerInterface::class,
                static fn (Container $c): HomeCarouselControllerInterface => $c->get(HomeCarouselController::class),
            );
        }

        add_action('init', function (): void {
            $this->container->get(SlideCpt::class)->register();
        });
    }
}
