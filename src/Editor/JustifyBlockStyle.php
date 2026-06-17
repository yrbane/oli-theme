<?php

declare(strict_types=1);

namespace OliTheme\Editor;

/**
 * Enregistre un style de bloc « Justifié » pour `core/paragraph` et
 * `core/heading`, sélectionnable dans le panneau Styles de l'inspecteur.
 *
 * Complète l'option d'alignement « Justifier » de la toolbar (voir
 * {@see JustifyAlignment}) par une entrée explicite dans les styles de bloc.
 * Le rendu est assuré par la classe `.is-style-justified` (CSS uniquement,
 * voir `base.css` pour le front et `editor-style.css` pour l'aperçu). Aucun JS.
 *
 * @package OliTheme\Editor
 *
 * @since 1.8.0
 */
final class JustifyBlockStyle
{
    /** Blocs natifs auxquels on propose le style « Justifié ». */
    private const TARGETS = [
        'core/paragraph',
        'core/heading',
    ];

    public function register(): void
    {
        add_action('init', [$this, 'registerStyle']);
    }

    public function registerStyle(): void
    {
        foreach (self::TARGETS as $block) {
            register_block_style($block, [
                'name'  => 'justified',
                'label' => __('Justifié', 'oli-theme'),
            ]);
        }
    }
}
