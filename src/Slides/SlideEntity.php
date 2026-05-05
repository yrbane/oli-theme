<?php

declare(strict_types=1);

namespace OliTheme\Slides;

use DateTimeImmutable;
use OliTheme\I18n\Language;

/**
 * DTO immuable représentant un slide du carrousel.
 *
 * Toutes les propriétés sont en lecture seule et initialisées à la construction.
 *
 * @package OliTheme\Slides
 *
 * @since 1.0.0
 */
final readonly class SlideEntity
{
    /**
     * @param int $id Identifiant WordPress du post.
     * @param string $title Titre du slide.
     * @param string|null $caption Légende (extrait WP), null si absent.
     * @param string $imageUrl URL de l'image mise en avant.
     * @param string|null $imageAlt Texte alternatif de l'image, null si absent.
     * @param string|null $linkUrl URL du lien CTA, null si absent.
     * @param string|null $linkLabel Libellé du lien CTA, null si absent.
     * @param int $order Ordre d'affichage (menu_order WP).
     * @param DateTimeImmutable|null $expiresAt Date d'expiration, null si illimité.
     * @param Language $language Langue de ce slide.
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $caption,
        public string $imageUrl,
        public ?string $imageAlt,
        public ?string $linkUrl,
        public ?string $linkLabel,
        public int $order,
        public ?DateTimeImmutable $expiresAt,
        public Language $language,
    ) {
    }
}
