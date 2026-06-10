<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders;

/**
 * Page d'administration sous Médias permettant à l'éditeur de cocher les
 * dossiers de la médiathèque à exposer sur la page publique « Galerie photos ».
 *
 * Stocke la sélection dans l'option `oli_gallery_folders` (liste de slugs).
 * Le {@see \OliTheme\Posts\PageController} lit cette liste pour déterminer
 * quels dossiers afficher, et le filtre « Tous » agrège leurs photos.
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

    public function __construct(private readonly ?MediaFolderQuery $query = null)
    {
    }

    /**
     * Hooks WordPress.
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_post_' . self::POST_ACTION, [$this, 'handleSave']);
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
     * Rend la page d'admin : liste de cases à cocher (un dossier par case).
     */
    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
        $selected = $this->getConfiguredFolders();
        $folders  = $this->query !== null ? $this->query->allFolders() : [];
        $saved    = isset($_GET['saved']) && $_GET['saved'] === '1';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Galerie photos — choix des dossiers', 'oli-theme'); ?></h1>
            <p class="description">
                <?php esc_html_e('Coche les dossiers de la médiathèque à exposer sur la page publique « Galerie photos ». L\'ordre des photos dans chaque dossier se gère depuis « Ordonner les galeries ».', 'oli-theme'); ?>
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
            <?php else : ?>
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
            <?php endif; ?>
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
