<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use OliTheme\Calendar\Sync\IcsParser;
use PHPUnit\Framework\TestCase;

final class IcsParserTest extends TestCase
{
    public function test_parses_single_utc_event(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:abc\r\nDTSTART:20260605T100000Z\r\nDTEND:20260605T120000Z\r\nSUMMARY:Réunion\r\nEND:VEVENT\r\nEND:VCALENDAR";
        $events = (new IcsParser())->parse($ics);
        self::assertCount(1, $events);
        self::assertSame('abc', $events[0]['uid']);
        self::assertSame('Réunion', $events[0]['summary']);
        self::assertSame('2026-06-05 10:00:00', $events[0]['start']->format('Y-m-d H:i:s'));
        self::assertSame('2026-06-05 12:00:00', $events[0]['end']->format('Y-m-d H:i:s'));
        self::assertFalse($events[0]['allDay']);
    }

    public function test_unfolds_lines(): void
    {
        $ics = "BEGIN:VEVENT\r\nUID:abc\r\nDTSTART:20260605T100000Z\r\nDTEND:20260605T110000Z\r\nSUMMARY:Très long\r\n   suite\r\nEND:VEVENT";
        $events = (new IcsParser())->parse($ics);
        self::assertCount(1, $events);
        // Le space en début de ligne 2 est retiré, le caractère suivant est conservé.
        self::assertSame("Très long  suite", $events[0]['summary']);
    }

    public function test_unescapes_special_chars(): void
    {
        $ics = "BEGIN:VEVENT\r\nUID:x\r\nDTSTART:20260605T100000Z\r\nDTEND:20260605T110000Z\r\nSUMMARY:A\\; B\\, C\\n2nd\r\nEND:VEVENT";
        $events = (new IcsParser())->parse($ics);
        self::assertSame("A; B, C\n2nd", $events[0]['summary']);
    }

    public function test_handles_all_day_events(): void
    {
        $ics = "BEGIN:VEVENT\r\nUID:d\r\nDTSTART;VALUE=DATE:20260605\r\nDTEND;VALUE=DATE:20260606\r\nSUMMARY:Vacances\r\nEND:VEVENT";
        $events = (new IcsParser())->parse($ics);
        self::assertCount(1, $events);
        self::assertTrue($events[0]['allDay']);
        self::assertSame('2026-06-05', $events[0]['start']->format('Y-m-d'));
    }

    public function test_ignores_non_vevent_blocks(): void
    {
        $ics = "BEGIN:VTIMEZONE\r\nTZID:Europe/Paris\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nUID:1\r\nDTSTART:20260605T100000Z\r\nDTEND:20260605T110000Z\r\nSUMMARY:OK\r\nEND:VEVENT";
        $events = (new IcsParser())->parse($ics);
        self::assertCount(1, $events);
        self::assertSame('1', $events[0]['uid']);
    }

    public function test_handles_tzid_when_specified(): void
    {
        $ics = "BEGIN:VEVENT\r\nUID:p\r\nDTSTART;TZID=Europe/Paris:20260605T120000\r\nDTEND;TZID=Europe/Paris:20260605T130000\r\nSUMMARY:Paris\r\nEND:VEVENT";
        $events = (new IcsParser())->parse($ics);
        // Paris en juin = UTC+2 → 12:00 Paris = 10:00 UTC.
        self::assertSame('2026-06-05 10:00:00', $events[0]['start']->format('Y-m-d H:i:s'));
    }

    public function test_returns_empty_on_no_events(): void
    {
        self::assertSame([], (new IcsParser())->parse("BEGIN:VCALENDAR\r\nEND:VCALENDAR"));
    }
}
