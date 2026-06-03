<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Cpt;

/**
 * Enregistre le CPT `oli_availability`.
 *
 * Un post = un créneau d'indisponibilité (`type=blocked`) ou un événement
 * personnel d'Olivier qui occupe un créneau sans qu'il y ait de réservation
 * publique associée (`type=event`).
 *
 * Métadonnées attendues (`_oli_*` pour ne pas collisionner avec WordPress) :
 * - `_oli_avail_start`  : timestamp début (UTC, secondes).
 * - `_oli_avail_end`    : timestamp fin (UTC, secondes).
 * - `_oli_avail_type`   : `blocked` ou `event`.
 * - `_oli_avail_source` : `manual` ou `ics:{url-hash}` (créneau importé).
 *
 * @package OliTheme\Calendar\Cpt
 *
 * @since 1.3.0
 */
final class AvailabilityCpt
{
    public const POST_TYPE = 'oli_availability';

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
            'menu_icon'           => 'dashicons-calendar-alt',
            'supports'            => ['title', 'custom-fields'],
            'has_archive'         => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'labels'              => [
                'name'          => __('Indisponibilités', 'oli-theme'),
                'singular_name' => __('Indisponibilité', 'oli-theme'),
                'add_new_item'  => __('Ajouter une indisponibilité', 'oli-theme'),
                'edit_item'     => __('Modifier l\'indisponibilité', 'oli-theme'),
                'menu_name'     => __('Indisponibilités', 'oli-theme'),
            ],
        ]);
    }
}
