<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

use DateTimeImmutable;

/**
 * Réservation publique posée par un visiteur via le widget.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final readonly class Booking
{
    public function __construct(
        public ?int $id,
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
        public string $serviceId,
        public string $customerName,
        public string $customerEmail,
        public BookingStatus $status,
        public string $customerPhone = '',
        public string $message = '',
        public string $language = 'fr',
    ) {
        if ($end <= $start) {
            throw new \InvalidArgumentException('Booking end must be strictly after start.');
        }
        if ($customerName === '') {
            throw new \InvalidArgumentException('Booking customer name is required.');
        }
        if ($customerEmail === '') {
            throw new \InvalidArgumentException('Booking customer email is required.');
        }
    }
}
