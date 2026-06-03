<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Admin;

use OliTheme\Admin\AdminTabInterface;
use OliTheme\Calendar\Service;
use OliTheme\Calendar\ServiceRepository;

/**
 * Sous-onglet « Services réservables » — CRUD des services (cours, massages).
 *
 * @package OliTheme\Calendar\Admin
 *
 * @since 1.3.0
 */
final class ServicesAdminPage implements AdminTabInterface
{
    public const ACTION_SAVE   = 'oli_calendar_service_save';
    public const ACTION_DELETE = 'oli_calendar_service_delete';

    public function __construct(private readonly ServiceRepository $repo)
    {
    }

    public function id(): string
    {
        return 'services';
    }

    public function group(): string
    {
        return 'calendrier';
    }

    public function label(): string
    {
        return __('Services', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    public function renderPanel(): void
    {
        $services = $this->repo->all();
        $admin    = admin_url('admin-post.php');
        $back     = add_query_arg(['page' => 'oli-theme-settings', 'tab' => 'calendrier', 'sub' => 'services'], admin_url('themes.php'));

        echo '<p>' . esc_html__('Définissez ici les prestations qu\'Olivier propose à la réservation (cours, massages, etc.). Chaque service a une durée fixe utilisée pour calculer le nombre de créneaux occupés.', 'oli-theme') . '</p>';

        if (!empty($services)) {
            ?>
            <table class="widefat striped" style="margin-bottom:1.5rem;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Libellé FR', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Libellé EN', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Durée', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Prix', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Action', 'oli-theme'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($services as $svc): ?>
                    <tr>
                        <td><?php echo esc_html($svc->labelFr); ?> <code><?php echo esc_html($svc->id); ?></code></td>
                        <td><?php echo esc_html($svc->labelEn); ?></td>
                        <td><?php echo esc_html((string) $svc->durationMinutes); ?> min</td>
                        <td>
                            <?php
                            if ($svc->priceCents !== null) {
                                echo esc_html(number_format($svc->priceCents / 100, 2, ',', ' ')) . ' €';
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td>
                            <form method="post" action="<?php echo esc_url($admin); ?>" style="display:inline;"
                                  onsubmit="return confirm('<?php echo esc_js(__('Supprimer ce service ?', 'oli-theme')); ?>');">
                                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_DELETE); ?>">
                                <input type="hidden" name="service_id" value="<?php echo esc_attr($svc->id); ?>">
                                <input type="hidden" name="_redirect" value="<?php echo esc_url($back); ?>">
                                <?php wp_nonce_field(self::ACTION_DELETE); ?>
                                <button type="submit" class="button-link-delete"><?php esc_html_e('Supprimer', 'oli-theme'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }

        ?>
        <h3 style="margin-top:1.5rem;"><?php esc_html_e('Ajouter / modifier un service', 'oli-theme'); ?></h3>
        <form method="post" action="<?php echo esc_url($admin); ?>" class="oli-calendar-service-form">
            <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_SAVE); ?>">
            <input type="hidden" name="_redirect" value="<?php echo esc_url($back); ?>">
            <?php wp_nonce_field(self::ACTION_SAVE); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="svc-id"><?php esc_html_e('Identifiant (vide = auto)', 'oli-theme'); ?></label></th>
                    <td><input type="text" id="svc-id" name="id" class="regular-text" placeholder="ex. massage-1h"></td>
                </tr>
                <tr>
                    <th><label for="svc-fr"><?php esc_html_e('Libellé FR', 'oli-theme'); ?></label></th>
                    <td><input type="text" id="svc-fr" name="labelFr" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="svc-en"><?php esc_html_e('Libellé EN', 'oli-theme'); ?></label></th>
                    <td><input type="text" id="svc-en" name="labelEn" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="svc-duration"><?php esc_html_e('Durée (minutes)', 'oli-theme'); ?></label></th>
                    <td><input type="number" id="svc-duration" name="durationMinutes" value="60" min="15" max="480" step="15" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="svc-price"><?php esc_html_e('Prix en centimes (optionnel)', 'oli-theme'); ?></label></th>
                    <td><input type="number" id="svc-price" name="priceCents" placeholder="ex. 6000 = 60 €" min="0" step="100" class="regular-text">
                        <p class="description"><?php esc_html_e('Laissez vide pour ne pas afficher de prix.', 'oli-theme'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="svc-desc-fr"><?php esc_html_e('Description FR', 'oli-theme'); ?></label></th>
                    <td><textarea id="svc-desc-fr" name="descriptionFr" rows="3" cols="50"></textarea></td>
                </tr>
                <tr>
                    <th><label for="svc-desc-en"><?php esc_html_e('Description EN', 'oli-theme'); ?></label></th>
                    <td><textarea id="svc-desc-en" name="descriptionEn" rows="3" cols="50"></textarea></td>
                </tr>
            </table>
            <?php submit_button(__('Enregistrer le service', 'oli-theme')); ?>
        </form>
        <?php
    }

    public static function handleSave(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
        check_admin_referer(self::ACTION_SAVE);

        $repo = new ServiceRepository();
        $price = $_POST['priceCents'] ?? '';
        $repo->save(new Service(
            id:              sanitize_text_field((string) ($_POST['id'] ?? '')),
            labelFr:         sanitize_text_field((string) ($_POST['labelFr'] ?? '')),
            labelEn:         sanitize_text_field((string) ($_POST['labelEn'] ?? '')),
            durationMinutes: (int) ($_POST['durationMinutes'] ?? 60),
            descriptionFr:   wp_kses_post((string) ($_POST['descriptionFr'] ?? '')),
            descriptionEn:   wp_kses_post((string) ($_POST['descriptionEn'] ?? '')),
            priceCents:      $price === '' ? null : (int) $price,
        ));

        self::redirectBack(true);
    }

    public static function handleDelete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
        check_admin_referer(self::ACTION_DELETE);

        $id = sanitize_text_field((string) ($_POST['service_id'] ?? ''));
        if ($id !== '') {
            (new ServiceRepository())->delete($id);
        }
        self::redirectBack(true);
    }

    private static function redirectBack(bool $saved): void
    {
        $redirect = isset($_POST['_redirect']) && \is_string($_POST['_redirect'])
            ? esc_url_raw((string) $_POST['_redirect'])
            : admin_url('themes.php?page=oli-theme-settings&tab=calendrier&sub=services');
        wp_safe_redirect(add_query_arg($saved ? 'oli_saved' : 'oli_deleted', '1', $redirect));
        exit;
    }
}
