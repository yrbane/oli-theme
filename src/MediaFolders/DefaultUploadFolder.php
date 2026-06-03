<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders;

/**
 * « Dossier d'upload par défaut » — toutes les images uploadées dans
 * la session admin courante seront automatiquement assignées au dossier
 * choisi.
 *
 * Persistance : user meta `oli_default_upload_folder` (par utilisateur,
 * survit entre sessions ; l'admin peut le reset avec « Aucun »).
 *
 * @package OliTheme\MediaFolders
 *
 * @since 1.5.0
 */
final class DefaultUploadFolder
{
    public const USERMETA = 'oli_default_upload_folder';
    public const FORM_KEY = 'oli_default_upload_folder';

    public function register(): void
    {
        // 1. Assigne automatiquement les nouveaux attachments au dossier choisi.
        add_action('add_attachment', [$this, 'assignNewUpload']);
        // 2. UI : select dans une notice persistante au-dessus de upload.php
        //    + page Médias → Dossiers (footer du formulaire de term).
        add_action('admin_notices',  [$this, 'renderSelector']);
        // 3. Handler du changement de défaut.
        add_action('admin_post_oli_default_upload_folder', [$this, 'handleSave']);
    }

    public function assignNewUpload(int $attachmentId): void
    {
        $userId = \function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        if ($userId <= 0) {
            return;
        }
        $folderId = (int) get_user_meta($userId, self::USERMETA, true);
        if ($folderId <= 0) {
            return;
        }
        $term = get_term($folderId, MediaFoldersTaxonomy::TAXONOMY);
        if (!\is_object($term) || isset($term->errors)) {
            return;
        }
        wp_set_object_terms($attachmentId, [(int) $term->term_id], MediaFoldersTaxonomy::TAXONOMY, true);
    }

    public function renderSelector(): void
    {
        $screen = \function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || !\in_array($screen->base, ['upload', 'media'], true)) {
            return;
        }
        if (!taxonomy_exists(MediaFoldersTaxonomy::TAXONOMY)) {
            return;
        }
        $userId  = (int) get_current_user_id();
        $current = (int) get_user_meta($userId, self::USERMETA, true);
        $currentName = '';
        if ($current > 0) {
            $term = get_term($current, MediaFoldersTaxonomy::TAXONOMY);
            if (\is_object($term)) {
                $currentName = (string) $term->name;
            }
        }
        $action = admin_url('admin-post.php');
        ?>
        <div class="notice notice-info" style="padding:0.5rem 1rem;display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <strong><?php esc_html_e('Dossier par défaut pour mes uploads :', 'oli-theme'); ?></strong>
            <form method="post" action="<?php echo esc_url($action); ?>" style="display:inline-flex;gap:0.5rem;align-items:center;margin:0;">
                <input type="hidden" name="action" value="oli_default_upload_folder">
                <?php wp_nonce_field('oli_default_upload_folder'); ?>
                <?php wp_dropdown_categories([
                    'taxonomy'          => MediaFoldersTaxonomy::TAXONOMY,
                    'name'              => self::FORM_KEY,
                    'show_option_none'  => __('— Aucun (pas d\'assignation auto) —', 'oli-theme'),
                    'option_none_value' => '0',
                    'selected'          => $current,
                    'hide_empty'        => false,
                    'hierarchical'      => true,
                    'depth'             => 5,
                    'orderby'           => 'name',
                ]); ?>
                <button type="submit" class="button button-small"><?php esc_html_e('Définir', 'oli-theme'); ?></button>
            </form>
            <?php if ($current > 0 && $currentName !== ''): ?>
                <em style="color:#50575e;">
                    <?php
                    printf(
                        esc_html__('Actuel : %s — les prochains uploads y seront rangés automatiquement.', 'oli-theme'),
                        '<strong>' . esc_html($currentName) . '</strong>',
                    );
                    ?>
                </em>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handleSave(): void
    {
        if (!current_user_can('upload_files')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
        check_admin_referer('oli_default_upload_folder');

        $userId = (int) get_current_user_id();
        $value  = isset($_POST[self::FORM_KEY]) ? (int) $_POST[self::FORM_KEY] : 0;
        if ($value <= 0) {
            delete_user_meta($userId, self::USERMETA);
        } else {
            $term = get_term($value, MediaFoldersTaxonomy::TAXONOMY);
            if (\is_object($term) && !isset($term->errors)) {
                update_user_meta($userId, self::USERMETA, (int) $term->term_id);
            }
        }
        wp_safe_redirect(wp_get_referer() ?: admin_url('upload.php'));
        exit;
    }
}
