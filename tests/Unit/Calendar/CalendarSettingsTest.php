<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use OliTheme\Calendar\CalendarSettings;
use PHPUnit\Framework\TestCase;

final class CalendarSettingsTest extends TestCase
{
    public function test_default_values_are_sensible(): void
    {
        $s = CalendarSettings::default();
        self::assertSame(60, $s->slotDurationMinutes);
        self::assertSame([1, 2, 3, 4, 5], $s->workingDays);
        self::assertSame('09:00', $s->workingHoursStart);
        self::assertSame('19:00', $s->workingHoursEnd);
        self::assertSame(CalendarSettings::STATE_AVAILABLE, $s->defaultState);
        self::assertSame('', $s->notificationEmail);
        self::assertFalse($s->autoConfirm);
    }

    public function test_from_input_clamps_duration(): void
    {
        $s = CalendarSettings::fromInput(['slotDurationMinutes' => 9999]);
        self::assertSame(CalendarSettings::SLOT_MAX, $s->slotDurationMinutes);

        $s = CalendarSettings::fromInput(['slotDurationMinutes' => 1]);
        self::assertSame(CalendarSettings::SLOT_MIN, $s->slotDurationMinutes);
    }

    public function test_from_input_accepts_60_or_120_minutes(): void
    {
        self::assertSame(60, CalendarSettings::fromInput(['slotDurationMinutes' => 60])->slotDurationMinutes);
        self::assertSame(120, CalendarSettings::fromInput(['slotDurationMinutes' => 120])->slotDurationMinutes);
    }

    public function test_from_input_deduplicates_and_validates_working_days(): void
    {
        $s = CalendarSettings::fromInput(['workingDays' => [1, 1, 8, 'x', -1, 6, 0]]);
        self::assertSame([0, 1, 6], $s->workingDays);
    }

    public function test_from_input_invalid_time_falls_back(): void
    {
        $s = CalendarSettings::fromInput(['workingHoursStart' => '25:99', 'workingHoursEnd' => 'noon']);
        self::assertSame('09:00', $s->workingHoursStart);
        self::assertSame('19:00', $s->workingHoursEnd);
    }

    public function test_from_input_normalizes_time_format(): void
    {
        $s = CalendarSettings::fromInput(['workingHoursStart' => '9:5', 'workingHoursEnd' => '17:30']);
        self::assertSame('09:05', $s->workingHoursStart);
        self::assertSame('17:30', $s->workingHoursEnd);
    }

    public function test_from_input_collapses_end_when_before_start(): void
    {
        $s = CalendarSettings::fromInput(['workingHoursStart' => '14:00', 'workingHoursEnd' => '10:00']);
        self::assertSame('14:00', $s->workingHoursStart);
        self::assertSame('14:00', $s->workingHoursEnd);
    }

    public function test_from_input_default_state_falls_back_on_invalid(): void
    {
        $s = CalendarSettings::fromInput(['defaultState' => 'foo']);
        self::assertSame(CalendarSettings::STATE_AVAILABLE, $s->defaultState);
    }
}
