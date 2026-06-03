<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Sync;

use DateTimeImmutable;
use DateTimeZone;
use OliTheme\Calendar\Availability;
use OliTheme\Calendar\AvailabilityRepository;
use OliTheme\Calendar\CalendarSettings;

/**
 * Importe les événements d'une ou plusieurs URLs iCal externes
 * (Gmail, Apple, Outlook…) sous forme de créneaux `blocked` côté thème,
 * pour éviter les double-bookings.
 *
 * Conçu pour être exécuté en cron (`oli_calendar_ics_pull`).
 *
 * @package OliTheme\Calendar\Sync
 *
 * @since 1.3.0
 */
final class IcsImporter
{
    /** Taille maximale acceptée pour un .ics téléchargé (1 MB). */
    private const MAX_BYTES = 1_048_576;

    public function __construct(
        private readonly CalendarSettings $settings,
        private readonly IcsParser $parser,
        private readonly AvailabilityRepository $availabilities,
    ) {
    }

    /**
     * Importe toutes les URLs configurées. Retourne le compte d'événements
     * importés par URL.
     *
     * @return array<string, int>
     */
    public function pullAll(): array
    {
        $stats = [];
        foreach ($this->settings->icsImportUrls as $url) {
            $stats[$url] = $this->pullOne($url);
        }
        return $stats;
    }

    public function pullOne(string $url): int
    {
        if (stripos($url, 'https://') !== 0) {
            return 0;
        }
        $sourceTag = 'ics:' . substr(hash('sha256', $url), 0, 16);
        $body      = $this->fetch($url);
        if ($body === null) {
            return 0;
        }
        $events = $this->parser->parse($body);

        // Stratégie simple et idempotente : purger l'existant pour cette source
        // dans la fenêtre future, puis réinsérer. Les créneaux passés sont laissés
        // pour audit. (Améliorable plus tard : diff sur UID).
        $this->purgeFutureFromSource($sourceTag);

        $tz = new DateTimeZone('UTC');
        $count = 0;
        foreach ($events as $ev) {
            $start = $ev['start'];
            $end   = $ev['end'];
            if ($end <= $start) {
                $end = $start->modify('+1 hour'); // garde-fou pour les events sans DTEND.
            }
            if ($start < new DateTimeImmutable('now', $tz)) {
                continue; // ne pas importer le passé.
            }
            $title = $ev['summary'] !== '' ? $ev['summary'] : '(externe)';
            try {
                $this->availabilities->save(new Availability(
                    id: null,
                    start: $start,
                    end:   $end,
                    title: $title,
                    type:  Availability::TYPE_BLOCKED,
                    source: $sourceTag,
                ));
                ++$count;
            } catch (\Throwable) {
                // Ignore les events invalides individuellement.
            }
        }
        return $count;
    }

    private function fetch(string $url): ?string
    {
        if (!\function_exists('wp_remote_get') || !\function_exists('wp_remote_retrieve_body') || !\function_exists('wp_remote_retrieve_response_code')) {
            return null;
        }
        $response = wp_remote_get($url, [
            'timeout'     => 15,
            'redirection' => 3,
            'sslverify'   => true,
            'headers'     => ['Accept' => 'text/calendar, text/plain;q=0.9, */*;q=0.5'],
        ]);
        if (\function_exists('is_wp_error') && is_wp_error($response)) {
            return null;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return null;
        }
        $body = (string) wp_remote_retrieve_body($response);
        if ($body === '' || \strlen($body) > self::MAX_BYTES) {
            return null;
        }
        // Sanity-check : doit ressembler à du iCal.
        if (stripos($body, 'BEGIN:VCALENDAR') === false) {
            return null;
        }
        return $body;
    }

    /**
     * Supprime les Availability futures associées à une source ICS donnée.
     * Implémenté via une fenêtre large (+5 ans) puis filtrage.
     */
    private function purgeFutureFromSource(string $sourceTag): void
    {
        $tz   = new DateTimeZone('UTC');
        $now  = new DateTimeImmutable('now', $tz);
        $far  = new DateTimeImmutable('+5 years', $tz);
        $list = $this->availabilities->findInRange($now, $far);
        foreach ($list as $a) {
            if ($a->source === $sourceTag && $a->id !== null) {
                $this->availabilities->delete($a->id);
            }
        }
    }
}
