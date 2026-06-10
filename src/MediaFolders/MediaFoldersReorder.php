<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders;

/**
 * Service de réordonnancement par drag & drop des photos d'un dossier.
 *
 * Expose :
 *  - l'endpoint AJAX `oli_media_folder_reorder_save` qui persiste un nouvel
 *    ordre `menu_order` sur les attachments d'un dossier ;
 *  - un helper {@see renderGrid()} qui rend la grille triable (toolbar +
 *    vignettes draggables) — réutilisé par {@see MediaFoldersGallerySettings}
 *    qui fournit la page admin unifiée.
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
    public const AJAX_ACTION  = 'oli_media_folder_reorder_save';
    public const NONCE_ACTION = 'oli_media_folder_reorder';

    public function __construct(private readonly MediaFolderQuery $query = new MediaFolderQuery())
    {
    }

    /**
     * Hooks WordPress : enregistre uniquement l'endpoint AJAX. La page admin
     * et l'enqueue des assets sont gérés par {@see MediaFoldersGallerySettings}
     * (page unifiée Médias → Galerie photos).
     */
    public function register(): void
    {
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'handleSave']);
    }

    /**
     * Rend la grille triable d'un dossier (toolbar « Enregistrer » + liste de
     * vignettes draggables). À appeler depuis une page admin qui a enqueue
     * `media-folders-reorder.css` et `media-folders-reorder.js`.
     */
    public function renderGrid(string $folderSlug): void
    {
        $photos = $folderSlug !== '' ? $this->query->photosInFolder($folderSlug, false, -1) : [];

        if ($folderSlug === '') {
            return;
        }
        if ($photos === []) {
            ?>
            <p><em><?php esc_html_e('Aucune photo dans ce dossier.', 'oli-theme'); ?></em></p>
            <?php
            return;
        }
        ?>
        <div class="oli-reorder__toolbar">
            <button
                type="button"
                class="button button-primary"
                id="oli-reorder-save"
                data-folder="<?php echo esc_attr($folderSlug); ?>"
            >
                <?php esc_html_e('Enregistrer l\'ordre des photos', 'oli-theme'); ?>
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
