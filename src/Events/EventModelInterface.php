<?php

declare(strict_types=1);

namespace OliTheme\Events;

use OliTheme\I18n\Language;

/**
 * Contrat du modèle de récupération des événements.
 *
 * @package OliTheme\Events
 *
 * @since 1.0.0
 */
interface EventModelInterface
{
    /**
     * Retourne les événements à venir pour une langue et une limite données.
     *
     * @return EventEntity[]
     */
    public function findUpcoming(Language $language, int $limit = 10): array;

    /**
     * Retourne les événements passés pour une langue et une limite données.
     *
     * @return EventEntity[]
     */
    public function findPast(Language $language, int $limit = 10): array;

    /**
     * Retourne un événement par son identifiant WordPress, ou null si introuvable.
     */
    public function findById(int $id): ?EventEntity;

    /**
     * Retourne un événement par son slug et sa langue, ou null si introuvable.
     */
    public function findBySlug(string $slug, Language $language): ?EventEntity;
}
