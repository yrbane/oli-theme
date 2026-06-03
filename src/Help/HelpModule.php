<?php

declare(strict_types=1);

namespace OliTheme\Help;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;

/**
 * Module Aide : auto-documentation in-admin.
 *
 * Enregistre dans le conteneur le registre, le renderer et la page d'admin,
 * puis ajoute l'onglet « Aide » au registre des onglets.
 *
 * @package OliTheme\Help
 *
 * @since 1.2.0
 */
final class HelpModule implements ModuleInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        $c = $this->container;

        if (!$c->has(HelpRegistry::class)) {
            $c->factory(HelpRegistry::class, static fn (): HelpRegistry => new HelpRegistry());
        }

        if (!$c->has(MarkdownRenderer::class)) {
            $c->factory(MarkdownRenderer::class, static fn (): MarkdownRenderer => new MarkdownRenderer());
        }

        if (!$c->has(HelpAdminPage::class)) {
            $c->factory(
                HelpAdminPage::class,
                static fn (Container $c): HelpAdminPage => new HelpAdminPage(
                    $c->get(HelpRegistry::class),
                    $c->get(MarkdownRenderer::class),
                    \function_exists('get_template_directory') ? (string) get_template_directory() : '',
                ),
            );
        }

        add_action('admin_menu', function () use ($c): void {
            $registry = $c->get(\OliTheme\Admin\AdminTabRegistry::class);
            \assert($registry instanceof \OliTheme\Admin\AdminTabRegistry);
            $page = $c->get(HelpAdminPage::class);
            \assert($page instanceof HelpAdminPage);
            $registry->add($page);
        }, 10);
    }
}
