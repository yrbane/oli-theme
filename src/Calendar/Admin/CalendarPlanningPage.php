<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Admin;

use DateTimeImmutable;
use DateTimeZone;
use OliTheme\Admin\AdminTabInterface;
use OliTheme\Calendar\Availability;
use OliTheme\Calendar\AvailabilityRepository;
use OliTheme\Calendar\Booking;
use OliTheme\Calendar\BookingRepository;
use OliTheme\Calendar\BookingStatus;
use OliTheme\Calendar\CalendarSettings;
use OliTheme\Calendar\Service;
use OliTheme\Calendar\ServiceRepository;
use OliTheme\Calendar\SlotGenerator;
use OliTheme\Calendar\SlotState;

/**
 * Sous-onglet « Planning » — vue grille hebdomadaire interactive du calendrier.
 *
 * Affiche les créneaux candidats (générés par {@see SlotGenerator}) croisés
 * avec les indisponibilités et les réservations actives. Permet à Olivier de
 * bloquer/libérer un créneau ou de gérer une réservation directement depuis
 * la grille.
 *
 * @package OliTheme\Calendar\Admin
 *
 * @since 1.3.0
 */
final class CalendarPlanningPage implements AdminTabInterface
{
    public const ACTION_BLOCK     = 'oli_calendar_block_slot';
    public const ACTION_UNBLOCK   = 'oli_calendar_unblock_slot';
    public const ACTION_CONFIRM   = 'oli_calendar_booking_confirm';
    public const ACTION_CANCEL    = 'oli_calendar_booking_cancel';

    public function __construct(
        private readonly CalendarSettings $settings,
        private readonly SlotGenerator $generator,
        private readonly AvailabilityRepository $availability,
        private readonly BookingRepository $bookings,
        private readonly ServiceRepository $services,
    ) {
    }

    public function id(): string
    {
        return 'planning';
    }

    public function group(): string
    {
        return 'calendrier';
    }

