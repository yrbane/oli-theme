<?php

declare(strict_types=1);

namespace OliTheme\Social;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;

/**
 * Module Réseaux sociaux : DI + page admin + variable Lunar globale.
 *
 * @package OliTheme\Social
 *
 * @since 1.0.0
 */
final class SocialModule implements ModuleInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        $c = $this->container;

        if (!$c->has(SocialLinksRepository::class)) {
            $c->factory(
                SocialLinksRepository::class,
                static fn (): SocialLinksRepository => new SocialLinksRepository(),
            );
        }

        if (!$c->has(SocialAdminPage::class)) {
            $c->factory(
                SocialAdminPage::class,
                static fn (Container $c): SocialAdminPage => new SocialAdminPage(
                    $c->get(SocialLinksRepository::class),
                ),
            );
        }

        add_action('admin_menu', function (): void {
            $this->container->get(SocialAdminPage::class)->register();
        });
    }
}
