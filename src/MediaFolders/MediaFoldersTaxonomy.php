<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders;

/**
 * Enregistre la taxonomie hiérarchique `oli_media_folder` sur `attachment`.
 *
 * Permet d'organiser la médiathèque WordPress en **dossiers et sous-dossiers**
 * (chaque folder = un terme de la taxonomie, avec une notion de parent).
 *
 * @package OliTheme\MediaFolders
 *
 * @since 1.5.0
 */
final class MediaFoldersTaxonomy
{
    public const TAXONOMY = 'oli_media_folder';

    public function register(): void
    {
        if (!\function_exists('register_taxonomy')) {
            return;
        }
        register_taxonomy(self::TAXONOMY, ['attachment'], [
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => 'upload.php',
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'show_in_quick_edit' => true,
            'meta_box_cb'       => 'post_categories_meta_box',
            'rewrite'           => false,
            'labels'            => [
                'name'              => __('Dossiers', 'oli-theme'),
                'singular_name'     => __('Dossier', 'oli-theme'),
                'menu_name'         => __('Dossiers', 'oli-theme'),
                'all_items'         => __('Tous les dossiers', 'oli-theme'),
                'parent_item'       => __('Dossier parent', 'oli-theme'),
                'parent_item_colon' => __('Dossier parent :', 'oli-theme'),
                'edit_item'         => __('Modifier le dossier', 'oli-theme'),
                'update_item'       => __('Mettre à jour le dossier', 'oli-theme'),
                'add_new_item'      => __('Ajouter un dossier', 'oli-theme'),
                'new_item_name'     => __('Nom du nouveau dossier', 'oli-theme'),
                'search_items'      => __('Rechercher un dossier', 'oli-theme'),
                'not_found'         => __('Aucun dossier trouvé.', 'oli-theme'),
            ],
        ]);
    }
}