    public function label(): string
    {
        return __('Planning', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    public function renderPanel(): void
    {
        $reference = $this->parseWeekRef($_GET['week'] ?? '');
        $monday    = $this->mondayOf($reference);
        $sunday    = $monday->modify('+7 days')->setTime(0, 0, 0);

        $slots         = $this->generator->forWeekOf($reference);
        $availabilities = $this->availability->findInRange($monday, $sunday);
        $bookings       = $this->bookings->findActiveInRange($monday, $sunday);
        $services       = $this->services->all();
        $servicesById   = [];
        foreach ($services as $svc) {
            $servicesById[$svc->id] = $svc;
        }

        $prevWeek = $monday->modify('-7 days');
        $nextWeek = $monday->modify('+7 days');
        $base     = add_query_arg(['page' => 'oli-theme-settings', 'tab' => 'calendrier', 'sub' => 'planning'], admin_url('themes.php'));

        ?>
        <div class="oli-planning-nav" style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem;">
            <a class="button" href="<?php echo esc_url(add_query_arg('week', $prevWeek->format('Y-m-d'), $base)); ?>">&larr; <?php esc_html_e('Semaine précédente', 'oli-theme'); ?></a>
            <strong style="flex:1;text-align:center;">
                <?php echo esc_html(sprintf(
                    /* translators: %1$s = monday date, %2$s = sunday date */
                    __('Semaine du %1$s au %2$s', 'oli-theme'),
                    $monday->format('d/m/Y'),
                    $monday->modify('+6 days')->format('d/m/Y'),
                )); ?>
            </strong>
            <a class="button" href="<?php echo esc_url(add_query_arg('week', $nextWeek->format('Y-m-d'), $base)); ?>"><?php esc_html_e('Semaine suivante', 'oli-theme'); ?> &rarr;</a>
            <a class="button button-secondary" href="<?php echo esc_url(remove_query_arg('week', $base)); ?>"><?php esc_html_e('Cette semaine', 'oli-theme'); ?></a>
        </div>

        <?php if (empty($slots)): ?>
            <div class="notice notice-warning inline"><p><?php esc_html_e('Aucun créneau généré pour cette semaine. Vérifiez les jours ouvrés et la plage horaire dans Réglages.', 'oli-theme'); ?></p></div>
        <?php else: ?>
            <table class="widefat oli-planning-grid">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Heure', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('État', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Détail', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Action', 'oli-theme'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($slots as $slot):
                    $state  = $this->resolveState($slot, $availabilities, $bookings);
                    $detail = $this->slotDetail($state, $slot, $availabilities, $bookings, $servicesById);
                ?>
                    <tr class="oli-planning-row oli-planning-row--<?php echo esc_attr($state['type']); ?>">
                        <td><?php echo esc_html($slot['start']->format('D d/m')); ?></td>
                        <td><?php echo esc_html($slot['start']->format('H:i') . ' → ' . $slot['end']->format('H:i')); ?></td>
                        <td><?php echo esc_html($state['label']); ?></td>
                        <td><?php echo $detail; // déjà escapé ?></td>
                        <td><?php echo $this->renderActions($state, $slot); // déjà escapé ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <style>
            .oli-planning-grid tr.oli-planning-row--available { background: rgba(46, 204, 113, 0.08); }
            .oli-planning-grid tr.oli-planning-row--blocked   { background: rgba(0, 0, 0, 0.05); color: #777; }
            .oli-planning-grid tr.oli-planning-row--pending   { background: rgba(243, 156, 18, 0.12); }
            .oli-planning-grid tr.oli-planning-row--confirmed { background: rgba(52, 152, 219, 0.12); }
        </style>
        <?php
    }

    /**
     * @param array{start: DateTimeImmutable, end: DateTimeImmutable} $slot
     * @param list<Availability> $availabilities
     * @param list<Booking> $bookings
     *
     * @return array{type:string,label:string,booking?:Booking,availability?:Availability}
     */
    private function resolveState(array $slot, array $availabilities, array $bookings): array
    {
        foreach ($bookings as $b) {
            if ($b->start < $slot['end'] && $b->end > $slot['start']) {
                $type = $b->status === BookingStatus::Pending ? 'pending' : 'confirmed';
                return [
                    'type'    => $type,
                    'label'   => $b->status === BookingStatus::Pending
                        ? __('Réservation en attente', 'oli-theme')
                        : __('Réservation confirmée', 'oli-theme'),
                    'booking' => $b,
                ];
            }
        }
        foreach ($availabilities as $a) {
            if ($a->overlaps($slot['start'], $slot['end'])) {
                return [
                    'type'         => 'blocked',
                    'label'        => $a->isImported() ? __('Bloqué (synchro externe)', 'oli-theme') : __('Bloqué', 'oli-theme'),
                    'availability' => $a,
                ];
            }
        }
        return ['type' => 'available', 'label' => __('Libre', 'oli-theme')];
    }

    /**
     * @param array<string, mixed> $state
     * @param array{start: DateTimeImmutable, end: DateTimeImmutable} $slot
     * @param list<Availability> $availabilities
     * @param list<Booking> $bookings
     * @param array<string, Service> $servicesById
     */
    private function slotDetail(array $state, array $slot, array $availabilities, array $bookings, array $servicesById): string
    {
        if (isset($state['booking']) && $state['booking'] instanceof Booking) {
            $b = $state['booking'];
            $service = $servicesById[$b->serviceId] ?? null;
            $serviceLabel = $service !== null ? $service->labelFr : $b->serviceId;
            return sprintf(
                '<strong>%s</strong><br><small>%s &lt;%s&gt;</small>',
                esc_html($serviceLabel),
                esc_html($b->customerName),
                esc_html($b->customerEmail),
            );
        }
        if (isset($state['availability']) && $state['availability'] instanceof Availability) {
            return esc_html($state['availability']->title);
        }
        return '<em>' . esc_html__('Libre à la réservation', 'oli-theme') . '</em>';
    }

    /**
     * @param array<string, mixed> $state
     * @param array{start: DateTimeImmutable, end: DateTimeImmutable} $slot
     */
    private function renderActions(array $state, array $slot): string
    {
        $admin = admin_url('admin-post.php');
        $back  = add_query_arg(
            ['page' => 'oli-theme-settings', 'tab' => 'calendrier', 'sub' => 'planning', 'week' => $slot['start']->format('Y-m-d')],
            admin_url('themes.php'),
        );

        if ($state['type'] === 'available') {
            return $this->renderForm(self::ACTION_BLOCK, [
                'start'    => $slot['start']->getTimestamp(),
                'end'      => $slot['end']->getTimestamp(),
                '_redirect' => $back,
            ], __('Bloquer', 'oli-theme'), 'button-secondary');
        }
        if ($state['type'] === 'blocked' && isset($state['availability'])) {
            $a = $state['availability'];
            if ($a->isImported()) {
                return '<em>' . esc_html__('Importé (non éditable)', 'oli-theme') . '</em>';
            }
            return $this->renderForm(self::ACTION_UNBLOCK, [
                'availability_id' => $a->id,
                '_redirect'       => $back,
            ], __('Libérer', 'oli-theme'), 'button-secondary');
        }
        if ($state['type'] === 'pending' && isset($state['booking'])) {
            $b = $state['booking'];
            $confirm = $this->renderForm(self::ACTION_CONFIRM, ['booking_id' => $b->id, '_redirect' => $back], __('Confirmer', 'oli-theme'), 'button-primary');
            $cancel  = $this->renderForm(self::ACTION_CANCEL,  ['booking_id' => $b->id, '_redirect' => $back], __('Annuler', 'oli-theme'), 'button-link-delete');
            return $confirm . ' ' . $cancel;
        }
        if ($state['type'] === 'confirmed' && isset($state['booking'])) {
            return $this->renderForm(self::ACTION_CANCEL, ['booking_id' => $state['booking']->id, '_redirect' => $back], __('Annuler', 'oli-theme'), 'button-link-delete');
        }
        return '';
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function renderForm(string $action, array $fields, string $label, string $class): string
    {
        $html  = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline;">';
        $html .= '<input type="hidden" name="action" value="' . esc_attr($action) . '">';
        $html .= wp_nonce_field($action, '_wpnonce', true, false);
        foreach ($fields as $key => $value) {
            $html .= '<input type="hidden" name="' . esc_attr((string) $key) . '" value="' . esc_attr((string) $value) . '">';
        }
        $html .= '<button type="submit" class="button ' . esc_attr($class) . '">' . esc_html($label) . '</button>';
        $html .= '</form>';
        return $html;
    }

    private function parseWeekRef(string $raw): DateTimeImmutable
    {
        $tz = new DateTimeZone('UTC');
        if ($raw === '') {
            return new DateTimeImmutable('now', $tz);
        }
        try {
            return (new DateTimeImmutable($raw, $tz))->setTime(0, 0, 0);
        } catch (\Exception) {
            return new DateTimeImmutable('now', $tz);
        }
    }

    private function mondayOf(DateTimeImmutable $reference): DateTimeImmutable
    {
        $day = $reference->setTime(0, 0, 0);
        $dow = (int) $day->format('N');
        return $dow > 1 ? $day->modify('-' . ($dow - 1) . ' day') : $day;
    }

    // ------------------------------------------------------------------
    // Action handlers (branchés via admin-post.php).
    // ------------------------------------------------------------------

    public static function handleBlock(): void
    {
        self::ensureCapability();
        check_admin_referer(self::ACTION_BLOCK);
        $start = (int) ($_POST['start'] ?? 0);
        $end   = (int) ($_POST['end']   ?? 0);
        if ($start > 0 && $end > $start) {
            $tz = new DateTimeZone('UTC');
            (new AvailabilityRepository())->save(new Availability(
                null,
                (new DateTimeImmutable('@' . $start))->setTimezone($tz),
                (new DateTimeImmutable('@' . $end))->setTimezone($tz),
                type:   Availability::TYPE_BLOCKED,
                source: Availability::SOURCE_MANUAL,
            ));
        }
        self::redirect();
    }

    public static function handleUnblock(): void
    {
        self::ensureCapability();
        check_admin_referer(self::ACTION_UNBLOCK);
        $id = (int) ($_POST['availability_id'] ?? 0);
        if ($id > 0) {
            (new AvailabilityRepository())->delete($id);
        }
        self::redirect();
    }

    public static function handleConfirm(): void
    {
        self::ensureCapability();
        check_admin_referer(self::ACTION_CONFIRM);
        $id = (int) ($_POST['booking_id'] ?? 0);
        if ($id > 0) {
            (new BookingRepository())->setStatus($id, BookingStatus::Confirmed);
        }
        self::redirect();
    }

    public static function handleCancel(): void
    {
        self::ensureCapability();
        check_admin_referer(self::ACTION_CANCEL);
        $id = (int) ($_POST['booking_id'] ?? 0);
        if ($id > 0) {
            (new BookingRepository())->setStatus($id, BookingStatus::Cancelled);
        }
        self::redirect();
    }

    private static function ensureCapability(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
    }

    private static function redirect(): void
    {
        $redirect = isset($_POST['_redirect']) && \is_string($_POST['_redirect'])
            ? esc_url_raw((string) $_POST['_redirect'])
            : admin_url('themes.php?page=oli-theme-settings&tab=calendrier');
        wp_safe_redirect(add_query_arg('oli_saved', '1', $redirect));
        exit;
    }
}
