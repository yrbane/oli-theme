<?php

declare(strict_types=1);

namespace OliTheme\Seo\Schema;

/**
 * Schéma JSON-LD pour le type Organization.
 *
 * @package OliTheme\Seo\Schema
 *
 * @since 1.0.0
 */
final readonly class OrganizationSchema implements SchemaInterface
{
    /**
     * @param string $name Nom de l'organisation.
     * @param string $url URL du site de l'organisation.
     * @param string|null $logoUrl URL du logo, null si absent.
     * @param string[] $sameAs Liste d'URLs de profils sociaux.
     */
    public function __construct(
        private string $name,
        private string $url,
        private ?string $logoUrl = null,
        private array $sameAs = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $schema = [
            '@type' => 'Organization',
            '@id' => rtrim($this->url, '/') . '/#organization',
            'name' => $this->name,
            'url' => $this->url,
        ];

        if ($this->logoUrl !== null) {
            $schema['logo'] = [
                '@type' => 'ImageObject',
                'url' => $this->logoUrl,
            ];
        }

        if ($this->sameAs !== []) {
            $schema['sameAs'] = array_values($this->sameAs);
        }

        return $schema;
    }
}
