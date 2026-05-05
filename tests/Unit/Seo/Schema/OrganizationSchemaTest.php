<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Schema;

use OliTheme\Seo\Schema\OrganizationSchema;
use PHPUnit\Framework\TestCase;

/**
 * Tests du OrganizationSchema.
 *
 * @package OliTheme\Tests\Unit\Seo\Schema
 *
 * @since 1.0.0
 */
final class OrganizationSchemaTest extends TestCase
{
    public function testToArrayMinimal(): void
    {
        $schema = new OrganizationSchema('Mon Orga', 'https://example.com');
        $result = $schema->toArray();

        self::assertSame('Organization', $result['@type']);
        self::assertSame('https://example.com/#organization', $result['@id']);
        self::assertSame('Mon Orga', $result['name']);
        self::assertSame('https://example.com', $result['url']);
        self::assertArrayNotHasKey('logo', $result);
        self::assertArrayNotHasKey('sameAs', $result);
    }

    public function testToArrayFull(): void
    {
        $schema = new OrganizationSchema(
            'Mon Orga',
            'https://example.com/',
            'https://example.com/logo.png',
            ['https://facebook.com/monorga', 'https://twitter.com/monorga'],
        );
        $result = $schema->toArray();

        self::assertSame('Organization', $result['@type']);
        self::assertSame('https://example.com/#organization', $result['@id']);
        self::assertSame('ImageObject', $result['logo']['@type']);
        self::assertSame('https://example.com/logo.png', $result['logo']['url']);
        self::assertCount(2, $result['sameAs']);
        self::assertSame('https://facebook.com/monorga', $result['sameAs'][0]);
    }
}
