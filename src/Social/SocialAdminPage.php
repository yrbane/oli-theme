<?php

declare(strict_types=1);

namespace OliTheme\Social;

use OliTheme\Admin\AdminTabInterface;

/**
 * Onglet « Réseaux sociaux » de la page d'administration unifiée du thème.
 *
 * Liste les plateformes supportées avec leur icône à côté du champ URL.
 *
 * @package OliTheme\Social
 *
 * @since 1.0.0
 */
final class SocialAdminPage implements AdminTabInterface
{
    public function __construct(private readonly SocialLinksRepository $repo)
    {
    }

    public function id(): string
    {
        return 'social';
    }

    public function group(): string
    {
        return 'identite';
    }

    public function label(): string
    {
        return __('Réseaux sociaux', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    public function renderPanel(): void
    {
        if (!empty($_POST['oli_social_save'])) {
            $this->handleSave();
        }

        $values = $this->repo->all();
        $iconsBaseUri = \function_exists('get_template_directory_uri')
            ? rtrim((string) get_template_directory_uri(), '/') . '/assets/img/icons/social'
            : '/assets/img/icons/social';

        ?>
        <div class="notice notice-info inline" style="margin:1rem 0;padding:0.75rem 1rem;">
            <p style="margin:0 0 0.5rem;"><strong><?php esc_html_e('Comment ça apparaît sur le site', 'oli-theme'); ?></strong></p>
            <ul style="margin:0 0 0 1.25rem;list-style:disc;line-height:1.6;">
                <li><?php esc_html_e('Les icônes des réseaux sociaux renseignés s\'affichent automatiquement dans le pied de page de toutes les pages du site.', 'oli-theme'); ?></li>
                <li><?php esc_html_e('Les plateformes laissées vides ne sont PAS affichées (pas d\'icône grisée ou inactive).', 'oli-theme'); ?></li>
                <li><?php esc_html_e('Pour retirer un réseau du widget, il suffit d\'effacer son URL et d\'enregistrer.', 'oli-theme'); ?></li>
            </ul>
        </div>

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
