<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use DateTimeImmutable;
use OliTheme\Calendar\Availability;
use OliTheme\Calendar\Booking;
use OliTheme\Calendar\BookingStatus;
use OliTheme\Calendar\SlotAvailabilityResolver;
use PHPUnit\Framework\TestCase;

final class SlotAvailabilityResolverTest extends TestCase
{
    /**
     * @return list<array{start: DateTimeImmutable, end: DateTimeImmutable}>
     */
    private function slotsOnHour(string $date, int $startH, int $endH): array
    {
        $slots = [];
        for ($h = $startH; $h < $endH; ++$h) {
            $slots[] = [
                'start' => new DateTimeImmutable(sprintf('%s %02d:00:00', $date, $h)),
                'end'   => new DateTimeImmutable(sprintf('%s %02d:00:00', $date, $h + 1)),
            ];
        }
        return $slots;
    }

    private function booking(string $date, int $startH, int $endH, BookingStatus $status): Booking
    {
        return new Booking(
            id: null,
            start: new DateTimeImmutable(sprintf('%s %02d:00:00', $date, $startH)),
            end:   new DateTimeImmutable(sprintf('%s %02d:00:00', $date, $endH)),
            serviceId: 'svc',
            customerName: 'X',
            customerEmail: 'x@x.x',
            status: $status,
        );
    }

    public function test_returns_all_when_no_constraints(): void
    {
        $resolver = new SlotAvailabilityResolver();
        $slots    = $this->slotsOnHour('2026-06-03', 9, 12);
        self::assertSame($slots, $resolver->freeSlots($slots, [], []));
    }

    public function test_removes_slots_overlapped_by_blocked_availability(): void
    {
        $resolver = new SlotAvailabilityResolver();
        $slots    = $this->slotsOnHour('2026-06-03', 9, 12);
        $blocked  = [new Availability(
            null,
            new DateTimeImmutable('2026-06-03 10:00'),
            new DateTimeImmutable('2026-06-03 11:00'),
        )];

        $free = $resolver->freeSlots($slots, $blocked, []);
        self::assertCount(2, $free);
        self::assertSame('09:00', $free[0]['start']->format('H:i'));
        self::assertSame('11:00', $free[1]['start']->format('H:i'));
    }

    public function test_removes_slots_with_active_booking(): void
    {
        $resolver = new SlotAvailabilityResolver();
        $slots    = $this->slotsOnHour('2026-06-03', 9, 12);
        $bookings = [$this->booking('2026-06-03', 10, 11, BookingStatus::Confirmed)];
        $free = $resolver->freeSlots($slots, [], $bookings);
        self::assertCount(2, $free);
    }

    public function test_keeps_slots_with_cancelled_booking(): void
    {
        $resolver = new SlotAvailabilityResolver();
        $slots    = $this->slotsOnHour('2026-06-03', 9, 12);
        $bookings = [$this->booking('2026-06-03', 10, 11, BookingStatus::Cancelled)];
        $free = $resolver->freeSlots($slots, [], $bookings);
        self::assertCount(3, $free); // annulée → créneau libre
    }

    public function test_combines_both_constraints(): void
    {
        $resolver = new SlotAvailabilityResolver();
        $slots    = $this->slotsOnHour('2026-06-03', 9, 13);
        $blocked  = [new Availability(null, new DateTimeImmutable('2026-06-03 09:00'), new DateTimeImmutable('2026-06-03 10:00'))];
        $bookings = [$this->booking('2026-06-03', 12, 13, BookingStatus::Pending)];
        $free = $resolver->freeSlots($slots, $blocked, $bookings);
        self::assertCount(2, $free);
        self::assertSame('10:00', $free[0]['start']->format('H:i'));
        self::assertSame('11:00', $free[1]['start']->format('H:i'));
    }
}
