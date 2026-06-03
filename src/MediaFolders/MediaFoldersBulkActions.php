<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders;

/**
 * Bulk actions sur la médiathèque (upload.php, vue Liste) pour traiter
 * plusieurs médias à la fois :
 *
 *  - **Déplacer vers le dossier…**  : remplace les dossiers existants par
 *    le dossier choisi (équivalent d'un « move from → to »).
 *  - **Ajouter au dossier…**         : append-only — l'attachment est
 *    désormais dans son ou ses anciens dossiers + le nouveau (un même
 *    fichier peut appartenir à plusieurs dossiers).
 *  - **Retirer du dossier…**         : enlève le dossier sélectionné de
 *    chaque attachment, sans toucher aux autres.
 *
 * Le choix du dossier cible se fait via une seconde page de confirmation
 * (`admin-post.php`) qui affiche un select déroulant hiérarchique des
 * dossiers existants.
 *
 * @package OliTheme\MediaFolders
 *
 * @since 1.5.0
 */
final class MediaFoldersBulkActions
{
    public const ACTION_MOVE   = 'oli_media_folder_move';
    public const ACTION_ADD    = 'oli_media_folder_add';
    public const ACTION_REMOVE = 'oli_media_folder_remove';
    public const PAGE_SLUG     = 'oli-media-folder-bulk';

    public function register(): void
    {
        // Bulk actions visibles dans le select au-dessus de upload.php (LIST).
        add_filter('bulk_actions-upload', [$this, 'registerBulkActions']);
        // Handler : capture l'action choisie, stocke les IDs en transient,
        // redirige vers la page de confirmation où l'admin choisit le dossier.
        add_filter('handle_bulk_actions-upload', [$this, 'handleBulkAction'], 10, 3);
        // Page admin de confirmation (où on choisit le dossier cible).
        add_action('admin_menu',           [$this, 'registerConfirmationPage']);
        add_action('admin_post_' . self::PAGE_SLUG, [$this, 'handleConfirmation']);
        // Notice de succès après application.
        add_action('admin_notices',        [$this, 'renderResultNotice']);
    }

    /**
     * @param array<string, string> $actions
     *
     * @return array<string, string>
     */
    public function registerBulkActions(array $actions): array
    {
        $actions[self::ACTION_MOVE]   = __('Déplacer vers le dossier…', 'oli-theme');
        $actions[self::ACTION_ADD]    = __('Ajouter au dossier…', 'oli-theme');
        $actions[self::ACTION_REMOVE] = __('Retirer du dossier…', 'oli-theme');
        return $actions;
    }

    /**
     * Intercepte les 3 actions, sauvegarde la sélection en transient, puis
     * redirige vers la page de confirmation. Retourne le redirect URL.
     *
     * @param string     $redirect URL par défaut où WP redirige après bulk.
     * @param string     $action   slug de l'action choisie.
     * @param list<int>  $ids      attachments cochés.
     */
    public function handleBulkAction(string $redirect, string $action, array $ids): string
    {
        if (!\in_array($action, [self::ACTION_MOVE, self::ACTION_ADD, self::ACTION_REMOVE], true)) {
            return $redirect;
        }
        if (empty($ids) || !current_user_can('upload_files')) {
            return $redirect;
        }

        $token = wp_generate_password(20, false);
        set_transient(
            'oli_media_folder_bulk_' . $token,
            ['action' => $action, 'ids' => array_values(array_map('intval', $ids))],
            600,
        );

        return add_query_arg([
            'page'  => self::PAGE_SLUG,
            'token' => $token,
        ], admin_url('upload.php'));
    }

    /**
     * Enregistre la page de confirmation cachée sous upload.php (pas de
     * lien de menu — accessible uniquement via la bulk action).
     */
    public function registerConfirmationPage(): void
    {
        add_submenu_page(
            'upload.php',
            __('Action groupée Dossiers', 'oli-theme'),
            '', // pas de menu visible
            'upload_files',
            self::PAGE_SLUG,
            [$this, 'renderConfirmation'],
        );
    }

