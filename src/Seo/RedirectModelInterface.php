<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Contrat du modèle de gestion des redirections HTTP.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
interface RedirectModelInterface
{
    /**
     * Recherche une règle de redirection par son URI source.
     */
    public function findBySource(string $source): ?RedirectEntity;

    /**
     * Retourne toutes les règles de redirection avec pagination.
     *
     * @return RedirectEntity[]
     */
    public function findAll(int $limit = 100, int $offset = 0): array;

    /**
     * Crée ou met à jour une règle de redirection (upsert par source).
     */
    public function save(string $source, string $target, int $code = 301): RedirectEntity;

    /**
     * Met à jour une règle existante par son identifiant (édition explicite).
     */
    public function update(int $id, string $source, string $target, int $code): RedirectEntity;

    /**
     * Supprime une règle de redirection par son identifiant.
     */
    public function delete(int $id): void;

    /**
     * Retourne le nombre total de redirections enregistrées.
     */
    public function count(): int;

    /**
     * Incrémente le compteur de déclenchements d'une règle.
     */
    public function incrementHits(int $id): void;
}
