<?php

declare(strict_types=1);

namespace OliTheme\Contact;

use OliTheme\Core\PostTypeInterface;

/**
 * Enregistrement du Custom Post Type `oli_contact_log`.
 *
 * Ce CPT est privé (non public) mais visible dans l'administration,
 * accessible depuis le menu Outils. La création de nouveaux logs est
 * réservée au code (capability 'do_not_allow') — seuls les administrateurs
 * peuvent consulter et supprimer des entrées.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
final class ContactLogCpt implements PostTypeInterface
{
    /**
     * Retourne le slug du Custom Post Type.
     */
    public function slug(): string
    {
        return 'oli_contact_log';
    }

    /**
     * Enregistre le CPT oli_contact_log via l'API WordPress.
     */
    public function register(): void
    {
        register_post_type('oli_contact_log', [
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'tools.php',
            'menu_position' => 90,
            'supports' => ['title', 'editor'],
            'taxonomies' => ['language'],
            'capability_type' => 'post',
            'capabilities' => ['create_posts' => 'do_not_allow'],
            'map_meta_cap' => true,
            'has_archive' => false,
            'rewrite' => false,
            'labels' => [
                'name' => __('Logs Contact', 'oli-theme'),
                'singular_name' => __('Log Contact', 'oli-theme'),
                'menu_name' => __('Logs Contact', 'oli-theme'),
            ],
        ]);
    }
}
