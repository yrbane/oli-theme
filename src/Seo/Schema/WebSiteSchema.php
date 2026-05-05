<?php

declare(strict_types=1);

namespace OliTheme\Seo\Schema;

/**
 * Schéma JSON-LD pour le type WebSite.
 *
 * Peut inclure une SearchAction si un patron d'URL de recherche est fourni.
 *
 * @package OliTheme\Seo\Schema
 *
 * @since 1.0.0
 */
final readonly class WebSiteSchema implements SchemaInterface
{
    /**
     * @param string $name Nom du site.
     * @param string $url URL racine du site.
     * @param string|null $searchUrlPattern Patron d'URL de recherche (ex: https://example.com/?s={search_term_string}).
     */
    public function __construct(
        private string $name,
        private string $url,
        private ?string $searchUrlPattern = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $schema = [
            '@type' => 'WebSite',
            '@id' => rtrim($this->url, '/') . '/#website',
            'name' => $this->name,
            'url' => $this->url,
        ];

        if ($this->searchUrlPattern !== null && $this->searchUrlPattern !== '') {
            $schema['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $this->searchUrlPattern,
                ],
                'query-input' => 'required name=search_term_string',
            ];
        }

        return $schema;
    }
}
