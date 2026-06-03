<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

use DateTimeImmutable;

/**
 * Créneau d'indisponibilité ou événement personnel d'Olivier.
 *
 * Distinct d'une réservation : pas de client public, pas de service.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final readonly class Availability
{
    public const TYPE_BLOCKED = 'blocked';
    public const TYPE_EVENT   = 'event';

    public const SOURCE_MANUAL = 'manual';

    public function __construct(
        public ?int $id,
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
        public string $title = '',
        public string $type = self::TYPE_BLOCKED,
        public string $source = self::SOURCE_MANUAL,
    ) {
        if ($end <= $start) {
            throw new \InvalidArgumentException('Availability end must be strictly after start.');
        }
    }

    public function overlaps(DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        return $this->start < $end && $this->end > $start;
    }

    /**
     * Vrai si le créneau a été importé depuis un calendrier externe (ICS).
     */
    public function isImported(): bool
    {
        return str_starts_with($this->source, 'ics:');
    }
}
