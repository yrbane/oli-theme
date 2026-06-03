<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

/**
 * Statut d'une réservation.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function isActive(): bool
    {
        return $this === self::Pending || $this === self::Confirmed;
    }
}
