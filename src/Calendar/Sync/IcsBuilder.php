<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Sync;

use DateTimeImmutable;
use DateTimeZone;
use OliTheme\Calendar\Availability;
use OliTheme\Calendar\Booking;
use OliTheme\Calendar\Service;

/**
 * Génère un flux iCalendar (RFC 5545) à partir des réservations
 * confirmées et des indisponibilités manuelles du calendrier Olikalari.
 *
 * @package OliTheme\Calendar\Sync
 *
 * @since 1.3.0
 */
final class IcsBuilder
{
    public function __construct(
        private readonly string $prodId = '-//Olikalari//oli-theme//FR',
        private readonly string $domain = 'olikalari.local',
    ) {
    }

    /**
     * @param list<Booking> $bookings
     * @param list<Availability> $availabilities
     * @param array<string, Service> $servicesById
     */
    public function build(array $bookings, array $availabilities, array $servicesById, DateTimeImmutable $stamp): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:' . $this->prodId,
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($bookings as $b) {
            if (!$b->status->isActive()) {
                continue;
            }
            $svc = $servicesById[$b->serviceId] ?? null;
            $serviceLabel = $svc !== null ? $svc->labelFr : $b->serviceId;
            $summary = sprintf('%s — %s', $serviceLabel, $b->customerName);
            $desc = sprintf("Email : %s\\nTéléphone : %s", $b->customerEmail, $b->customerPhone !== '' ? $b->customerPhone : '—');
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:booking-' . (int) $b->id . '@' . $this->domain;
            $lines[] = 'DTSTAMP:' . $this->utcStamp($stamp);
            $lines[] = 'DTSTART:' . $this->utcStamp($b->start);
            $lines[] = 'DTEND:'   . $this->utcStamp($b->end);
            $lines[] = 'SUMMARY:' . $this->escape($summary);
            $lines[] = 'DESCRIPTION:' . $this->escape($desc);
            $lines[] = 'STATUS:' . ($b->status->value === 'confirmed' ? 'CONFIRMED' : 'TENTATIVE');
            $lines[] = 'END:VEVENT';
        }

        foreach ($availabilities as $a) {
            if ($a->isImported()) {
                continue; // ne pas réexporter ce qu'on a importé.
            }
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:avail-' . (int) $a->id . '@' . $this->domain;
            $lines[] = 'DTSTAMP:' . $this->utcStamp($stamp);
            $lines[] = 'DTSTART:' . $this->utcStamp($a->start);
            $lines[] = 'DTEND:'   . $this->utcStamp($a->end);
            $lines[] = 'SUMMARY:' . $this->escape($a->title !== '' ? $a->title : 'Indisponible');
            $lines[] = 'STATUS:CONFIRMED';
            $lines[] = 'TRANSP:OPAQUE';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return $this->foldLines($lines);
    }

    private function utcStamp(DateTimeImmutable $dt): string
    {
        return $dt->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }

    /**
     * Échappement RFC 5545 : `\\`, `,`, `;` deviennent `\\\\`, `\,`, `\;`.
     * Les retours-ligne deviennent `\n`.
     */
    private function escape(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\n"], '\\n', $text);
        return str_replace(['\\', ',', ';'], ['\\\\', '\\,', '\\;'], $text);
    }

    /**
     * Plie chaque ligne à 75 octets (RFC 5545 section 3.1) et joint en CRLF.
     *
     * @param list<string> $lines
     */
    private function foldLines(array $lines): string
    {
        $folded = [];
        foreach ($lines as $line) {
            while (\strlen($line) > 75) {
                $folded[] = substr($line, 0, 75);
                $line     = ' ' . substr($line, 75);
            }
            $folded[] = $line;
        }
        return implode("\r\n", $folded) . "\r\n";
    }
}
