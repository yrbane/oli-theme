<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Admin;

use OliTheme\Admin\AdminTabInterface;
use OliTheme\Calendar\BookingRepository;
use OliTheme\Calendar\BookingStatus;
use OliTheme\Calendar\ServiceRepository;

/**
 * Sous-onglet « Réservations » — listing récent avec filtre par statut.
 *
 * @package OliTheme\Calendar\Admin
 *
 * @since 1.3.0
 */
final class BookingsListAdminPage implements AdminTabInterface
{
    public function __construct(
        private readonly BookingRepository $bookings,
        private readonly ServiceRepository $services,
    ) {
    }

    public function id(): string
    {
        return 'reservations';
    }

    public function group(): string
    {
        return 'calendrier';
    }

    public function label(): string
    {
        return __('Réservations', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    public function renderPanel(): void
    {
        $statusFilter = isset($_GET['status']) && \is_string($_GET['status'])
            ? BookingStatus::tryFrom(sanitize_key((string) $_GET['status']))
            : null;
        $items    = $this->bookings->recent($statusFilter !== null ? ['status' => $statusFilter, 'limit' => 100] : ['limit' => 100]);
        $services = [];
        foreach ($this->services->all() as $svc) {
            $services[$svc->id] = $svc;
        }
        $base = add_query_arg(['page' => 'oli-theme-settings', 'tab' => 'calendrier', 'sub' => 'reservations'], admin_url('themes.php'));

        ?>
        <p>
            <strong><?php esc_html_e('Filtrer :', 'oli-theme'); ?></strong>
            <a class="<?php echo $statusFilter === null ? 'button-primary' : ''; ?> button" href="<?php echo esc_url($base); ?>"><?php esc_html_e('Toutes', 'oli-theme'); ?></a>
            <?php foreach ([BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Cancelled] as $s): ?>
                <a class="<?php echo $statusFilter === $s ? 'button-primary' : ''; ?> button"
                   href="<?php echo esc_url(add_query_arg('status', $s->value, $base)); ?>">
                    <?php echo esc_html($this->statusLabel($s)); ?>
                </a>
            <?php endforeach; ?>
        </p>

        <?php if (empty($items)): ?>
            <p><em><?php esc_html_e('Aucune réservation dans cette catégorie.', 'oli-theme'); ?></em></p>
        <?php else: ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Service', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Client', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Statut', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Langue', 'oli-theme'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $b):
                    $service = $services[$b->serviceId] ?? null;
                ?>
                    <tr>
                        <td><?php echo esc_html($b->start->format('d/m/Y H:i') . ' → ' . $b->end->format('H:i')); ?></td>
                        <td><?php echo esc_html($service !== null ? $service->labelFr : $b->serviceId); ?></td>
                        <td>
                            <?php echo esc_html($b->customerName); ?><br>
                            <small><a href="mailto:<?php echo esc_attr($b->customerEmail); ?>"><?php echo esc_html($b->customerEmail); ?></a></small>
                            <?php if ($b->customerPhone !== ''): ?>
                                <br><small><?php echo esc_html($b->customerPhone); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($this->statusLabel($b->status)); ?></td>
                        <td><?php echo esc_html(strtoupper($b->language)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif;
    }

    private function statusLabel(BookingStatus $status): string
    {
        return match ($status) {
            BookingStatus::Pending   => __('En attente', 'oli-theme'),
            BookingStatus::Confirmed => __('Confirmée', 'oli-theme'),
            BookingStatus::Cancelled => __('Annulée', 'oli-theme'),
        };
    }
}
