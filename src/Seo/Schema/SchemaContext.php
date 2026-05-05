<?php

declare(strict_types=1);

namespace OliTheme\Seo\Schema;

/**
 * Agrège plusieurs schémas JSON-LD sous la racine @graph.
 *
 * @package OliTheme\Seo\Schema
 *
 * @since 1.0.0
 */
final class SchemaContext
{
    /** @var SchemaInterface[] */
    private array $schemas = [];

    /**
     * Ajoute un schéma JSON-LD au contexte.
     */
    public function add(SchemaInterface $schema): void
    {
        $this->schemas[] = $schema;
    }

    /**
     * Sérialise tous les schémas en JSON-LD avec la racine @graph.
     */
    public function toJsonLd(): string
    {
        $graph = array_map(static fn (SchemaInterface $s): array => $s->toArray(), $this->schemas);

        return (string) json_encode([
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }
}
