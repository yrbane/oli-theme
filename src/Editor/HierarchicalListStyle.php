<?php

declare(strict_types=1);

namespace OliTheme\Editor;

/**
 * Enregistre un style de bloc « Hiérarchique » pour `core/list` qui produit
 * une numérotation à la Word : 1, 1.1, 1.1.1, 1.1.2, 1.2 …
 *
 * Le rendu est assuré par les compteurs CSS (counter-reset / counter-increment)
 * dans `olikalari.css` sur `.wp-block-list.is-style-hierarchical`. Aucun JS.
 *
 * Issue : #22.
 *
 * @package OliTheme\Editor
 *
 * @since 1.7.0
 */
final class HierarchicalListStyle
{
    public function register(): void
    {
        add_action('init', [$this, 'registerStyle']);
    }

    public function registerStyle(): void
    {
        register_block_style('core/list', [
            'name'  => 'hierarchical',
            'label' => __('Hiérarchique (1, 1.1, 1.1.1)', 'oli-theme'),
        ]);
    }
}
