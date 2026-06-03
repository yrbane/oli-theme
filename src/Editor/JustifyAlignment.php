<?php

declare(strict_types=1);

namespace OliTheme\Editor;

/**
 * Ajoute l'alignement « Justifié » dans la barre d'outils Gutenberg pour
 * les blocs `core/paragraph`, `core/heading` et `core/list`.
 *
 * Par défaut, Gutenberg propose `left | center | right`. Cette classe
 * étend le support `typography.textAlign` du block via le filtre WP
 * `block_type_metadata` (disponible depuis WP 6.5) pour ajouter
 * l'option `justify` dans le sélecteur d'alignement.
 *
 * Le rendu front est pris en charge par la classe `.has-text-align-justify`
 * que Gutenberg applique sur le markup généré, à styler en CSS.
 *
 * @package OliTheme\Editor
 *
 * @since 1.6.0
 */
final class JustifyAlignment
{
    /** Blocs natifs WP qui supportent textAlign et auxquels on ajoute `justify`. */
    private const TARGETS = [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/list-item',
        'core/verse',
        'core/quote',
        'core/preformatted',
    ];

    public function register(): void
    {
        add_filter('block_type_metadata', [$this, 'extendTextAlign']);
    }

    /**
     * Ajoute `justify` à la liste des valeurs autorisées pour `textAlign`
     * sur les blocs natifs supportés.
     *
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    public function extendTextAlign(array $metadata): array
    {
        $name = (string) ($metadata['name'] ?? '');
        if (!\in_array($name, self::TARGETS, true)) {
            return $metadata;
        }
        if (!isset($metadata['supports']) || !\is_array($metadata['supports'])) {
            $metadata['supports'] = [];
        }
        if (!isset($metadata['supports']['typography']) || !\is_array($metadata['supports']['typography'])) {
            $metadata['supports']['typography'] = [];
        }
        $metadata['supports']['typography']['textAlign'] = ['left', 'center', 'right', 'justify'];

        return $metadata;
    }
}
