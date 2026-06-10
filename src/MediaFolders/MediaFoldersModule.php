<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\MediaFolders\Frontend\FolderGalleryShortcode;

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
        if (!$c->has(MediaFoldersBulkActions::class)) {
            $c->factory(MediaFoldersBulkActions::class, static fn (): MediaFoldersBulkActions => new MediaFoldersBulkActions());
        }
        if (!$c->has(DefaultUploadFolder::class)) {
            $c->factory(DefaultUploadFolder::class, static fn (): DefaultUploadFolder => new DefaultUploadFolder());
        }
        if (!$c->has(MediaFolderQuery::class)) {
            $c->factory(MediaFolderQuery::class, static fn (): MediaFolderQuery => new MediaFolderQuery());
        }
        if (!$c->has(MediaFoldersReorder::class)) {
            $c->factory(
                MediaFoldersReorder::class,
                static fn (Container $cc): MediaFoldersReorder => new MediaFoldersReorder(
                    $cc->get(MediaFolderQuery::class),
                ),
            );
        }
        if (!$c->has(MediaFoldersGallerySettings::class)) {
            $c->factory(
                MediaFoldersGallerySettings::class,
                static fn (Container $cc): MediaFoldersGallerySettings => new MediaFoldersGallerySettings(
                    $cc->get(MediaFolderQuery::class),
                    $cc->get(MediaFoldersReorder::class),
                ),
            );
        }

        // Enregistrement de la taxonomie sur init (avant le rendu admin).
        add_action('init', static function () use ($c): void {
            $c->get(MediaFoldersTaxonomy::class)->register();
        }, 0);

        // UI admin (filtre + bulk actions + dossier d'upload par défaut +
        // réordonnancement + sélection des dossiers exposés en galerie).
        if (\function_exists('is_admin') && is_admin()) {
            $c->get(MediaFoldersAdmin::class)->register();
            $c->get(MediaFoldersBulkActions::class)->register();
            $c->get(DefaultUploadFolder::class)->register();
            $c->get(MediaFoldersReorder::class)->register();
            $c->get(MediaFoldersGallerySettings::class)->register();
        }

        // Services frontend (shortcode + bloc Gutenberg).
        if (!$c->has(FolderGalleryShortcode::class)) {
            $c->factory(
                FolderGalleryShortcode::class,
                static fn (Container $cc): FolderGalleryShortcode => new FolderGalleryShortcode(
                    $cc->get(MediaFolderQuery::class),
                ),
            );
        }
        add_action('init', static function () use ($c): void {
            $c->get(FolderGalleryShortcode::class)->register();
        });

        // CSS du shortcode/bloc en frontend (uniquement quand le contenu en a besoin).
        add_action('wp_enqueue_scripts', static function (): void {
            $themeUri = \function_exists('get_template_directory_uri') ? (string) get_template_directory_uri() : '';
            wp_enqueue_style('oli-folder-gallery', $themeUri . '/assets/css/folder-gallery.css', [], '1.5.0');
        });
    }
}
