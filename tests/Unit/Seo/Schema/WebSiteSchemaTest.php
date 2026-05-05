<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Schema;

use OliTheme\Seo\Schema\WebSiteSchema;
use PHPUnit\Framework\TestCase;

/**
 * Tests du WebSiteSchema.
 *
 * @package OliTheme\Tests\Unit\Seo\Schema
 *
 * @since 1.0.0
 */
final class WebSiteSchemaTest extends TestCase
{
    public function testToArrayWithoutSearchAction(): void
    {
        $schema = new WebSiteSchema('Mon Site', 'https://example.com');
        $result = $schema->toArray();

        self::assertSame('WebSite', $result['@type']);
        self::assertSame('https://example.com/#website', $result['@id']);
        self::assertSame('Mon Site', $result['name']);
        self::assertSame('https://example.com', $result['url']);
        self::assertArrayNotHasKey('potentialAction', $result);
    }

    public function testToArrayWithSearchAction(): void
    {
        $pattern = 'https://example.com/?s={search_term_string}';
        $schema = new WebSiteSchema('Mon Site', 'https://example.com/', $pattern);
        $result = $schema->toArray();

        self::assertSame('WebSite', $result['@type']);
        self::assertSame('https://example.com/#website', $result['@id']);
        self::assertArrayHasKey('potentialAction', $result);
        self::assertSame('SearchAction', $result['potentialAction']['@type']);
        self::assertSame($pattern, $result['potentialAction']['target']['urlTemplate']);
        self::assertSame('required name=search_term_string', $result['potentialAction']['query-input']);
    }
}