    public function renderConfirmation(): void
    {
        if (!current_user_can('upload_files')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
        $token = isset($_GET['token']) ? sanitize_key((string) $_GET['token']) : '';
        $job   = $token !== '' ? get_transient('oli_media_folder_bulk_' . $token) : false;
        if (!\is_array($job) || !isset($job['action'], $job['ids'])) {
            wp_die(esc_html__('Le lien de cette action groupée a expiré. Recommencez.', 'oli-theme'));
        }

        $action = (string) $job['action'];
        $count  = \count((array) $job['ids']);

        $labels = [
            self::ACTION_MOVE   => __('Déplacer vers le dossier', 'oli-theme'),
            self::ACTION_ADD    => __('Ajouter au dossier', 'oli-theme'),
            self::ACTION_REMOVE => __('Retirer du dossier', 'oli-theme'),
        ];
        $help = [
            self::ACTION_MOVE   => __('Les dossiers actuels des médias sélectionnés seront remplacés par celui que tu choisis.', 'oli-theme'),
            self::ACTION_ADD    => __('Le dossier choisi s\'ajoutera aux dossiers existants (un média peut appartenir à plusieurs dossiers).', 'oli-theme'),
            self::ACTION_REMOVE => __('Le dossier choisi sera retiré de chaque média sélectionné ; les autres dossiers restent.', 'oli-theme'),
        ];

        $admin = admin_url('admin-post.php');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($labels[$action] ?? $action); ?></h1>
            <p>
                <?php printf(
                    esc_html(_n('%d média sélectionné.', '%d médias sélectionnés.', $count, 'oli-theme')),
                    $count,
                ); ?>
            </p>
            <p><?php echo esc_html($help[$action] ?? ''); ?></p>

            <form method="post" action="<?php echo esc_url($admin); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::PAGE_SLUG); ?>">
                <input type="hidden" name="token"  value="<?php echo esc_attr($token); ?>">
                <?php wp_nonce_field(self::PAGE_SLUG); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="oli-folder-target"><?php esc_html_e('Dossier cible', 'oli-theme'); ?></label></th>
                        <td>
                            <?php
                            wp_dropdown_categories([
                                'taxonomy'         => MediaFoldersTaxonomy::TAXONOMY,
                                'name'             => 'folder_id',
                                'id'               => 'oli-folder-target',
                                'show_option_none' => __('— Choisir un dossier —', 'oli-theme'),
                                'option_none_value' => '0',
                                'hide_empty'       => false,
                                'hierarchical'     => true,
                                'depth'            => 5,
                                'orderby'          => 'name',
                            ]);
                            ?>
                            <p class="description"><?php esc_html_e('Si aucun dossier n\'existe encore, crée-en un d\'abord depuis Médias → Dossiers.', 'oli-theme'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Appliquer', 'oli-theme')); ?>
                <a href="<?php echo esc_url(admin_url('upload.php')); ?>" class="button button-secondary"><?php esc_html_e('Annuler', 'oli-theme'); ?></a>
            </form>
        </div>
        <?php
    }

    public function handleConfirmation(): void
    {
        if (!current_user_can('upload_files')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
        check_admin_referer(self::PAGE_SLUG);

        $token = isset($_POST['token']) ? sanitize_key((string) $_POST['token']) : '';
        $key   = 'oli_media_folder_bulk_' . $token;
        $job   = $token !== '' ? get_transient($key) : false;
        if (!\is_array($job) || !isset($job['action'], $job['ids'])) {
            wp_die(esc_html__('Le lien de cette action groupée a expiré. Recommencez.', 'oli-theme'));
        }
        delete_transient($key);

        $folderId = (int) ($_POST['folder_id'] ?? 0);
        if ($folderId <= 0) {
            wp_safe_redirect(add_query_arg('oli_folder_bulk', 'no_target', admin_url('upload.php')));
            exit;
        }
        $term = get_term($folderId, MediaFoldersTaxonomy::TAXONOMY);
        if (!\is_object($term) || isset($term->errors)) {
            wp_safe_redirect(add_query_arg('oli_folder_bulk', 'invalid_target', admin_url('upload.php')));
            exit;
        }

        $action = (string) $job['action'];
        $count  = 0;
        foreach ((array) $job['ids'] as $attachmentId) {
            $attachmentId = (int) $attachmentId;
            if ($attachmentId <= 0) {
                continue;
            }
            switch ($action) {
                case self::ACTION_MOVE:
                    wp_set_object_terms($attachmentId, [(int) $term->term_id], MediaFoldersTaxonomy::TAXONOMY, false);
                    break;
                case self::ACTION_ADD:
                    wp_set_object_terms($attachmentId, [(int) $term->term_id], MediaFoldersTaxonomy::TAXONOMY, true);
                    break;
                case self::ACTION_REMOVE:
                    wp_remove_object_terms($attachmentId, [(int) $term->term_id], MediaFoldersTaxonomy::TAXONOMY);
                    break;
            }
            $count++;
        }

        wp_safe_redirect(add_query_arg([
            'oli_folder_bulk'  => $action,
            'oli_folder_count' => $count,
            'oli_folder_name'  => urlencode((string) $term->name),
        ], admin_url('upload.php')));
        exit;
    }

    public function renderResultNotice(): void
    {
        $screen = \function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || $screen->base !== 'upload') {
            return;
        }
        $status = isset($_GET['oli_folder_bulk']) ? sanitize_key((string) $_GET['oli_folder_bulk']) : '';
        if ($status === '') {
            return;
        }
        if ($status === 'no_target') {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('Aucun dossier cible sélectionné — opération annulée.', 'oli-theme') . '</p></div>';
            return;
        }
        if ($status === 'invalid_target') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Dossier cible invalide.', 'oli-theme') . '</p></div>';
            return;
        }
        $count = isset($_GET['oli_folder_count']) ? (int) $_GET['oli_folder_count'] : 0;
        $name  = isset($_GET['oli_folder_name'])  ? sanitize_text_field(urldecode((string) $_GET['oli_folder_name'])) : '';
        $msg = match ($status) {
            self::ACTION_MOVE   => sprintf(_n('%d média déplacé vers « %s ».', '%d médias déplacés vers « %s ».', $count, 'oli-theme'), $count, $name),
            self::ACTION_ADD    => sprintf(_n('%d média ajouté à « %s ».',    '%d médias ajoutés à « %s ».',    $count, 'oli-theme'), $count, $name),
            self::ACTION_REMOVE => sprintf(_n('%d média retiré de « %s ».',   '%d médias retirés de « %s ».',   $count, 'oli-theme'), $count, $name),
            default             => '',
        };
        if ($msg !== '') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        }
    }
}
