<?php

declare(strict_types=1);

namespace OliTheme\Social;

/**
 * Page d'administration « Apparence > Réseaux sociaux ».
 *
 * Liste les plateformes supportées avec leur icône à côté du champ URL.
 *
 * @package OliTheme\Social
 *
 * @since 1.0.0
 */
final class SocialAdminPage
{
    public const PAGE_SLUG = 'oli-social-links';

    public function __construct(private readonly SocialLinksRepository $repo)
    {
    }

    public function register(): void
    {
        add_theme_page(
            __('Réseaux sociaux', 'oli-theme'),
            __('Réseaux sociaux', 'oli-theme'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
        );
    }

    public function render(): void
    {
        if (!empty($_POST['oli_social_save'])) {
            $this->handleSave();
        }

        $values = $this->repo->all();
        $iconsBaseUri = \function_exists('get_template_directory_uri')
            ? rtrim((string) get_template_directory_uri(), '/') . '/assets/img/icons/social'
            : '/assets/img/icons/social';

        ?>
        <div class="wrap oli-social-admin">
            <h1><?php esc_html_e('Réseaux sociaux', 'oli-theme'); ?></h1>
            <p class="description">
                <?php esc_html_e('Renseignez l\'URL de votre profil pour chaque plateforme. Les icônes correspondantes seront affichées dans le pied de page du site. Laissez vide pour masquer un réseau.', 'oli-theme'); ?>
            </p>

            <form method="post" action="">
                <?php wp_nonce_field('oli_social_save', '_oli_social_nonce'); ?>
                <input type="hidden" name="oli_social_save" value="1">

                <table class="form-table" role="presentation"><tbody>
                    <?php foreach (SocialLinksRepository::PLATFORMS as $p): ?>
                        <?php $iconUrl = $iconsBaseUri . '/' . $p['icon']; ?>
                        <tr>
                            <th scope="row" style="width:14rem;">
                                <label for="oli-social-<?php echo esc_attr($p['id']); ?>" style="display:inline-flex;align-items:center;gap:0.5rem;">
                                    <img src="<?php echo esc_attr($iconUrl); ?>" alt="" width="20" height="20" style="vertical-align:middle;flex:0 0 auto;">
                                    <span><?php echo esc_html($p['label']); ?></span>
                                </label>
                            </th>
                            <td>
                                <input type="text"
                                       id="oli-social-<?php echo esc_attr($p['id']); ?>"
                                       name="oli_social[<?php echo esc_attr($p['id']); ?>]"
                                       value="<?php echo esc_attr($values[$p['id']] ?? ''); ?>"
                                       placeholder="<?php echo esc_attr($p['placeholder']); ?>"
                                       class="regular-text code"
                                       style="max-width:32rem;width:100%;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody></table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function handleSave(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('oli_social_save', '_oli_social_nonce');

        /** @var array<string, mixed> $raw */
        $raw = \is_array($_POST['oli_social'] ?? null) ? $_POST['oli_social'] : [];
        $this->repo->save($raw);

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Réseaux sociaux enregistrés.', 'oli-theme') . '</p></div>';
    }
}
