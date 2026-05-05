<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use DateTimeImmutable;
use OliTheme\I18n\Language;

/**
 * DTO immuable représentant un contenu WordPress (page, post, ou autre CPT).
 *
 * Contient uniquement des scalaires, des DTO ou des objets de valeur.
 * Aucun template ne reçoit jamais d'objet `WP_Post` ; tous passent par cette
 * entité afin de découpler la couche de vues de WordPress.
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final readonly class PostEntity
{
    /**
     * @param int $id Identifiant WP du contenu.
     * @param string $type Type WP (`post`, `page`, ...).
     * @param string $title Titre brut, déjà décodé.
     * @param string $content HTML rendu du contenu (déjà filtré WP).
     * @param string|null $excerpt Extrait HTML, ou null.
     * @param string $slug Slug URL.
     * @param Language $language Langue résolue du contenu.
     * @param string|null $featuredImageUrl URL de l'image à la une (taille `large`).
     * @param string|null $featuredImageAlt Alt de l'image à la une.
     * @param string $permalink URL canonique.
     * @param DateTimeImmutable $publishedAt Date de publication.
     * @param DateTimeImmutable|null $updatedAt Date de dernière mise à jour.
     * @param string|null $author Nom d'affichage de l'auteur.
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public string $content,
        public ?string $excerpt,
        public string $slug,
        public Language $language,
        public ?string $featuredImageUrl,
        public ?string $featuredImageAlt,
        public string $permalink,
        public DateTimeImmutable $publishedAt,
        public ?DateTimeImmutable $updatedAt,
        public ?string $author,
    ) {
    }
}
