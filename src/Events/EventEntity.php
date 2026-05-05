<?php

declare(strict_types=1);

namespace OliTheme\Events;

use DateTimeImmutable;
use OliTheme\I18n\Language;

/**
 * DTO immuable représentant un événement du site.
 *
 * Toutes les propriétés sont en lecture seule et initialisées à la construction.
 *
 * @package OliTheme\Events
 *
 * @since 1.0.0
 */
final readonly class EventEntity
{
    /**
     * @param int $id Identifiant WordPress du post.
     * @param string $title Titre de l'événement.
     * @param string $description Description HTML rendue.
     * @param string|null $excerpt Extrait, null si absent.
     * @param string $slug Slug WordPress de l'événement.
     * @param DateTimeImmutable $startDate Date et heure de début.
     * @param DateTimeImmutable|null $endDate Date et heure de fin, null si non définie.
     * @param string|null $location Lieu de l'événement, null si non défini.
     * @param string|null $address Adresse complète, null si non définie.
     * @param string|null $flyerUrl URL du flyer/visuel, null si absent.
     * @param string|null $registrationUrl URL d'inscription, null si absent.
     * @param string|null $price Tarif, null si non défini.
     * @param Language $language Langue de cet événement.
     * @param string $permalink URL publique de l'événement.
     * @param bool $isPast Indique si l'événement est passé.
     * @param bool $isOngoing Indique si l'événement est en cours.
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public ?string $excerpt,
        public string $slug,
        public DateTimeImmutable $startDate,
        public ?DateTimeImmutable $endDate,
        public ?string $location,
        public ?string $address,
        public ?string $flyerUrl,
        public ?string $registrationUrl,
        public ?string $price,
        public Language $language,
        public string $permalink,
        public bool $isPast,
        public bool $isOngoing,
    ) {
    }
}
