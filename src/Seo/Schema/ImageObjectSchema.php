<?php

declare(strict_types=1);

namespace OliTheme\Seo\Schema;

/**
 * Schéma JSON-LD pour le type ImageObject.
 *
 * @package OliTheme\Seo\Schema
 *
 * @since 1.0.0
 */
final readonly class ImageObjectSchema implements SchemaInterface
{
    /**
     * @param string $url URL de l'image.
     * @param string|null $caption Légende de l'image, null si absente.
     * @param int|null $width Largeur en pixels, null si inconnue.
     * @param int|null $height Hauteur en pixels, null si inconnue.
     */
    public function __construct(
        private string $url,
        private ?string $caption = null,
        private ?int $width = null,
        private ?int $height = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $schema = ['@type' => 'ImageObject', 'url' => $this->url];

        if ($this->caption !== null) {
            $schema['caption'] = $this->caption;
        }

        if ($this->width !== null) {
            $schema['width'] = $this->width;
        }

        if ($this->height !== null) {
            $schema['height'] = $this->height;
        }

        return $schema;
    }
}
