<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

use DateTimeImmutable;

/**
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
interface BookingRepositoryInterface
{
    /** @return list<Booking> */
    public function findActiveInRange(DateTimeImmutable $from, DateTimeImmutable $to): array;

    public function save(Booking $booking, string $ipHash = ''): int;
}
