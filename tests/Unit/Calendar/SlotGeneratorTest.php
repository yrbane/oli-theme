<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use DateTimeImmutable;
use OliTheme\Calendar\CalendarSettings;
use OliTheme\Calendar\SlotGenerator;
use PHPUnit\Framework\TestCase;

final class SlotGeneratorTest extends TestCase
{
    public function test_generates_correct_count_for_default_week(): void
    {
        // 5 jours ouvrés × (19h - 9h = 10h) ÷ 1h = 50 créneaux.
        $gen = new SlotGenerator(CalendarSettings::default());
        $slots = $gen->forWeekOf(new DateTimeImmutable('2026-06-03')); // mercredi
        self::assertCount(50, $slots);
    }

    public function test_respects_slot_duration(): void
    {
        $s = new CalendarSettings(slotDurationMinutes: 120, workingDays: [1], workingHoursStart: '09:00', workingHoursEnd: '13:00');
        $gen = new SlotGenerator($s);
        $slots = $gen->forWeekOf(new DateTimeImmutable('2026-06-03'));
        // 1 jour × (13-9 = 4h) / 2h = 2 créneaux.
        self::assertCount(2, $slots);
        self::assertSame('09:00', $slots[0]['start']->format('H:i'));
        self::assertSame('11:00', $slots[0]['end']->format('H:i'));
        self::assertSame('11:00', $slots[1]['start']->format('H:i'));
        self::assertSame('13:00', $slots[1]['end']->format('H:i'));
    }

    public function test_skips_excluded_days(): void
    {
        $s = new CalendarSettings(workingDays: [3]); // mercredi seulement.
        $gen = new SlotGenerator($s);
        $slots = $gen->forWeekOf(new DateTimeImmutable('2026-06-03'));
        // 10 créneaux d'1h sur 1 jour ouvré.
        self::assertCount(10, $slots);
        foreach ($slots as $slot) {
            self::assertSame('Wed', $slot['start']->format('D'));
        }
    }

    public function test_handles_partial_last_slot_by_dropping_it(): void
    {
        // 9h → 10h30 avec créneaux de 1h : devrait produire 1 créneau (9-10),
        // le 10-11 dépasse 10:30 donc est exclu.
        $s = new CalendarSettings(slotDurationMinutes: 60, workingDays: [1], workingHoursStart: '09:00', workingHoursEnd: '10:30');
        $gen = new SlotGenerator($s);
        $slots = $gen->forWeekOf(new DateTimeImmutable('2026-06-01'));
        self::assertCount(1, $slots);
    }

    public function test_returns_empty_when_no_working_days(): void
    {
        $s = new CalendarSettings(workingDays: []);
        $gen = new SlotGenerator($s);
        $slots = $gen->forWeekOf(new DateTimeImmutable('2026-06-03'));
        self::assertSame([], $slots);
    }

    public function test_returns_empty_when_end_equals_start(): void
    {
        $s = new CalendarSettings(workingDays: [1], workingHoursStart: '09:00', workingHoursEnd: '09:00');
        $gen = new SlotGenerator($s);
        $slots = $gen->forWeekOf(new DateTimeImmutable('2026-06-01'));
        self::assertSame([], $slots);
    }

    public function test_aligns_to_monday_of_the_week(): void
    {
        $gen = new SlotGenerator(new CalendarSettings(workingDays: [1], workingHoursStart: '09:00', workingHoursEnd: '10:00'));
        // Réference = un dimanche → la semaine est lundi → dimanche.
        $sunday = new DateTimeImmutable('2026-06-07'); // dimanche
        $slots = $gen->forWeekOf($sunday);
        self::assertCount(1, $slots);
        self::assertSame('2026-06-01', $slots[0]['start']->format('Y-m-d'));
    }
}
