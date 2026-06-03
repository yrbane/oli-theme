<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use DateTimeImmutable;
use OliTheme\Calendar\Availability;
use PHPUnit\Framework\TestCase;

final class AvailabilityTest extends TestCase
{
    public function test_constructor_rejects_inverted_range(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Availability(null, new DateTimeImmutable('2026-06-03 10:00'), new DateTimeImmutable('2026-06-03 09:00'));
    }

    public function test_constructor_rejects_equal_range(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Availability(null, new DateTimeImmutable('2026-06-03 10:00'), new DateTimeImmutable('2026-06-03 10:00'));
    }

    public function test_overlaps_detects_overlap(): void
    {
        $a = new Availability(null, new DateTimeImmutable('2026-06-03 10:00'), new DateTimeImmutable('2026-06-03 12:00'));
        self::assertTrue($a->overlaps(new DateTimeImmutable('2026-06-03 11:00'), new DateTimeImmutable('2026-06-03 13:00')));
        self::assertTrue($a->overlaps(new DateTimeImmutable('2026-06-03 09:00'), new DateTimeImmutable('2026-06-03 11:00')));
        self::assertTrue($a->overlaps(new DateTimeImmutable('2026-06-03 11:00'), new DateTimeImmutable('2026-06-03 11:30')));
    }

    public function test_overlaps_rejects_adjacent(): void
    {
        $a = new Availability(null, new DateTimeImmutable('2026-06-03 10:00'), new DateTimeImmutable('2026-06-03 12:00'));
        self::assertFalse($a->overlaps(new DateTimeImmutable('2026-06-03 12:00'), new DateTimeImmutable('2026-06-03 14:00')));
        self::assertFalse($a->overlaps(new DateTimeImmutable('2026-06-03 08:00'), new DateTimeImmutable('2026-06-03 10:00')));
    }

    public function test_is_imported_when_source_starts_with_ics(): void
    {
        $manual = new Availability(null, new DateTimeImmutable('2026-06-03 10:00'), new DateTimeImmutable('2026-06-03 11:00'), source: Availability::SOURCE_MANUAL);
        self::assertFalse($manual->isImported());

        $ics = new Availability(null, new DateTimeImmutable('2026-06-03 10:00'), new DateTimeImmutable('2026-06-03 11:00'), source: 'ics:abc123');
        self::assertTrue($ics->isImported());
    }
}
