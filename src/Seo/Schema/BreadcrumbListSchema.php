<?php

declare(strict_types=1);

namespace OliTheme\Seo\Schema;

/**
 * Schéma JSON-LD pour le type BreadcrumbList.
 *
 * Accepte un tableau de fils d'Ariane sous forme de tableaux associatifs.
 *
 * @package OliTheme\Seo\Schema
 *
 * @since 1.0.0
 */
final readonly class BreadcrumbListSchema implements SchemaInterface
{
    /**
     * @param array<int, array{label: string, url: string, isCurrent: bool}> $crumbs Liste des éléments du fil d'Ariane.
     */
    public function __construct(private array $crumbs)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $items = [];

        foreach ($this->crumbs as $position => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $crumb['label'],
                'item' => $crumb['url'],
            ];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
}
