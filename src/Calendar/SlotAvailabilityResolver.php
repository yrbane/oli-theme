<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

use DateTimeImmutable;

/**
 * Croise les créneaux générés par {@see SlotGenerator} avec les
 * {@see Availability} et {@see Booking} actifs pour produire la liste
 * **réellement libres** côté frontend.
 *
 * Cette classe ne touche pas WordPress et est entièrement testable en
 * unit avec des fixtures.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final class SlotAvailabilityResolver
{
    /**
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable}> $candidateSlots
     * @param list<Availability> $availabilities
     * @param list<Booking> $bookings
     *
     * @return list<array{start: DateTimeImmutable, end: DateTimeImmutable}>
     */
    public function freeSlots(array $candidateSlots, array $availabilities, array $bookings): array
    {
        $free = [];
        foreach ($candidateSlots as $slot) {
            if ($this->isBooked($slot, $bookings)) {
                continue;
            }
            if ($this->isBlocked($slot, $availabilities)) {
                continue;
            }
            $free[] = $slot;
        }

        return $free;
    }

    /**
     * Vrai si une réservation active occupe le créneau.
     *
     * @param array{start: DateTimeImmutable, end: DateTimeImmutable} $slot
     * @param list<Booking> $bookings
     */
    private function isBooked(array $slot, array $bookings): bool
    {
        foreach ($bookings as $b) {
            if (!$b->status->isActive()) {
                continue;
            }
            if ($b->start < $slot['end'] && $b->end > $slot['start']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vrai si une indisponibilité chevauche le créneau.
     *
     * @param array{start: DateTimeImmutable, end: DateTimeImmutable} $slot
     * @param list<Availability> $availabilities
     */
    private function isBlocked(array $slot, array $availabilities): bool
    {
        foreach ($availabilities as $a) {
            if ($a->overlaps($slot['start'], $slot['end'])) {
                return true;
            }
        }
        return false;
    }
}
