<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Génère la liste des créneaux d'une semaine selon les réglages du calendrier.
 *
 * Sans persistence : pour un (settings, date début de semaine), produit la
 * liste structurée des créneaux candidats que l'admin peut bloquer/ouvrir
 * et que le frontend peut proposer à la réservation.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final class SlotGenerator
{
    public function __construct(private readonly CalendarSettings $settings)
    {
    }

    /**
     * Retourne les créneaux candidats pour la semaine contenant `$reference`
     * (lundi → dimanche). Chaque créneau est un tuple [start, end] de
     * `DateTimeImmutable` en UTC dans l'ordre chronologique.
     *
     * @return list<array{start: DateTimeImmutable, end: DateTimeImmutable}>
     */
    public function forWeekOf(DateTimeImmutable $reference): array
    {
        // Trouve le lundi de la semaine.
        $monday = $reference->setTime(0, 0, 0);
        $dayOfWeek = (int) $monday->format('N'); // 1 (Mon) .. 7 (Sun)
        if ($dayOfWeek > 1) {
            $monday = $monday->modify('-' . ($dayOfWeek - 1) . ' day');
        }

        $slots = [];
        for ($offset = 0; $offset < 7; ++$offset) {
            $day = $monday->modify('+' . $offset . ' day');
            // Convention WP : 0 = dimanche, 6 = samedi. PHP `format('w')` aligne.
            $wpDow = (int) $day->format('w');
            if (!\in_array($wpDow, $this->settings->workingDays, true)) {
                continue;
            }
            foreach ($this->slotsForDay($day) as $slot) {
                $slots[] = $slot;
            }
        }

        return $slots;
    }

    /**
     * Calcule les créneaux pour un jour donné selon `workingHoursStart`,
     * `workingHoursEnd` et `slotDurationMinutes`.
     *
     * @return list<array{start: DateTimeImmutable, end: DateTimeImmutable}>
     */
    private function slotsForDay(DateTimeImmutable $day): array
    {
        $tz = $day->getTimezone();
        [$startH, $startM] = $this->parseTime($this->settings->workingHoursStart);
        [$endH,   $endM]   = $this->parseTime($this->settings->workingHoursEnd);

        $cursor = $day->setTime($startH, $startM, 0);
        $end    = $day->setTime($endH, $endM, 0);
        $step   = '+' . $this->settings->slotDurationMinutes . ' minutes';

        $slots = [];
        while ($cursor < $end) {
            $next = $cursor->modify($step);
            if ($next > $end) {
                break;
            }
            $slots[] = ['start' => $cursor, 'end' => $next];
            $cursor = $next;
        }

        return $slots;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseTime(string $hhmm): array
    {
        $parts = explode(':', $hhmm);
        return [(int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0)];
    }
}
