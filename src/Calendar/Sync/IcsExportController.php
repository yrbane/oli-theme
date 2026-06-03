<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Sync;

use DateTimeImmutable;
use DateTimeZone;
use OliTheme\Calendar\AvailabilityRepository;
use OliTheme\Calendar\BookingRepository;
use OliTheme\Calendar\CalendarSettings;
use OliTheme\Calendar\Service;
use OliTheme\Calendar\ServiceRepository;

/**
 * Sert le flux iCalendar public (URL avec token secret).
 *
 * URL exemple : `https://olikalari.com/?oli_ics=<token>`
 *
 * Sécurité :
 *  - Comparaison du token via `hash_equals`.
 *  - Header `X-Robots-Tag: noindex, nofollow` pour ne pas indexer.
 *  - `Cache-Control: private, no-store` (l'URL inclut un secret).
 *  - Pas de cookie attendu (consultation par client iCal externe).
 *
 * @package OliTheme\Calendar\Sync
 *
 * @since 1.3.0
 */
final class IcsExportController
{
    public const QUERY_VAR = 'oli_ics';

    public function __construct(
        private readonly CalendarSettings $settings,
        private readonly BookingRepository $bookings,
        private readonly AvailabilityRepository $availabilities,
        private readonly ServiceRepository $services,
        private readonly IcsBuilder $builder,
    ) {
    }

    /**
     * À brancher sur le hook `init`.
     */
    public function registerQueryVar(): void
    {
        add_filter('query_vars', static function (array $vars): array {
            $vars[] = self::QUERY_VAR;
            return $vars;
        });
    }

    /**
     * À brancher sur le hook `template_redirect`. Si le query var
     * `oli_ics` est présent et le token correspond, sort le .ics et
     * termine la requête.
     */
    public function maybeRespond(): void
    {
        $token = (string) get_query_var(self::QUERY_VAR);
        if ($token === '') {
            return;
        }
        $expected = $this->settings->icsExportToken;
        if ($expected === '' || !hash_equals($expected, $token)) {
            status_header(404);
            exit;
        }

        // Fenêtre exportée : -7 jours → +180 jours (couvre 6 mois à l'avance).
        $from = new DateTimeImmutable('-7 days', new DateTimeZone('UTC'));
        $to   = new DateTimeImmutable('+180 days', new DateTimeZone('UTC'));

        $bookings       = $this->bookings->findActiveInRange($from, $to);
        $availabilities = $this->availabilities->findInRange($from, $to);
        $serviceMap     = [];
        foreach ($this->services->all() as $svc) {
            $serviceMap[$svc->id] = $svc;
        }

        $ics = $this->builder->build($bookings, $availabilities, $serviceMap, new DateTimeImmutable('now', new DateTimeZone('UTC')));

        header('Content-Type: text/calendar; charset=UTF-8');
        header('Content-Disposition: inline; filename="olikalari.ics"');
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex, nofollow');
        echo $ics;
        exit;
    }
}
