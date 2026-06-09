<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders;

/**
 * Page d'admin + endpoint AJAX pour réordonner par drag & drop les photos
 * d'un dossier de la médiathèque (mise à jour de `menu_order` sur les
 * attachments concernés).
 *
 * Le tri est ensuite reflété en frontend par
 * {@see MediaFolderQuery::photosInFolder()} qui ordonne par `menu_order ASC`.
 *
 * @package OliTheme\MediaFolders
 *
 * @since 1.6.0
 */
final class MediaFoldersReorder
{
    public const PAGE_SLUG    = 'oli-media-folders-reorder';
    public const AJAX_ACTION  = 'oli_media_folder_reorder_save';
    public const NONCE_ACTION = 'oli_media_folder_reorder';

    public function __construct(private readonly MediaFolderQuery $query = new MediaFolderQuery())
    {
    }

    /**
     * Hooks WordPress : menu admin, endpoint AJAX, enqueue conditionnel.
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'handleSave']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Sous-menu sous Médias.
     */
    public function registerMenu(): void
    {
        add_submenu_page(
            'upload.php',
            __('Ordonner les galeries', 'oli-theme'),
            __('Ordonner les galeries', 'oli-theme'),
            'upload_files',
            self::PAGE_SLUG,
            [$this, 'renderPage'],
        );
    }

    /**
     * Enqueue JS + CSS uniquement sur la page de réordonnancement.
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
            'action'  => self::AJAX_ACTION,
            'nonce'   => wp_create_nonce(self::NONCE_ACTION),
            'i18n'    => [
                'saving'  => __('Enregistrement…', 'oli-theme'),
                'saved'   => __('Ordre enregistré.', 'oli-theme'),
                'error'   => __('Erreur lors de l\'enregistrement.', 'oli-theme'),
                'empty'   => __('Aucune photo dans ce dossier.', 'oli-theme'),
            ],
        ]);
    }

    /**
     * Rend la page admin : sélecteur de dossier + grille triable des photos
     * du dossier sélectionné.
     */
    public function renderPage(): void
    {
        if (!current_user_can('upload_files')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
        $selected = isset($_GET['folder']) ? sanitize_key((string) $_GET['folder']) : '';
        $photos = $selected !== '' ? $this->query->photosInFolder($selected, false, -1) : [];
        ?>
        <div class="wrap oli-reorder">
            <h1><?php esc_html_e('Ordonner les galeries', 'oli-theme'); ?></h1>
            <p class="description">
                <?php esc_html_e('Choisis un dossier puis glisse les vignettes pour les réordonner. L\'ordre est appliqué à la galerie publique correspondante.', 'oli-theme'); ?>
            </p>

            <form method="get" action="" class="oli-reorder__picker">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>">
                <label for="oli-reorder-folder"><strong><?php esc_html_e('Dossier :', 'oli-theme'); ?></strong></label>
                <?php
                wp_dropdown_categories([
                    'taxonomy'         => MediaFoldersTaxonomy::TAXONOMY,
                    'name'             => 'folder',
                    'id'               => 'oli-reorder-folder',
                    'value_field'      => 'slug',
                    'selected'         => $selected,
                    'show_option_none' => __('— Choisir un dossier —', 'oli-theme'),
                    'option_none_value' => '',
                    'hide_empty'       => false,
                    'hierarchical'     => true,
                    'depth'            => 5,
                    'orderby'          => 'name',
                ]);
                ?>
                <button type="submit" class="button"><?php esc_html_e('Afficher', 'oli-theme'); ?></button>
            </form>

            <?php if ($selected === '') : ?>
                <p><em><?php esc_html_e('Aucun dossier sélectionné.', 'oli-theme'); ?></em></p>
            <?php elseif ($photos === []) : ?>
                <p><em><?php esc_html_e('Aucune photo dans ce dossier.', 'oli-theme'); ?></em></p>
            <?php else : ?>
                <div class="oli-reorder__toolbar">
                    <button
                        type="button"
                        class="button button-primary"
                        id="oli-reorder-save"
                        data-folder="<?php echo esc_attr($selected); ?>"
                    >
                        <?php esc_html_e('Enregistrer l\'ordre', 'oli-theme'); ?>
                    </button>
                    <span class="oli-reorder__status" id="oli-reorder-status" aria-live="polite"></span>
                </div>
                <ul class="oli-reorder__grid" id="oli-reorder-grid">
                    <?php foreach ($photos as $photo) : ?>
                        <li
                            class="oli-reorder__item"
                            draggable="true"
                            data-id="<?php echo (int) $photo['id']; ?>"
                        >
                            <img
                                src="<?php echo esc_url($photo['thumb']); ?>"
                                alt="<?php echo esc_attr($photo['alt']); ?>"
                                loading="lazy"
                                width="150"
                                height="150"
                            >
                            <span class="oli-reorder__caption">
                                <?php echo esc_html($photo['title'] !== '' ? $photo['title'] : (string) $photo['id']); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handler AJAX qui persiste un nouvel ordre `menu_order` pour les
     * attachments d'un dossier.
     */
    public function handleSave(): void
    {
        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => esc_html__('Action non autorisée.', 'oli-theme')], 403);
        }
        if (check_ajax_referer(self::NONCE_ACTION, 'nonce', false) === false) {
            wp_send_json_error(['message' => esc_html__('Jeton de sécurité invalide.', 'oli-theme')], 403);
        }

        $folder = isset($_POST['folder']) ? sanitize_key((string) $_POST['folder']) : '';
        $rawOrder = isset($_POST['order']) && \is_array($_POST['order']) ? $_POST['order'] : [];
        $order = array_values(array_filter(array_map(static fn ($v): int => (int) $v, $rawOrder)));

        if ($folder === '' || $order === []) {
            wp_send_json_error(['message' => esc_html__('Données incomplètes.', 'oli-theme')], 422);
        }

        $updated = 0;
        $position = 0;
        foreach ($order as $attachmentId) {
            // Garde-fou : on ne touche que les attachments réellement
            // taggés sur le dossier ciblé (évite toute écriture croisée).
            if (!has_term($folder, MediaFoldersTaxonomy::TAXONOMY, $attachmentId)) {
                continue;
            }
            wp_update_post([
                'ID'         => $attachmentId,
                'menu_order' => $position,
            ]);
            ++$position;
            ++$updated;
        }

        wp_send_json_success([
            'folder'  => $folder,
            'updated' => $updated,
        ]);
    }
}
