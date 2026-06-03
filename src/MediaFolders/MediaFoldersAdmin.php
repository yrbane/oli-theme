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
        // Filtre dropdown au-dessus de la liste des médias (upload.php LIST view).
        add_action('restrict_manage_posts', [$this, 'renderFilterDropdown']);
        // Filtre la requête principale selon le dossier sélectionné.
        add_filter('parse_query', [$this, 'filterQueryByFolder']);
        // Ajoute le filtre par dossier dans la modale wp.media (sélecteur).
        add_filter('ajax_query_attachments_args', [$this, 'filterAjaxAttachments']);
        // Enqueue le script Backbone qui ajoute le filtre dans la vue Grille
        // de la médiathèque et la modale wp.media (sélecteurs d'attachments).
        add_action('wp_enqueue_media', [$this, 'enqueueGridFilter']);
        // Affiche une notice rapide sur upload.php pour orienter Olivier.
        add_action('admin_notices', [$this, 'maybeRenderHelpNotice']);
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
     * Enqueue le script Backbone qui ajoute le filtre Dossier dans toutes
     * les instances de wp.media (vue Grille de upload.php + modales).
     */
    public function enqueueGridFilter(): void
    {
        if (!\function_exists('get_template_directory_uri') || !taxonomy_exists(MediaFoldersTaxonomy::TAXONOMY)) {
            return;
        }
        $themeUri = (string) get_template_directory_uri();
        wp_enqueue_script(
            'oli-media-folders-admin',
            $themeUri . '/assets/js/media-folders-admin.js',
            ['media-views'],
            '1.5.0',
            true,
        );

        // Liste des dossiers à passer au JS (parent → enfants, avec depth).
        $terms = get_terms([
            'taxonomy'   => MediaFoldersTaxonomy::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'name',
        ]);
        $folders = [];
        if (\is_array($terms)) {
            $folders = $this->flattenHierarchy($terms);
        }
        wp_localize_script('oli-media-folders-admin', 'oliMediaFolders', $folders);
    }

    /**
     * Affiche un message d'aide sur upload.php pour expliquer comment
     * utiliser les dossiers (la metabox apparaît en vue Liste ou via le filtre).
     */
    public function maybeRenderHelpNotice(): void
    {
        $screen = \function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || $screen->base !== 'upload') {
            return;
        }
        $countTerms = (int) wp_count_terms([
            'taxonomy'   => MediaFoldersTaxonomy::TAXONOMY,
            'hide_empty' => false,
        ]);
        if ($countTerms === 0) {
            // Aucun dossier créé → invite explicite.
            echo '<div class="notice notice-info is-dismissible"><p>';
            printf(
                '<strong>%s</strong> %s <a href="%s">%s</a>.',
                esc_html__('Aucun dossier créé pour le moment.', 'oli-theme'),
                esc_html__('Pour organiser tes photos en galeries :', 'oli-theme'),
                esc_url(admin_url('edit-tags.php?taxonomy=oli_media_folder&post_type=attachment')),
                esc_html__('créer un premier dossier', 'oli-theme'),
            );
            echo '</p></div>';
            return;
        }
        // Sinon, rappel rapide.
        echo '<div class="notice notice-info is-dismissible"><p>';
        printf(
            '<strong>%s</strong> %s <code>%s</code> %s <a href="%s">%s</a>.',
            esc_html__('Dossiers :', 'oli-theme'),
            esc_html__('un filtre apparaît au-dessus de la grille pour montrer les photos d\'un dossier. Pour ranger une photo, ouvre-la et coche un dossier dans la metabox', 'oli-theme'),
            esc_html__('Dossiers', 'oli-theme'),
            esc_html__('. Gérer les dossiers :', 'oli-theme'),
            esc_url(admin_url('edit-tags.php?taxonomy=oli_media_folder&post_type=attachment')),
            esc_html__('Médias → Dossiers', 'oli-theme'),
        );
        echo '</p></div>';
    }

    /**
     * Aplatit une liste hiérarchique de terms en une liste à plat avec depth,
     * pour générer un select indenté côté JS.
     *
     * @param list<object> $terms
     *
     * @return list<array{slug:string,name:string,parent:int,depth:int}>
     */
    private function flattenHierarchy(array $terms): array
    {
        $byParent = [];
        foreach ($terms as $t) {
            $byParent[(int) ($t->parent ?? 0)][] = $t;
        }
        $out = [];
        $walk = static function (int $parent, int $depth) use (&$walk, &$out, $byParent): void {
            foreach ($byParent[$parent] ?? [] as $term) {
                $out[] = [
                    'slug'   => (string) $term->slug,
                    'name'   => (string) $term->name,
                    'parent' => (int) $term->parent,
                    'depth'  => $depth,
                ];
                $walk((int) $term->term_id, $depth + 1);
            }
        };
        $walk(0, 0);
        return $out;
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
