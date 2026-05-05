<?php

declare(strict_types=1);

namespace OliTheme\Seo\Schema;

/**
 * Schéma JSON-LD pour le type LocalBusiness.
 *
 * @package OliTheme\Seo\Schema
 *
 * @since 1.0.0
 */
final readonly class LocalBusinessSchema implements SchemaInterface
{
    /**
     * @param string $name Nom de l'établissement.
     * @param string $url URL du site de l'établissement.
     * @param string|null $address Adresse postale, null si absente.
     * @param string|null $phone Numéro de téléphone, null si absent.
     */
    public function __construct(
        private string $name,
        private string $url,
        private ?string $address = null,
        private ?string $phone = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $schema = [
            '@type' => 'LocalBusiness',
            '@id' => rtrim($this->url, '/') . '/#local-business',
            'name' => $this->name,
            'url' => $this->url,
        ];

        if ($this->address !== null) {
            $schema['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $this->address];
        }

        if ($this->phone !== null) {
            $schema['telephone'] = $this->phone;
        }

        return $schema;
    }
}
