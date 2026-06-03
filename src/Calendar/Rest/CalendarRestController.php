<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Rest;

use DateTimeImmutable;
use DateTimeZone;
use OliTheme\Calendar\BookingFormHandler;
use OliTheme\Calendar\BookingRequest;
use OliTheme\Calendar\BookingStatus;
use OliTheme\Calendar\CalendarSettings;
use OliTheme\Calendar\Service;
use OliTheme\Calendar\ServiceRepositoryInterface;
use OliTheme\Calendar\SlotAvailabilityResolver;
use OliTheme\Calendar\SlotGenerator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Endpoints REST publics du module Calendrier.
 *
 *  GET  /wp-json/oli/v1/calendar/services
 *  GET  /wp-json/oli/v1/calendar/slots?service=<id>&from=YYYY-MM-DD
 *  POST /wp-json/oli/v1/calendar/bookings
 *
 * @package OliTheme\Calendar\Rest
 *
 * @since 1.3.0
 */
final class CalendarRestController
{
    public const NAMESPACE = 'oli/v1';

    public function __construct(
        private readonly CalendarSettings $settings,
        private readonly SlotGenerator $generator,
        private readonly SlotAvailabilityResolver $resolver,
        private readonly ServiceRepositoryInterface $services,
        private readonly \OliTheme\Calendar\AvailabilityRepositoryInterface $availabilities,
        private readonly \OliTheme\Calendar\BookingRepositoryInterface $bookings,
        private readonly BookingFormHandler $handler,
    ) {
    }

    public function register(): void
    {
        register_rest_route(self::NAMESPACE, '/calendar/services', [
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback'            => [$this, 'listServices'],
        ]);

        register_rest_route(self::NAMESPACE, '/calendar/slots', [
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback'            => [$this, 'listSlots'],
            'args'                => [
                'service' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'from'    => [
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/calendar/bookings', [
            'methods'             => WP_REST_Server::CREATABLE,
            'permission_callback' => '__return_true',
            'callback'            => [$this, 'createBooking'],
        ]);
    }

    public function listServices(): WP_REST_Response
    {
        $items = array_map(
            static fn (Service $s): array => $s->toArray(),
            $this->services->all(),
        );

        return new WP_REST_Response(['items' => $items], 200);
    }

    public function listSlots(WP_REST_Request $request): WP_REST_Response
    {
        $serviceId = (string) $request->get_param('service');
        $service   = $this->services->byId($serviceId);
        if ($service === null) {
            return new WP_REST_Response(['items' => [], 'error' => 'unknown_service'], 400);
        }

        $from   = (string) ($request->get_param('from') ?: '');
        $weekRef = $this->parseDate($from);
        $slots   = $this->generator->forWeekOf($weekRef);

        // Croise les créneaux candidats avec les contraintes pour ne renvoyer
        // que ceux libres et futurs.
        $now    = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $monday = $weekRef->modify('-' . max(0, ((int) $weekRef->format('N')) - 1) . ' day');
        $sunday = $monday->modify('+7 days');

        $availabilities = $this->availabilities->findInRange($monday, $sunday);
        $bookings       = $this->bookings->findActiveInRange($monday, $sunday);

        $free = $this->resolver->freeSlots($slots, $availabilities, $bookings);
        $payload = [];
        foreach ($free as $slot) {
            if ($slot['start'] < $now) {
                continue;
            }
            // Seuls les créneaux assez longs pour le service sont proposés.
            $serviceEnd = $slot['start']->modify('+' . $service->durationMinutes . ' minutes');
            if ($serviceEnd > $slot['end']) {
                // Si le service dure 2h et le créneau 1h, on l'ignore (cas pratique
                // rare car settings.slotDurationMinutes ≥ durée services typiques).
                continue;
            }
            $payload[] = [
                'start'    => $slot['start']->format(DateTimeImmutable::ATOM),
                'end'      => $slot['end']->format(DateTimeImmutable::ATOM),
                'service'  => $service->id,
                'duration' => $service->durationMinutes,
            ];
        }

        return new WP_REST_Response([
            'service'  => $service->toArray(),
            'weekFrom' => $monday->format('Y-m-d'),
            'weekTo'   => $sunday->format('Y-m-d'),
            'items'    => $payload,
        ], 200);
    }

    public function createBooking(WP_REST_Request $request): WP_REST_Response
    {
        $body = $request->get_json_params() ?? [];
        if (!\is_array($body)) {
            $body = [];
        }
        // Hash IP discret (audit + rate limiting sans stocker l'IP brute).
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $body['__ip_hash'] = $ip !== '' ? substr(hash('sha256', $ip . '|oli-cal'), 0, 32) : '';

        $req    = BookingRequest::fromArray($body);
        $result = $this->handler->handle($req, time());

        if (!$result['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error'   => $result['errorCode'] ?? 'unknown',
                'message' => $result['errorMessage'] ?? '',
            ], $result['errorCode'] === 'rate_limit' ? 429 : 400);
        }

        return new WP_REST_Response([
            'success'   => true,
            'bookingId' => $result['bookingId'] ?? null,
            'status'    => ($result['status'] ?? BookingStatus::Pending)->value,
        ], 201);
    }

    private function parseDate(string $iso): DateTimeImmutable
    {
        $tz = new DateTimeZone('UTC');
        if ($iso === '') {
            return new DateTimeImmutable('now', $tz);
        }
        try {
            return (new DateTimeImmutable($iso, $tz))->setTime(0, 0, 0);
        } catch (\Exception) {
            return new DateTimeImmutable('now', $tz);
        }
    }
}
