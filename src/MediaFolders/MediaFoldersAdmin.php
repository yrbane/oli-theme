<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders;

/**
 * UI admin de la médiathèque : filtre par dossier + colonne de liste.
 *
 * @package OliTheme\MediaFolders
 *
 * @since 1.5.0
 */
final class MediaFoldersAdmin
{
    public function register(): void
    {
        // Filtre dropdown au-dessus de la liste des médias (upload.php).
        add_action('restrict_manage_posts', [$this, 'renderFilterDropdown']);
        // Filtre la requête principale selon le dossier sélectionné.
        add_filter('parse_query', [$this, 'filterQueryByFolder']);
        // Ajoute le filtre par dossier dans la modale wp.media (sélecteur).
        add_filter('ajax_query_attachments_args', [$this, 'filterAjaxAttachments']);
    }

    /**
     * Affiche un select de dossiers en haut de upload.php (vue Liste).
     */
    public function renderFilterDropdown(string $postType = ''): void
    {
        $screen = \function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || $screen->base !== 'upload') {
            return;
        }
        if (!taxonomy_exists(MediaFoldersTaxonomy::TAXONOMY)) {
            return;
        }
        $current = isset($_GET[MediaFoldersTaxonomy::TAXONOMY]) ? (string) $_GET[MediaFoldersTaxonomy::TAXONOMY] : '';
        wp_dropdown_categories([
            'taxonomy'         => MediaFoldersTaxonomy::TAXONOMY,
            'name'             => MediaFoldersTaxonomy::TAXONOMY,
            'show_option_all'  => __('Tous les dossiers', 'oli-theme'),
            'value_field'      => 'slug',
            'selected'         => $current,
            'hide_empty'       => false,
            'hierarchical'     => true,
            'depth'            => 5,
            'orderby'          => 'name',
        ]);
    }

    /**
     * Quand l'admin filtre par dossier dans upload.php, restreint la query.
     */
    public function filterQueryByFolder(\WP_Query $query): void
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        $screen = \function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || $screen->base !== 'upload') {
            return;
        }
        $slug = isset($_GET[MediaFoldersTaxonomy::TAXONOMY]) ? sanitize_key((string) $_GET[MediaFoldersTaxonomy::TAXONOMY]) : '';
        if ($slug === '' || $slug === '0') {
            return;
        }
        $existingTax = (array) ($query->query_vars['tax_query'] ?? []);
        $existingTax[] = [
            'taxonomy' => MediaFoldersTaxonomy::TAXONOMY,
            'field'    => 'slug',
            'terms'    => $slug,
            'include_children' => true,
        ];
        $query->set('tax_query', $existingTax);
    }

    /**
     * Filtre les attachments listés dans la modale wp.media (sélecteur de
     * featured image, galerie, etc.) si un dossier est demandé via query var.
     *
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function filterAjaxAttachments(array $args): array
    {
        $folder = isset($_POST['query'][MediaFoldersTaxonomy::TAXONOMY])
            ? sanitize_key((string) $_POST['query'][MediaFoldersTaxonomy::TAXONOMY])
            : '';
        if ($folder === '') {
            return $args;
        }
        $args['tax_query'] = array_merge((array) ($args['tax_query'] ?? []), [[
            'taxonomy' => MediaFoldersTaxonomy::TAXONOMY,
            'field'    => 'slug',
            'terms'    => $folder,
            'include_children' => true,
        ]]);
        return $args;
    }
}
