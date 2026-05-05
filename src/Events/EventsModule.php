<?php

declare(strict_types=1);

namespace OliTheme\Events;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;
use OliTheme\Navigation\MenuControllerInterface;

/**
 * Module Events : enregistre le CPT oli_event et les services associés
 * (modèle, contrôleurs, metabox) dans le container.
 *
 * @package OliTheme\Events
 *
 * @since 1.0.0
 */
final class EventsModule implements ModuleInterface
{
    /**
     * @param Container $container Container de services du thème.
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Enregistre les services Events et branche les hooks WordPress.
     */
    public function register(): void
    {
        $container = $this->container;

        if (! $container->has(EventCpt::class)) {
            $container->factory(
                EventCpt::class,
                static fn (): EventCpt => new EventCpt(),
            );
        }

        if (! $container->has(EventModel::class)) {
            $container->factory(
                EventModel::class,
                static fn (Container $c): EventModel => new EventModel(
                    $c->get(LanguageRegistryInterface::class),
                ),
            );
        }

        if (! $container->has(EventModelInterface::class)) {
            $container->factory(
                EventModelInterface::class,
                static fn (Container $c): EventModelInterface => $c->get(EventModel::class),
            );
        }

        if (! $container->has(EventController::class)) {
            $container->factory(
                EventController::class,
                static fn (Container $c): EventController => new EventController(
                    $c->get(EventModelInterface::class),
                    $c->get(LanguageResolverInterface::class),
                    $c->get(LanguageSwitcherControllerInterface::class),
                    $c->get(MenuControllerInterface::class),
                    $c->get(RendererInterface::class),
                ),
            );
        }

        if (! $container->has(EventControllerInterface::class)) {
            $container->factory(
                EventControllerInterface::class,
                static fn (Container $c): EventControllerInterface => $c->get(EventController::class),
            );
        }

        if (! $container->has(EventArchiveController::class)) {
            $container->factory(
                EventArchiveController::class,
                static fn (Container $c): EventArchiveController => new EventArchiveController(
                    $c->get(EventModelInterface::class),
                    $c->get(LanguageResolverInterface::class),
                    $c->get(LanguageSwitcherControllerInterface::class),
                    $c->get(MenuControllerInterface::class),
                    $c->get(RendererInterface::class),
                ),
            );
        }

        if (! $container->has(EventArchiveControllerInterface::class)) {
            $container->factory(
                EventArchiveControllerInterface::class,
                static fn (Container $c): EventArchiveControllerInterface => $c->get(EventArchiveController::class),
            );
        }

        if (! $container->has(EventMetabox::class)) {
            $container->factory(
                EventMetabox::class,
                static fn (Container $c): EventMetabox => new EventMetabox(
                    $c->get(RendererInterface::class),
                ),
            );
        }

        add_action('init', function (): void {
            $this->container->get(EventCpt::class)->register();
        });

        add_action('add_meta_boxes', function (): void {
            $this->container->get(EventMetabox::class)->register();
        });

        add_action('save_post_oli_event', function ($id): void {
            $this->container->get(EventMetabox::class)->save((int) $id, $_POST);
        });
    }
}
