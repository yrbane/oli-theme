<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use OliTheme\Calendar\BookingStatus;
use OliTheme\Calendar\SlotState;
use PHPUnit\Framework\TestCase;

final class SlotStateTest extends TestCase
{
    public function test_only_available_is_reservable(): void
    {
        self::assertTrue(SlotState::Available->isReservable());
        self::assertFalse(SlotState::Blocked->isReservable());
        self::assertFalse(SlotState::Booked->isReservable());
    }

    public function test_state_values(): void
    {
        self::assertSame('available', SlotState::Available->value);
        self::assertSame('blocked', SlotState::Blocked->value);
        self::assertSame('booked', SlotState::Booked->value);
    }

    public function test_booking_status_is_active(): void
    {
        self::assertTrue(BookingStatus::Pending->isActive());
        self::assertTrue(BookingStatus::Confirmed->isActive());
        self::assertFalse(BookingStatus::Cancelled->isActive());
    }
}
