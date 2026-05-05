<?php

declare(strict_types=1);

namespace OliTheme\Events;

use OliTheme\Core\PostTypeInterface;

/**
 * Enregistrement du Custom Post Type `oli_event`.
 *
 * Ce CPT est public, exposé dans l'interface d'administration et dans l'API REST.
 * Il dispose d'une archive et d'un slug personnalisé 'evenements'.
 *
 * @package OliTheme\Events
 *
 * @since 1.0.0
 */
final class EventCpt implements PostTypeInterface
{
    /**
     * Retourne le slug du Custom Post Type.
     */
    public function slug(): string
    {
        return 'oli_event';
    }

    /**
     * Enregistre le CPT oli_event via l'API WordPress.
     */
    public function register(): void
    {
        register_post_type('oli_event', [
            'public' => true,
            'show_in_rest' => true,
            'menu_position' => 21,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'],
            'taxonomies' => ['language'],
            'hierarchical' => false,
            'has_archive' => true,
            'rewrite' => ['slug' => 'evenements', 'with_front' => false],
            'labels' => [
                'name' => __('Événements', 'oli-theme'),
                'singular_name' => __('Événement', 'oli-theme'),
                'add_new_item' => __('Ajouter un événement', 'oli-theme'),
                'edit_item' => __('Modifier l\'événement', 'oli-theme'),
                'menu_name' => __('Événements', 'oli-theme'),
            ],
        ]);
    }
}
