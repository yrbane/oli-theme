<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Logique métier de validation + persistance d'une demande de réservation.
 *
 * Découplée du transport HTTP pour rester testable en unit. Le contrôleur
 * REST construit un {@see BookingRequest}, appelle `handle()`, puis traduit
 * le résultat en réponse HTTP.
 *
 * Vérifie successivement :
 *  1. Honeypot vide.
 *  2. Délai mini entre rendu et soumission (>= 2 s).
 *  3. Rate limit IP.
 *  4. Service connu.
 *  5. Email valide + nom non vide.
 *  6. Date parsable et future.
 *  7. Créneau réellement libre (croise availabilities + bookings actifs).
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final class BookingFormHandler
{
    /** Délai minimum (secondes) entre le rendu du form et sa soumission. */
    public const MIN_RENDER_DELAY = 2;

    public function __construct(
        private readonly CalendarSettings $settings,
        private readonly ServiceRepositoryInterface $services,
        private readonly AvailabilityRepositoryInterface $availabilities,
        private readonly BookingRepositoryInterface $bookings,
        private readonly RateLimiter $rateLimiter,
    ) {
    }

    /**
     * @return array{success: bool, errorCode?: string, errorMessage?: string, bookingId?: int, status?: BookingStatus}
     */
    public function handle(BookingRequest $request, int $now): array
    {
        if ($request->honeypot !== '') {
            // Spam silencieux : on simule un succès pour ne pas révéler la détection.
            return ['success' => true];
        }
        if ($request->renderedAt > 0 && ($now - $request->renderedAt) < self::MIN_RENDER_DELAY) {
            return ['success' => false, 'errorCode' => 'too_fast', 'errorMessage' => 'Submission too fast.'];
        }
        if (!$this->rateLimiter->tryConsume($request->ipHash !== '' ? $request->ipHash : 'anonymous')) {
            return ['success' => false, 'errorCode' => 'rate_limit', 'errorMessage' => 'Trop de tentatives, réessayez plus tard.'];
        }
        $service = $this->services->byId($request->serviceId);
        if ($service === null) {
            return ['success' => false, 'errorCode' => 'unknown_service', 'errorMessage' => 'Service inconnu.'];
        }
        if ($request->customerName === '') {
            return ['success' => false, 'errorCode' => 'missing_name', 'errorMessage' => 'Le nom est requis.'];
        }
        if (!filter_var($request->customerEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'errorCode' => 'invalid_email', 'errorMessage' => 'Email invalide.'];
        }
        try {
            $start = (new DateTimeImmutable($request->startIso, new DateTimeZone('UTC')));
        } catch (\Exception) {
            return ['success' => false, 'errorCode' => 'invalid_date', 'errorMessage' => 'Date invalide.'];
        }
        $end = $start->modify('+' . $service->durationMinutes . ' minutes');
        if ($start->getTimestamp() < $now) {
            return ['success' => false, 'errorCode' => 'past_date', 'errorMessage' => 'Créneau passé.'];
        }

        // Vérifie qu'aucune réservation active ni indisponibilité ne couvre le créneau.
        $conflictingBookings = $this->bookings->findActiveInRange($start, $end);
        if (!empty($conflictingBookings)) {
            return ['success' => false, 'errorCode' => 'slot_taken', 'errorMessage' => 'Ce créneau vient d\'être réservé.'];
        }
        $conflictingAvail = $this->availabilities->findInRange($start, $end);
        foreach ($conflictingAvail as $a) {
            if ($a->overlaps($start, $end)) {
                return ['success' => false, 'errorCode' => 'slot_blocked', 'errorMessage' => 'Ce créneau n\'est plus disponible.'];
            }
        }

        $status = $this->settings->autoConfirm ? BookingStatus::Confirmed : BookingStatus::Pending;
        $booking = new Booking(
            id:            null,
            start:         $start,
            end:           $end,
            serviceId:     $service->id,
            customerName:  $request->customerName,
            customerEmail: $request->customerEmail,
            status:        $status,
            customerPhone: $request->customerPhone,
            message:       $request->message,
            language:      \in_array($request->language, ['fr', 'en'], true) ? $request->language : 'fr',
        );

        $id = $this->bookings->save($booking, $request->ipHash);

        return ['success' => true, 'bookingId' => $id, 'status' => $status];
    }
}
