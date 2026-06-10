<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders;

/**
 * Page d'administration unifiée sous Médias : choix des dossiers exposés
 * sur la page publique « Galerie photos » + réordonnancement drag & drop
 * des photos d'un dossier.
 *
 * Stocke la sélection dans l'option `oli_gallery_folders` (liste de slugs).
 * Le {@see \OliTheme\Posts\PageController} lit cette liste pour déterminer
 * quels dossiers afficher, et le filtre « Tous » agrège leurs photos.
 *
 * Le réordonnancement est délégué à {@see MediaFoldersReorder} qui expose
 * la grille drag-drop ({@see MediaFoldersReorder::renderGrid()}) et l'endpoint
 * AJAX consommé par `assets/js/media-folders-reorder.js`.
 *
 * @package OliTheme\MediaFolders
 *
 * @since 1.6.0
 */
final class MediaFoldersGallerySettings
{
    public const PAGE_SLUG    = 'oli-media-folders-gallery';
    public const OPTION       = 'oli_gallery_folders';
    public const NONCE_ACTION = 'oli_media_folders_gallery_save';
    public const POST_ACTION  = 'oli_media_folders_gallery_save';

    public function __construct(
        private readonly ?MediaFolderQuery $query = null,
        private readonly ?MediaFoldersReorder $reorder = null,
    ) {
    }

    /**
     * Hooks WordPress.
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_post_' . self::POST_ACTION, [$this, 'handleSave']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Sous-menu sous Médias.
     */
    public function registerMenu(): void
    {
        add_submenu_page(
            'upload.php',
            __('Galerie photos', 'oli-theme'),
            __('Galerie photos', 'oli-theme'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage'],
        );
    }

    /**
     * Enqueue le CSS + JS de la grille drag-drop uniquement sur la page
     * unifiée.
     *
     * @param string $hookSuffix Suffixe de la page admin courante.
     */
    public function enqueueAssets(string $hookSuffix): void
    {
        if (!str_ends_with($hookSuffix, '_page_' . self::PAGE_SLUG)) {
            return;
        }
        $themeUri = (string) get_template_directory_uri();
        wp_enqueue_style(
            'oli-media-folders-reorder',
            $themeUri . '/assets/css/media-folders-reorder.css',
            [],
            '1.6.0',
        );
        wp_enqueue_script(
            'oli-media-folders-reorder',
            $themeUri . '/assets/js/media-folders-reorder.js',
            [],
            '1.6.0',
            true,
        );
        wp_localize_script('oli-media-folders-reorder', 'oliReorder', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'action'  => MediaFoldersReorder::AJAX_ACTION,
            'nonce'   => wp_create_nonce(MediaFoldersReorder::NONCE_ACTION),
            'i18n'    => [
                'saving' => __('Enregistrement…', 'oli-theme'),
                'saved'  => __('Ordre enregistré.', 'oli-theme'),
                'error'  => __('Erreur lors de l\'enregistrement.', 'oli-theme'),
                'empty'  => __('Aucune photo dans ce dossier.', 'oli-theme'),
            ],
        ]);
    }

    /**
     * Slugs des dossiers configurés pour la page Galerie photos.
     *
     * @return list<string>
     */
    public function getConfiguredFolders(): array
    {
        $raw = get_option(self::OPTION, []);
        if (!\is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $slug) {
            if (!\is_string($slug) || $slug === '') {
                continue;
            }
            $out[] = $slug;
        }

        return $out;
    }

    /**
     * Rend la page d'admin : cases à cocher des dossiers exposés + sélecteur
     * de dossier à réordonner + grille drag-drop des photos.
     */
    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
        $selected     = $this->getConfiguredFolders();
        $folders      = $this->query !== null ? $this->query->allFolders() : [];
        $saved        = isset($_GET['saved']) && $_GET['saved'] === '1';
        $reorderSlug  = isset($_GET['reorder']) ? sanitize_key((string) $_GET['reorder']) : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Galerie photos', 'oli-theme'); ?></h1>
            <p class="description">
                <?php esc_html_e('Coche les dossiers de la médiathèque à exposer sur la page publique « Galerie photos », puis choisis un dossier ci-dessous pour réordonner ses photos par glisser-déposer.', 'oli-theme'); ?>
            </p>

            <?php if ($saved) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Sélection enregistrée.', 'oli-theme'); ?></p></div>
            <?php endif; ?>

            <?php if ($folders === []) : ?>
                <p>
                    <em><?php esc_html_e('Aucun dossier n\'existe encore.', 'oli-theme'); ?></em>
                    <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=oli_media_folder&post_type=attachment')); ?>">
                        <?php esc_html_e('Créer un premier dossier', 'oli-theme'); ?>
                    </a>
                </p>
                <?php
                return;
            endif;
            ?>

            <h2><?php esc_html_e('Dossiers exposés', 'oli-theme'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::POST_ACTION); ?>">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <?php foreach ($folders as $folder) : ?>
                            <tr>
                                <th scope="row">
                                    <label>
                                        <input
                                            type="checkbox"
                                            name="folders[]"
                                            value="<?php echo esc_attr($folder['slug']); ?>"
                                            <?php checked(\in_array($folder['slug'], $selected, true)); ?>
                                        >
                                        <?php echo esc_html($folder['name']); ?>
                                    </label>
                                </th>
                                <td>
                                    <span class="description">
                                        <?php
                                        printf(
                                            /* translators: %d = nombre de photos */
                                            esc_html(_n('%d photo', '%d photos', (int) $folder['count'], 'oli-theme')),
                                            (int) $folder['count'],
                                        );
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Enregistrer la sélection', 'oli-theme'); ?>
                    </button>
                </p>
            </form>

            <hr>

            <h2><?php esc_html_e('Ordonner les photos d\'un dossier', 'oli-theme'); ?></h2>
            <form method="get" action="" class="oli-reorder__picker">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>">
                <label for="oli-reorder-folder"><strong><?php esc_html_e('Dossier :', 'oli-theme'); ?></strong></label>
                <?php
                wp_dropdown_categories([
                    'taxonomy'          => MediaFoldersTaxonomy::TAXONOMY,
                    'name'              => 'reorder',
                    'id'                => 'oli-reorder-folder',
                    'value_field'       => 'slug',
                    'selected'          => $reorderSlug,
                    'show_option_none'  => __('— Choisir un dossier —', 'oli-theme'),
                    'option_none_value' => '',
                    'hide_empty'        => false,
                    'hierarchical'      => true,
                    'depth'             => 5,
                    'orderby'           => 'name',
                ]);
                ?>
                <button type="submit" class="button"><?php esc_html_e('Afficher', 'oli-theme'); ?></button>
            </form>

            <?php
            if ($reorderSlug !== '' && $this->reorder !== null) {
                $this->reorder->renderGrid($reorderSlug);
            }
            ?>
        </div>
        <?php
    }

    /**
     * Handler de soumission (admin-post.php) : sanitize + persiste.
     */
    public function handleSave(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
        check_admin_referer(self::NONCE_ACTION);

        $raw = isset($_POST['folders']) && \is_array($_POST['folders']) ? $_POST['folders'] : [];
        $seen = [];
        $clean = [];
        foreach ($raw as $value) {
            if (!\is_string($value)) {
                continue;
            }
            $slug = sanitize_key($value);
            if ($slug === '' || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $clean[]     = $slug;
        }
        update_option(self::OPTION, $clean);

        wp_safe_redirect(add_query_arg(
            ['page' => self::PAGE_SLUG, 'saved' => '1'],
            admin_url('upload.php'),
        ));
    }
}
