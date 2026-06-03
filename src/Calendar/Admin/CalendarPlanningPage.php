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
            <?php
            // Regroupe les créneaux en grille jours × heures.
            $byKey = [];     // 'YYYY-MM-DD|HH:MM' → slot
            $daysIndex = []; // 'YYYY-MM-DD' → ['label' => 'lun. 02/06', 'iso' => '...']
            $hoursIndex = []; // 'HH:MM' → 'HH:MM → HH:MM'
            foreach ($slots as $slot) {
                $dayKey  = $slot['start']->format('Y-m-d');
                $hourKey = $slot['start']->format('H:i');
                $byKey[$dayKey . '|' . $hourKey] = $slot;
                if (!isset($daysIndex[$dayKey])) {
                    $daysIndex[$dayKey] = [
                        'label' => $this->localizedDayHeader($slot['start']),
                        'iso'   => $dayKey,
                    ];
                }
                if (!isset($hoursIndex[$hourKey])) {
                    $hoursIndex[$hourKey] = $slot['start']->format('H:i') . ' – ' . $slot['end']->format('H:i');
                }
            }
            ksort($daysIndex);
            ksort($hoursIndex);
            $today = (new DateTimeImmutable('now'))->format('Y-m-d');
            ?>
            <div class="oli-planning-legend">
                <span class="oli-planning-pill oli-planning-pill--available"><?php esc_html_e('Libre', 'oli-theme'); ?></span>
                <span class="oli-planning-pill oli-planning-pill--blocked"><?php esc_html_e('Bloqué', 'oli-theme'); ?></span>
                <span class="oli-planning-pill oli-planning-pill--pending"><?php esc_html_e('En attente', 'oli-theme'); ?></span>
                <span class="oli-planning-pill oli-planning-pill--confirmed"><?php esc_html_e('Confirmée', 'oli-theme'); ?></span>
            </div>
            <div class="oli-planning-scroll">
                <table class="oli-planning-week">
                    <thead>
                        <tr>
                            <th class="oli-planning-week__corner" scope="col"><?php esc_html_e('Heure', 'oli-theme'); ?></th>
                            <?php foreach ($daysIndex as $day): ?>
                                <th scope="col" class="oli-planning-week__day-header<?php echo $day['iso'] === $today ? ' is-today' : ''; ?>">
                                    <?php echo esc_html($day['label']); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($hoursIndex as $hourKey => $hourLabel): ?>
                        <tr>
                            <th scope="row" class="oli-planning-week__hour"><?php echo esc_html($hourLabel); ?></th>
                            <?php foreach ($daysIndex as $day):
                                $slot = $byKey[$day['iso'] . '|' . $hourKey] ?? null;
                                if ($slot === null) {
                                    echo '<td class="oli-planning-week__cell oli-planning-week__cell--empty" aria-hidden="true"></td>';
                                    continue;
                                }
                                $state = $this->resolveState($slot, $availabilities, $bookings);
                                $detail = $this->slotDetail($state, $slot, $availabilities, $bookings, $servicesById);
                                $actions = $this->renderActions($state, $slot);
                            ?>
                                <td class="oli-planning-week__cell oli-planning-week__cell--<?php echo esc_attr($state['type']); ?>">
                                    <div class="oli-planning-week__cell-label"><?php echo esc_html($state['label']); ?></div>
                                    <div class="oli-planning-week__cell-detail"><?php echo $detail; // déjà escapé ?></div>
                                    <div class="oli-planning-week__cell-actions"><?php echo $actions; // déjà escapé ?></div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <style>
            .oli-planning-legend { display: flex; gap: 0.5rem; flex-wrap: wrap; margin: 0 0 0.75rem; }
            .oli-planning-pill { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.15rem 0.55rem; border-radius: 12px; font-size: 0.78rem; font-weight: 600; }
            .oli-planning-pill::before { content: ''; width: 0.6rem; height: 0.6rem; border-radius: 50%; }
            .oli-planning-pill--available { background: #e6f7ed; color: #1e7a3f; }
            .oli-planning-pill--available::before { background: #2ecc71; }
            .oli-planning-pill--blocked   { background: #ececec; color: #555; }
            .oli-planning-pill--blocked::before   { background: #888; }
            .oli-planning-pill--pending   { background: #fdf2dd; color: #8a5b00; }
            .oli-planning-pill--pending::before   { background: #f39c12; }
            .oli-planning-pill--confirmed { background: #e0effd; color: #1e5b8d; }
            .oli-planning-pill--confirmed::before { background: #3498db; }

            .oli-planning-scroll { overflow-x: auto; border: 1px solid #dcdcde; border-radius: 6px; background: #fff; }
            .oli-planning-week { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
            .oli-planning-week th, .oli-planning-week td { border-bottom: 1px solid #ececec; border-right: 1px solid #ececec; vertical-align: top; }
            .oli-planning-week th:last-child, .oli-planning-week td:last-child { border-right: 0; }
            .oli-planning-week tbody tr:last-child th, .oli-planning-week tbody tr:last-child td { border-bottom: 0; }

            .oli-planning-week__corner { width: 92px; min-width: 92px; background: #f6f7f7; text-align: left; padding: 0.5rem 0.6rem; font-size: 0.78rem; color: #50575e; position: sticky; left: 0; top: 0; z-index: 3; }
            .oli-planning-week__day-header { background: #f6f7f7; padding: 0.5rem 0.6rem; font-size: 0.85rem; color: #1d2327; text-align: center; position: sticky; top: 0; z-index: 2; }
            .oli-planning-week__day-header.is-today { background: #fef9f1; color: #b85d1f; box-shadow: inset 0 -2px 0 #ef7c33; }
            .oli-planning-week__hour { width: 92px; min-width: 92px; background: #fafafa; padding: 0.4rem 0.6rem; font-size: 0.78rem; color: #50575e; text-align: left; font-weight: 600; position: sticky; left: 0; z-index: 1; }

            .oli-planning-week__cell { padding: 0.45rem 0.5rem; font-size: 0.82rem; min-height: 70px; transition: background-color 120ms ease, box-shadow 120ms ease; }
            .oli-planning-week__cell--empty { background: repeating-linear-gradient(45deg, #fff 0 6px, #f8f8f8 6px 12px); }
            .oli-planning-week__cell--available { background: #ecfaf1; }
            .oli-planning-week__cell--blocked   { background: #efefef; color: #777; }
            .oli-planning-week__cell--pending   { background: #fef4e0; }
            .oli-planning-week__cell--confirmed { background: #e6f0fa; }
            .oli-planning-week__cell--available:hover,
            .oli-planning-week__cell--pending:hover,
            .oli-planning-week__cell--confirmed:hover { box-shadow: inset 0 0 0 2px rgba(0,0,0,0.08); }

            .oli-planning-week__cell-label { font-weight: 600; font-size: 0.78rem; margin-bottom: 0.2rem; }
            .oli-planning-week__cell-detail { font-size: 0.78rem; line-height: 1.35; color: #50575e; margin-bottom: 0.35rem; word-wrap: break-word; }
            .oli-planning-week__cell-actions { display: flex; flex-direction: column; gap: 0.2rem; }
            .oli-planning-week__cell-actions .button,
            .oli-planning-week__cell-actions form { width: 100%; }
            .oli-planning-week__cell-actions .button { font-size: 0.72rem; padding: 0.15rem 0.45rem; min-height: 0; line-height: 1.4; }
            .oli-planning-week__cell-actions form { display: block; margin: 0; }
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

    /**
     * Libellé de jour de semaine en français (et anglais en repli) sans
     * dépendre de l'extension intl. Format : « lun. 02/06 ».
     */
    private function localizedDayHeader(DateTimeImmutable $day): string
    {
        static $labels = [
            1 => 'lun.', 2 => 'mar.', 3 => 'mer.', 4 => 'jeu.',
            5 => 'ven.', 6 => 'sam.', 7 => 'dim.',
        ];
        $dow = (int) $day->format('N');
        return ($labels[$dow] ?? $day->format('D')) . ' ' . $day->format('d/m');
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
