<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Frontend;

use OliTheme\Calendar\ServiceRepositoryInterface;

/**
 * Bloc Gutenberg + shortcode pour insérer le widget de réservation
 * dans n'importe quelle page WordPress.
 *
 * Le rendu serveur produit le conteneur HTML + un payload JSON qui sera
 * hydraté par `assets/js/booking-calendar.js`.
 *
 * @package OliTheme\Calendar\Frontend
 *
 * @since 1.3.0
 */
final class BookingBlock
{
    public const BLOCK_NAME = 'oli/booking-calendar';
    public const SHORTCODE  = 'oli_booking_calendar';

    public function __construct(private readonly ServiceRepositoryInterface $services)
    {
    }

    public function register(): void
    {
        if (\function_exists('register_block_type')) {
            register_block_type(self::BLOCK_NAME, [
                'render_callback' => [$this, 'render'],
                'attributes'      => [
                    'serviceId'  => ['type' => 'string', 'default' => ''],
                    'title'      => ['type' => 'string', 'default' => 'Réserver un créneau'],
                ],
            ]);
        }
        if (\function_exists('add_shortcode')) {
            add_shortcode(self::SHORTCODE, [$this, 'renderShortcode']);
        }
    }

    /**
     * @param array<string, mixed>|null $attrs
     */
    public function render(?array $attrs = null): string
    {
        $attrs ??= [];
        $title      = (string) ($attrs['title']     ?? __('Réserver un créneau', 'oli-theme'));
        $preselect  = (string) ($attrs['serviceId'] ?? '');
        $services   = array_map(static fn ($s) => $s->toArray(), $this->services->all());
        $renderedAt = time();

        $payload = wp_json_encode([
            'services'   => $services,
            'preselect'  => $preselect,
            'renderedAt' => $renderedAt,
            'restBase'   => esc_url_raw(rest_url('oli/v1/calendar')),
            'i18n'       => [
                'selectService' => __('Choisir un service', 'oli-theme'),
                'name'          => __('Votre nom', 'oli-theme'),
                'email'         => __('Votre e-mail', 'oli-theme'),
                'phone'         => __('Téléphone (optionnel)', 'oli-theme'),
                'message'       => __('Message (optionnel)', 'oli-theme'),
                'submit'        => __('Réserver', 'oli-theme'),
                'submitting'    => __('Envoi…', 'oli-theme'),
                'success'       => __('Demande envoyée. Vous recevrez un e-mail de confirmation.', 'oli-theme'),
                'error'         => __('Une erreur est survenue. Réessayez plus tard.', 'oli-theme'),
                'noSlots'       => __('Aucun créneau libre cette semaine.', 'oli-theme'),
                'prevWeek'      => __('Semaine précédente', 'oli-theme'),
                'nextWeek'      => __('Semaine suivante', 'oli-theme'),
            ],
        ]);

        $domId = 'oli-booking-' . substr(md5((string) $renderedAt), 0, 6);

        ob_start();
        ?>
        <section class="oli-booking" id="<?php echo esc_attr($domId); ?>" aria-labelledby="<?php echo esc_attr($domId); ?>-title">
            <h2 class="oli-booking__title" id="<?php echo esc_attr($domId); ?>-title"><?php echo esc_html($title); ?></h2>
            <div class="oli-booking__service-picker" data-oli-service-picker></div>
            <div class="oli-booking__week-nav" data-oli-week-nav></div>
            <div class="oli-booking__slots" data-oli-slots aria-live="polite"></div>
            <dialog class="oli-booking__modal" data-oli-modal>
                <form class="oli-booking__form" data-oli-form novalidate>
                    <h3 class="oli-booking__modal-title" data-oli-modal-title></h3>
                    <input type="text" name="website" autocomplete="off" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;">
                    <input type="hidden" name="rendered_at" value="<?php echo esc_attr((string) $renderedAt); ?>">
                    <input type="hidden" name="service_id" data-oli-form-service>
                    <input type="hidden" name="start" data-oli-form-start>
                    <label><span><?php esc_html_e('Votre nom', 'oli-theme'); ?> *</span>
                        <input type="text" name="name" required minlength="2" maxlength="120"></label>
                    <label><span><?php esc_html_e('Votre e-mail', 'oli-theme'); ?> *</span>
                        <input type="email" name="email" required maxlength="200"></label>
                    <label><span><?php esc_html_e('Téléphone (optionnel)', 'oli-theme'); ?></span>
                        <input type="tel" name="phone" maxlength="40"></label>
                    <label><span><?php esc_html_e('Message (optionnel)', 'oli-theme'); ?></span>
                        <textarea name="message" rows="3" maxlength="1000"></textarea></label>
                    <div class="oli-booking__form-actions">
                        <button type="button" data-oli-modal-cancel><?php esc_html_e('Annuler', 'oli-theme'); ?></button>
                        <button type="submit" data-oli-submit><?php esc_html_e('Réserver', 'oli-theme'); ?></button>
                    </div>
                    <p class="oli-booking__form-error" data-oli-form-error role="alert" hidden></p>
                    <p class="oli-booking__form-success" data-oli-form-success role="status" hidden></p>
                </form>
            </dialog>
            <script type="application/json" data-oli-config><?php echo $payload; ?></script>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed>|string $atts
     */
    public function renderShortcode(array|string $atts = []): string
    {
        $atts = \is_array($atts) ? $atts : [];
        return $this->render([
            'serviceId' => (string) ($atts['service'] ?? ''),
            'title'     => (string) ($atts['title']   ?? __('Réserver un créneau', 'oli-theme')),
        ]);
    }
}
