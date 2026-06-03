<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;

/**
 * Module Dossiers médiathèque — organise les attachments en arborescence.
 *
 * @package OliTheme\MediaFolders
 *
 * @since 1.5.0
 */
final class MediaFoldersModule implements ModuleInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        $c = $this->container;

        if (!$c->has(MediaFoldersTaxonomy::class)) {
            $c->factory(MediaFoldersTaxonomy::class, static fn (): MediaFoldersTaxonomy => new MediaFoldersTaxonomy());
        }
        if (!$c->has(MediaFoldersAdmin::class)) {
            $c->factory(MediaFoldersAdmin::class, static fn (): MediaFoldersAdmin => new MediaFoldersAdmin());
        }

        // Enregistrement de la taxonomie sur init (avant le rendu admin).
        add_action('init', static function () use ($c): void {
            $c->get(MediaFoldersTaxonomy::class)->register();
        }, 0);

        // UI admin (filtre dropdown + query) — uniquement côté admin.
        if (\function_exists('is_admin') && is_admin()) {
            $c->get(MediaFoldersAdmin::class)->register();
        }
    }
}
