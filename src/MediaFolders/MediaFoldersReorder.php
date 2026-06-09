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

    /**
     * Handler AJAX qui persiste un nouvel ordre `menu_order` pour les
     * attachments d'un dossier.
     */
    public function handleSave(): void
    {
        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => esc_html__('Action non autorisée.', 'oli-theme')], 403);

            return;
        }
        if (check_ajax_referer(self::NONCE_ACTION, 'nonce', false) === false) {
            wp_send_json_error(['message' => esc_html__('Jeton de sécurité invalide.', 'oli-theme')], 403);

            return;
        }

        $folder = isset($_POST['folder']) ? sanitize_key((string) $_POST['folder']) : '';
        $rawOrder = isset($_POST['order']) && \is_array($_POST['order']) ? $_POST['order'] : [];
        $order = array_values(array_filter(array_map(static fn ($v): int => (int) $v, $rawOrder)));

        if ($folder === '' || $order === []) {
            wp_send_json_error(['message' => esc_html__('Données incomplètes.', 'oli-theme')], 422);

            return;
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
