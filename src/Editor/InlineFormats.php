<?php

declare(strict_types=1);

namespace OliTheme\Editor;

/**
 * Réactive le format inline `core/underline` (retiré du toolbar Gutenberg
 * core par défaut) et enregistre un format custom `oli/inline-color` qui
 * applique une couleur à la sélection uniquement (et pas au paragraphe
 * entier comme le fait l'attribut block-level).
 *
 * Issues : #20 (couleur inline) + #23 (souligné).
 *
 * @package OliTheme\Editor
 *
 * @since 1.7.0
 */
final class InlineFormats
{
    public function __construct(
        private readonly string $themeUri,
        private readonly string $version,
    ) {
    }

    public function register(): void
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        wp_enqueue_script(
            'oli-inline-formats',
            $this->themeUri . '/assets/js/editor/inline-formats.js',
            ['wp-rich-text', 'wp-block-editor', 'wp-element', 'wp-components', 'wp-i18n'],
            $this->version,
            true,
        );
    }
}
