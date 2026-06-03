<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

use DateTimeImmutable;

/**
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
interface AvailabilityRepositoryInterface
{
    /** @return list<Availability> */
    public function findInRange(DateTimeImmutable $from, DateTimeImmutable $to): array;
}
