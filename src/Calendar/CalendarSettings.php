<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

/**
 * DTO immuable des réglages globaux du calendrier de réservation.
 *
 * Source de vérité : option WordPress `oli_calendar_settings`.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final readonly class CalendarSettings
{
    /** Durée minimale acceptée pour un créneau (minutes). */
    public const SLOT_MIN = 30;
    /** Durée maximale acceptée pour un créneau (minutes). */
    public const SLOT_MAX = 240;

    /** Jours ouvrés acceptés (0 = dimanche, 6 = samedi — convention WP). */
    public const DAYS = [0, 1, 2, 3, 4, 5, 6];

    /** État par défaut d'un créneau quand aucune réservation/blocage n'existe. */
    public const STATE_AVAILABLE = 'available';
    /** État inverse : tout est bloqué sauf si Olivier ouvre le créneau. */
    public const STATE_BLOCKED   = 'blocked';

    /**
     * @param int $slotDurationMinutes Durée d'un créneau (60 ou 120 typiquement).
     * @param list<int> $workingDays Jours ouvrés (sous-ensemble de [0..6]).
     * @param string $workingHoursStart Heure de début au format `HH:MM` (24h).
     * @param string $workingHoursEnd Heure de fin au format `HH:MM` (24h).
     * @param string $defaultState `available` ou `blocked`.
     * @param string $notificationEmail Adresse e-mail qui reçoit les nouvelles réservations.
     * @param bool $autoConfirm Réservation directement `confirmed` (true) ou `pending` (false).
     */
    public function __construct(
        public int $slotDurationMinutes = 60,
        public array $workingDays = [1, 2, 3, 4, 5],
        public string $workingHoursStart = '09:00',
        public string $workingHoursEnd = '19:00',
        public string $defaultState = self::STATE_AVAILABLE,
        public string $notificationEmail = '',
        public bool $autoConfirm = false,
    ) {
    }

    /**
     * Valeurs par défaut neutres pour un nouveau site.
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Sanitize une entrée brute (typiquement issue d'un formulaire Settings API).
     *
     * @param array<string, mixed> $input
     */
    public static function fromInput(array $input): self
    {
        $duration = (int) ($input['slotDurationMinutes'] ?? 60);
        $duration = max(self::SLOT_MIN, min(self::SLOT_MAX, $duration));

        $rawDays   = $input['workingDays'] ?? [1, 2, 3, 4, 5];
        $cleanDays = [];
        if (\is_array($rawDays)) {
            foreach ($rawDays as $d) {
                $d = (int) $d;
                if (\in_array($d, self::DAYS, true) && !\in_array($d, $cleanDays, true)) {
                    $cleanDays[] = $d;
                }
            }
        }
        sort($cleanDays);

        $start = self::sanitizeTime((string) ($input['workingHoursStart'] ?? '09:00'), '09:00');
        $end   = self::sanitizeTime((string) ($input['workingHoursEnd']   ?? '19:00'), '19:00');
        if ($end <= $start) {
            $end = $start; // sera traité par SlotGenerator comme « pas de créneaux ».
        }

        $defaultState = (string) ($input['defaultState'] ?? self::STATE_AVAILABLE);
        if (!\in_array($defaultState, [self::STATE_AVAILABLE, self::STATE_BLOCKED], true)) {
            $defaultState = self::STATE_AVAILABLE;
        }

        return new self(
            slotDurationMinutes: $duration,
            workingDays: $cleanDays,
            workingHoursStart: $start,
            workingHoursEnd: $end,
            defaultState: $defaultState,
            notificationEmail: (string) ($input['notificationEmail'] ?? ''),
            autoConfirm: (bool) ($input['autoConfirm'] ?? false),
        );
    }

    /**
     * Valide et reformate une heure HH:MM ; retourne $fallback si invalide.
     */
    private static function sanitizeTime(string $value, string $fallback): string
    {
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]?\d)$/', $value, $m) !== 1) {
            return $fallback;
        }
        return \sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
    }
}
