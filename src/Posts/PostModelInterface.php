<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\I18n\Language;

/**
 * Contrat du modèle de posts/pages WordPress.
 *
 * Permet de découpler les controllers du modèle concret (final) et
 * facilite le mocking dans les tests unitaires.
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
interface PostModelInterface
{
    /**
     * Récupère une entité par son identifiant WP.
     */
    public function find(int $id): ?PostEntity;

    /**
     * Récupère une entité par son slug et sa langue.
     */
    public function findBySlug(string $slug, Language $language, string $type = 'post'): ?PostEntity;

    /**
     * @return PostEntity[]
     */
    public function findByLanguage(Language $language, int $limit = 10, string $type = 'post'): array;

    /**
     * Lit une meta sous-jacente avec valeur par défaut.
     */
    public function getMeta(int $id, string $key, mixed $default = null): mixed;
}
