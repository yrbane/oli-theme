<?php

declare(strict_types=1);

namespace OliTheme\Slides;

use OliTheme\Core\PostTypeInterface;

/**
 * Enregistrement du Custom Post Type `oli_slide`.
 *
 * Ce CPT est privé (non listable publiquement) mais exposé dans l'interface
 * d'administration et dans l'API REST afin de permettre la gestion des slides
 * depuis l'éditeur Gutenberg.
 *
 * @package OliTheme\Slides
 *
 * @since 1.0.0
 */
final class SlideCpt implements PostTypeInterface
{
    /**
     * Retourne le slug du Custom Post Type.
     */
    public function slug(): string
    {
        return 'oli_slide';
    }

    /**
     * Enregistre le CPT oli_slide via l'API WordPress.
     */
    public function register(): void
    {
        register_post_type('oli_slide', [
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'menu_position' => 22,
            'menu_icon' => 'dashicons-images-alt2',
            'supports' => ['title', 'thumbnail', 'excerpt', 'page-attributes'],
            'taxonomies' => ['language'],
            'has_archive' => false,
            'rewrite' => false,
            'labels' => [
                'name' => __('Slides', 'oli-theme'),
                'singular_name' => __('Slide', 'oli-theme'),
                'add_new_item' => __('Ajouter un slide', 'oli-theme'),
                'edit_item' => __('Modifier le slide', 'oli-theme'),
                'menu_name' => __('Slides', 'oli-theme'),
            ],
        ]);
    }
}
