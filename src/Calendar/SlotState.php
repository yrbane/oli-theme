<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

/**
 * États possibles d'un créneau du calendrier.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
enum SlotState: string
{
    /** Créneau libre, ouvert à la réservation. */
    case Available = 'available';
    /** Créneau bloqué par Olivier (indisponibilité ponctuelle). */
    case Blocked = 'blocked';
    /** Créneau occupé par une réservation (pending ou confirmed). */
    case Booked = 'booked';

    /**
     * Vrai si l'état empêche une nouvelle réservation côté front.
     */
    public function isReservable(): bool
    {
        return $this === self::Available;
    }
}
