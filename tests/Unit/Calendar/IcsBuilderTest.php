<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use DateTimeImmutable;
use OliTheme\Calendar\Availability;
use OliTheme\Calendar\Booking;
use OliTheme\Calendar\BookingStatus;
use OliTheme\Calendar\Service;
use OliTheme\Calendar\Sync\IcsBuilder;
use PHPUnit\Framework\TestCase;

final class IcsBuilderTest extends TestCase
{
    public function test_basic_envelope(): void
    {
        $ics = (new IcsBuilder())->build([], [], [], new DateTimeImmutable('2026-06-03 10:00 UTC'));
        self::assertStringContainsString('BEGIN:VCALENDAR', $ics);
        self::assertStringContainsString('PRODID:-//Olikalari', $ics);
        self::assertStringContainsString('VERSION:2.0', $ics);
        self::assertStringContainsString('END:VCALENDAR', $ics);
    }

    public function test_includes_active_booking_with_summary_and_description(): void
    {
        $booking = new Booking(
            id: 42,
            start: new DateTimeImmutable('2026-06-05 10:00 UTC'),
            end:   new DateTimeImmutable('2026-06-05 11:00 UTC'),
            serviceId: 'massage',
            customerName: 'Jean Dupont',
            customerEmail: 'jean@example.com',
            status: BookingStatus::Confirmed,
            customerPhone: '06 11',
        );
        $svc = new Service('massage', 'Massage', 'Massage', 60);
        $ics = (new IcsBuilder())->build([$booking], [], ['massage' => $svc], new DateTimeImmutable('2026-06-03 10:00 UTC'));

        self::assertStringContainsString('UID:booking-42@', $ics);
        self::assertStringContainsString('DTSTART:20260605T100000Z', $ics);
        self::assertStringContainsString('DTEND:20260605T110000Z', $ics);
        self::assertStringContainsString('SUMMARY:Massage — Jean Dupont', $ics);
        self::assertStringContainsString('jean@example.com', $ics);
        self::assertStringContainsString('STATUS:CONFIRMED', $ics);
    }

    public function test_excludes_cancelled_bookings(): void
    {
        $b = new Booking(
            id: 1,
            start: new DateTimeImmutable('2026-06-05 10:00 UTC'),
            end:   new DateTimeImmutable('2026-06-05 11:00 UTC'),
            serviceId: 's', customerName: 'X', customerEmail: 'x@x.x',
            status: BookingStatus::Cancelled,
        );
        $ics = (new IcsBuilder())->build([$b], [], [], new DateTimeImmutable('2026-06-03 10:00 UTC'));
        self::assertStringNotContainsString('UID:booking-1@', $ics);
    }

    public function test_includes_manual_availability_excludes_imported(): void
    {
        $manual = new Availability(
            id: 10,
            start: new DateTimeImmutable('2026-06-05 14:00 UTC'),
            end:   new DateTimeImmutable('2026-06-05 16:00 UTC'),
            title: 'Vacances',
            source: Availability::SOURCE_MANUAL,
        );
        $imported = new Availability(
            id: 11,
            start: new DateTimeImmutable('2026-06-06 14:00 UTC'),
            end:   new DateTimeImmutable('2026-06-06 16:00 UTC'),
            source: 'ics:hash123',
        );
        $ics = (new IcsBuilder())->build([], [$manual, $imported], [], new DateTimeImmutable('2026-06-03 10:00 UTC'));
        self::assertStringContainsString('UID:avail-10@', $ics);
        self::assertStringContainsString('SUMMARY:Vacances', $ics);
        self::assertStringNotContainsString('UID:avail-11@', $ics);
    }

    public function test_escapes_commas_and_semicolons(): void
    {
        $b = new Booking(
            id: 1,
            start: new DateTimeImmutable('2026-06-05 10:00 UTC'),
            end:   new DateTimeImmutable('2026-06-05 11:00 UTC'),
            serviceId: 's', customerName: 'A; B, C',
            customerEmail: 'x@x.x',
            status: BookingStatus::Confirmed,
        );
        $ics = (new IcsBuilder())->build([$b], [], [], new DateTimeImmutable('2026-06-03 10:00 UTC'));
        self::assertStringContainsString('A\\; B\\, C', $ics);
    }

    public function test_lines_are_folded_at_75_chars(): void
    {
        $b = new Booking(
            id: 99,
            start: new DateTimeImmutable('2026-06-05 10:00 UTC'),
            end:   new DateTimeImmutable('2026-06-05 11:00 UTC'),
            serviceId: 's',
            customerName: str_repeat('A', 200),
            customerEmail: 'x@x.x',
            status: BookingStatus::Confirmed,
        );
        $ics = (new IcsBuilder())->build([$b], [], [], new DateTimeImmutable('2026-06-03 10:00 UTC'));
        foreach (explode("\r\n", $ics) as $line) {
            self::assertLessThanOrEqual(75, \strlen($line), 'Line too long: ' . substr($line, 0, 40));
        }
    }
}
