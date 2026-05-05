<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Schema;

use OliTheme\Seo\Schema\BreadcrumbListSchema;
use PHPUnit\Framework\TestCase;

/**
 * Tests du BreadcrumbListSchema.
 *
 * @package OliTheme\Tests\Unit\Seo\Schema
 *
 * @since 1.0.0
 */
final class BreadcrumbListSchemaTest extends TestCase
{
    public function testEmptyCrumbsReturnsEmptyItemListElement(): void
    {
        $schema = new BreadcrumbListSchema([]);
        $result = $schema->toArray();

        self::assertSame('BreadcrumbList', $result['@type']);
        self::assertSame([], $result['itemListElement']);
    }

    public function testWithThreeCrumbsReturnsThreeListItems(): void
    {
        $crumbs = [
            ['label' => 'Accueil', 'url' => 'https://example.com', 'isCurrent' => false],
            ['label' => 'Blog', 'url' => 'https://example.com/blog', 'isCurrent' => false],
            ['label' => 'Article', 'url' => 'https://example.com/blog/article', 'isCurrent' => true],
        ];

        $schema = new BreadcrumbListSchema($crumbs);
        $result = $schema->toArray();

        self::assertSame('BreadcrumbList', $result['@type']);
        self::assertCount(3, $result['itemListElement']);

        self::assertSame('ListItem', $result['itemListElement'][0]['@type']);
        self::assertSame(1, $result['itemListElement'][0]['position']);
        self::assertSame('Accueil', $result['itemListElement'][0]['name']);
        self::assertSame('https://example.com', $result['itemListElement'][0]['item']);

        self::assertSame(2, $result['itemListElement'][1]['position']);
        self::assertSame('Blog', $result['itemListElement'][1]['name']);

        self::assertSame(3, $result['itemListElement'][2]['position']);
        self::assertSame('Article', $result['itemListElement'][2]['name']);
    }
}
