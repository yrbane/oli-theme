<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Admin;

use OliTheme\Admin\AdminTabInterface;
use OliTheme\Calendar\CalendarSettings;

/**
 * Sous-onglet « Réglages » du groupe Calendrier.
 *
 * Expose les 6 réglages globaux du calendrier (durée des créneaux, jours
 * ouvrés, plage horaire, état par défaut, email de notification, auto-
 * confirmation) via un formulaire admin-post natif.
 *
 * @package OliTheme\Calendar\Admin
 *
 * @since 1.3.0
 */
final class CalendarSettingsAdminPage implements AdminTabInterface
{
    public const ACTION = 'oli_calendar_settings_save';

    public function id(): string
    {
        return 'reglages';
    }

    public function group(): string
    {
        return 'calendrier';
    }

    public function label(): string
    {
        return __('Réglages', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    public function renderPanel(): void
    {
        $raw = (array) get_option('oli_calendar_settings', []);
        $s   = CalendarSettings::fromInput($raw);
        $admin = admin_url('admin-post.php');
        $back  = add_query_arg(['page' => 'oli-theme-settings', 'tab' => 'calendrier', 'sub' => 'reglages'], admin_url('themes.php'));

        ?>
        <form method="post" action="<?php echo esc_url($admin); ?>" class="oli-calendar-settings-form">
            <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>">
            <?php wp_nonce_field(self::ACTION); ?>
            <input type="hidden" name="_redirect" value="<?php echo esc_url($back); ?>">

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="oli-cal-duration"><?php esc_html_e('Durée d\'un créneau (minutes)', 'oli-theme'); ?></label></th>
                    <td>
                        <input type="number" id="oli-cal-duration" name="slotDurationMinutes"
                               value="<?php echo esc_attr((string) $s->slotDurationMinutes); ?>"
                               min="<?php echo esc_attr((string) CalendarSettings::SLOT_MIN); ?>"
                               max="<?php echo esc_attr((string) CalendarSettings::SLOT_MAX); ?>"
                               step="15" class="small-text">
                        <p class="description"><?php esc_html_e('Typiquement 60 ou 120 minutes.', 'oli-theme'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Jours ouvrés', 'oli-theme'); ?></th>
                    <td>
                        <?php
                        $dayLabels = [0 => 'Dimanche', 1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];
                        foreach ($dayLabels as $d => $label) {
                            $checked = \in_array($d, $s->workingDays, true) ? 'checked' : '';
                            printf(
                                '<label style="display:inline-flex;align-items:center;margin-right:0.75rem;"><input type="checkbox" name="workingDays[]" value="%d" %s> %s</label>',
                                $d,
                                $checked,
                                esc_html($label),
                            );
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="oli-cal-start"><?php esc_html_e('Heure d\'ouverture', 'oli-theme'); ?></label></th>
                    <td><input type="time" id="oli-cal-start" name="workingHoursStart" value="<?php echo esc_attr($s->workingHoursStart); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="oli-cal-end"><?php esc_html_e('Heure de fermeture', 'oli-theme'); ?></label></th>
                    <td><input type="time" id="oli-cal-end" name="workingHoursEnd" value="<?php echo esc_attr($s->workingHoursEnd); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('État par défaut d\'un créneau', 'oli-theme'); ?></th>
                    <td>
                        <label style="margin-right:1rem;">
                            <input type="radio" name="defaultState" value="<?php echo esc_attr(CalendarSettings::STATE_AVAILABLE); ?>"
                                <?php checked($s->defaultState, CalendarSettings::STATE_AVAILABLE); ?>>
                            <?php esc_html_e('Ouvert à la réservation', 'oli-theme'); ?>
                        </label>
                        <label>
                            <input type="radio" name="defaultState" value="<?php echo esc_attr(CalendarSettings::STATE_BLOCKED); ?>"
                                <?php checked($s->defaultState, CalendarSettings::STATE_BLOCKED); ?>>
                            <?php esc_html_e('Bloqué (à ouvrir manuellement)', 'oli-theme'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="oli-cal-email"><?php esc_html_e('E-mail de notification', 'oli-theme'); ?></label></th>
                    <td>
                        <input type="email" id="oli-cal-email" name="notificationEmail"
                               value="<?php echo esc_attr($s->notificationEmail); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Reçoit les nouvelles réservations. Laisser vide pour utiliser l\'email admin du site.', 'oli-theme'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Confirmation automatique', 'oli-theme'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="autoConfirm" value="1" <?php checked($s->autoConfirm); ?>>
                            <?php esc_html_e('Les nouvelles réservations sont directement confirmées (sinon : statut « en attente »).', 'oli-theme'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Enregistrer les réglages', 'oli-theme')); ?>
        </form>
        <?php
    }

    /**
     * Handler `admin-post.php` (action `oli_calendar_settings_save`).
     */
    public static function handleSave(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
        check_admin_referer(self::ACTION);

        $clean = CalendarSettings::fromInput([
            'slotDurationMinutes' => (int) ($_POST['slotDurationMinutes'] ?? 60),
            'workingDays'         => (array) ($_POST['workingDays'] ?? []),
            'workingHoursStart'   => (string) ($_POST['workingHoursStart'] ?? '09:00'),
            'workingHoursEnd'     => (string) ($_POST['workingHoursEnd'] ?? '19:00'),
            'defaultState'        => (string) ($_POST['defaultState'] ?? CalendarSettings::STATE_AVAILABLE),
            'notificationEmail'   => sanitize_email((string) ($_POST['notificationEmail'] ?? '')),
            'autoConfirm'         => !empty($_POST['autoConfirm']),
        ]);

        update_option('oli_calendar_settings', [
            'slotDurationMinutes' => $clean->slotDurationMinutes,
            'workingDays'         => $clean->workingDays,
            'workingHoursStart'   => $clean->workingHoursStart,
            'workingHoursEnd'     => $clean->workingHoursEnd,
            'defaultState'        => $clean->defaultState,
            'notificationEmail'   => $clean->notificationEmail,
            'autoConfirm'         => $clean->autoConfirm,
        ]);

        $redirect = isset($_POST['_redirect']) && \is_string($_POST['_redirect'])
            ? esc_url_raw((string) $_POST['_redirect'])
            : admin_url('themes.php?page=oli-theme-settings&tab=calendrier');
        wp_safe_redirect(add_query_arg('oli_saved', '1', $redirect));
        exit;
    }
}
