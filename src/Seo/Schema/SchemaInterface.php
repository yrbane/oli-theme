<?php

declare(strict_types=1);

namespace OliTheme\Seo\Schema;

/**
 * Contrat des schémas JSON-LD individuels.
 *
 * @package OliTheme\Seo\Schema
 *
 * @since 1.0.0
 */
interface SchemaInterface
{
    /**
     * Retourne le schéma JSON-LD sous forme de tableau.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
