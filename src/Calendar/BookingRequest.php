<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

use DateTimeImmutable;

/**
 * DTO immuable représentant une demande de réservation reçue côté front
 * AVANT validation. Toutes les valeurs sont brutes (strings).
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final readonly class BookingRequest
{
    public function __construct(
        public string $serviceId,
        public string $startIso,
        public string $customerName,
        public string $customerEmail,
        public string $customerPhone = '',
        public string $message = '',
        public string $language = 'fr',
        public string $honeypot = '',
        public int $renderedAt = 0,
        public string $ipHash = '',
    ) {
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function fromArray(array $body): self
    {
        return new self(
            serviceId:     (string) ($body['service_id']     ?? ''),
            startIso:      (string) ($body['start']          ?? ''),
            customerName:  trim((string) ($body['name']      ?? '')),
            customerEmail: trim((string) ($body['email']     ?? '')),
            customerPhone: trim((string) ($body['phone']     ?? '')),
            message:       (string) ($body['message']        ?? ''),
            language:      (string) ($body['lang']           ?? 'fr'),
            honeypot:      (string) ($body['website']        ?? ''),
            renderedAt:    (int)    ($body['rendered_at']    ?? 0),
            ipHash:        (string) ($body['__ip_hash']      ?? ''),
        );
    }
}
