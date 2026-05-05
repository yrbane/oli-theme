<?php

declare(strict_types=1);

namespace OliTheme\Seo\Schema;

/**
 * Schéma JSON-LD pour le type Person.
 *
 * @package OliTheme\Seo\Schema
 *
 * @since 1.0.0
 */
final readonly class PersonSchema implements SchemaInterface
{
    /**
     * @param string $name Nom de la personne.
     * @param string|null $url URL de la page de la personne, null si absent.
     * @param string[] $sameAs Liste d'URLs de profils sociaux.
     */
    public function __construct(
        private string $name,
        private ?string $url = null,
        private array $sameAs = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $schema = ['@type' => 'Person', 'name' => $this->name];

        if ($this->url !== null) {
            $schema['@id'] = rtrim($this->url, '/') . '#person-' . urlencode($this->name);
            $schema['url'] = $this->url;
        }

        if ($this->sameAs !== []) {
            $schema['sameAs'] = array_values($this->sameAs);
        }

        return $schema;
    }
}
