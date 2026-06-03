<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Sync;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Parser iCalendar minimal pour extraire les blocs VEVENT d'un .ics.
 *
 * Couvre le sous-ensemble nécessaire au flux d'import :
 *  - VEVENT avec DTSTART, DTEND, SUMMARY.
 *  - Formats DATE-TIME UTC (`...Z`), DATE-TIME locaux, DATE-only.
 *  - Line unfolding (CRLF + WSP).
 *  - Échappement inversé (`\\`, `\,`, `\;`, `\n`).
 *
 * Ne tente pas de gérer : RRULE, VTIMEZONE personnalisé (suppose UTC ou TZID
 * standard), composantes autres que VEVENT.
 *
 * @package OliTheme\Calendar\Sync
 *
 * @since 1.3.0
 */
final class IcsParser
{
    /**
     * @return list<array{uid:string,summary:string,start:DateTimeImmutable,end:DateTimeImmutable,allDay:bool}>
     */
    public function parse(string $ics): array
    {
        $lines  = $this->unfold($ics);
        $events = [];
        $current = null;

        foreach ($lines as $line) {
            if ($line === 'BEGIN:VEVENT') {
                $current = [];
                continue;
            }
            if ($line === 'END:VEVENT') {
                if (\is_array($current) && isset($current['start'], $current['end'])) {
                    $events[] = [
                        'uid'     => (string) ($current['uid']     ?? ''),
                        'summary' => (string) ($current['summary'] ?? ''),
                        'start'   => $current['start'],
                        'end'     => $current['end'],
                        'allDay'  => (bool) ($current['allDay']    ?? false),
                    ];
                }
                $current = null;
                continue;
            }
            if ($current === null) {
                continue;
            }
            [$rawName, $value] = $this->splitNameValue($line);
            $name = strtoupper(strtok($rawName, ';'));

            switch ($name) {
                case 'UID':
                    $current['uid'] = $value;
                    break;
                case 'SUMMARY':
                    $current['summary'] = $this->unescape($value);
                    break;
                case 'DTSTART':
                    [$dt, $allDay]      = $this->parseDateTime($rawName, $value);
                    $current['start']   = $dt;
                    $current['allDay']  = $allDay;
                    break;
                case 'DTEND':
                    [$dt]            = $this->parseDateTime($rawName, $value);
                    $current['end']  = $dt;
                    break;
            }
        }
        return $events;
    }

    /**
     * @return list<string>
     */
    private function unfold(string $ics): array
    {
        $ics   = str_replace("\r\n", "\n", $ics);
        $raw   = explode("\n", $ics);
        $out   = [];
        foreach ($raw as $line) {
            if ($line === '') {
                continue;
            }
            if (($line[0] === ' ' || $line[0] === "\t") && !empty($out)) {
                $out[count($out) - 1] .= substr($line, 1);
            } else {
                $out[] = $line;
            }
        }
        return $out;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitNameValue(string $line): array
    {
        $pos = strpos($line, ':');
        if ($pos === false) {
            return [$line, ''];
        }
        return [substr($line, 0, $pos), substr($line, $pos + 1)];
    }

    /**
     * @return array{0:DateTimeImmutable,1:bool} dateTime + isAllDay
     */
    private function parseDateTime(string $rawName, string $value): array
    {
        // VALUE=DATE → all-day (YYYYMMDD).
        $isAllDay = stripos($rawName, 'VALUE=DATE') !== false || preg_match('/^\d{8}$/', $value) === 1;
        $tzUtc    = new DateTimeZone('UTC');

        if ($isAllDay) {
            $dt = DateTimeImmutable::createFromFormat('!Ymd', $value, $tzUtc) ?: new DateTimeImmutable('@0', $tzUtc);
            return [$dt, true];
        }

        // Format UTC : YYYYMMDDTHHMMSSZ.
        if (preg_match('/^(\d{8}T\d{6})Z$/', $value, $m) === 1) {
            $dt = DateTimeImmutable::createFromFormat('!Ymd\THis', $m[1], $tzUtc) ?: new DateTimeImmutable('@0', $tzUtc);
            return [$dt, false];
        }

        // Format avec TZID : on prend la valeur du paramètre TZID et on tente.
        $tz = $tzUtc;
        if (preg_match('/TZID=([^:;]+)/', $rawName, $m) === 1) {
            try {
                $tz = new DateTimeZone($m[1]);
            } catch (\Exception) {
                $tz = $tzUtc;
            }
        }
        $dt = DateTimeImmutable::createFromFormat('!Ymd\THis', $value, $tz);
        if ($dt === false) {
            $dt = new DateTimeImmutable('@0', $tzUtc);
        }
        return [$dt->setTimezone($tzUtc), false];
    }

    private function unescape(string $value): string
    {
        return str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $value);
    }
}
