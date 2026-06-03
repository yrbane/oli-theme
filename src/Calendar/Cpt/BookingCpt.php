<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Cpt;

/**
 * Enregistre le CPT `oli_booking`.
 *
 * Un post = une réservation. Le titre du post est synthétisé
 * `{service} — {client} — {date}` pour faciliter la lecture en liste admin.
 *
 * Métadonnées (`_oli_book_*`) :
 * - `_oli_book_start`        : timestamp début (UTC).
 * - `_oli_book_end`          : timestamp fin (UTC).
 * - `_oli_book_service_id`   : id du service réservé (option `oli_services`).
 * - `_oli_book_status`       : `pending` / `confirmed` / `cancelled`.
 * - `_oli_book_customer_name`
 * - `_oli_book_customer_email`
 * - `_oli_book_customer_phone`  (optionnel)
 * - `_oli_book_message`         (optionnel)
 * - `_oli_book_lang`            (code langue, ex. `fr`/`en`)
 * - `_oli_book_ip_hash`         (SHA-256 IP — pour rate limiting / audit, sans stocker l'IP brute)
 *
 * @package OliTheme\Calendar\Cpt
 *
 * @since 1.3.0
 */
final class BookingCpt
{
    public const POST_TYPE = 'oli_booking';

    public function register(): void
    {
        if (!\function_exists('register_post_type')) {
            return;
        }
        register_post_type(self::POST_TYPE, [
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'menu_icon'           => 'dashicons-tickets',
            'supports'            => ['title', 'custom-fields'],
            'has_archive'         => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'labels'              => [
                'name'          => __('Réservations', 'oli-theme'),
                'singular_name' => __('Réservation', 'oli-theme'),
                'add_new_item'  => __('Ajouter une réservation', 'oli-theme'),
                'edit_item'     => __('Modifier la réservation', 'oli-theme'),
                'menu_name'     => __('Réservations', 'oli-theme'),
            ],
        ]);
    }
}
