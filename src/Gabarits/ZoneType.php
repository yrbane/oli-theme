<?php

declare(strict_types=1);

namespace OliTheme\Gabarits;

/**
 * Types de contenu acceptés par une zone d'un gabarit.
 *
 * @package OliTheme\Gabarits
 *
 * @since 1.5.0
 */
enum ZoneType: string
{
    /** Texte enrichi (HTML autorisé via wp_kses_post). */
    case Text = 'text';
    /** Image unique (attachment id). */
    case Image = 'image';
    /** Galerie d'images (list<int> d'attachment ids). */
    case Gallery = 'gallery';

    public function label(): string
    {
        return match ($this) {
            self::Text    => 'Texte',
            self::Image   => 'Image',
            self::Gallery => 'Galerie',
        };
    }
}
