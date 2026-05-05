<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Schema;

use OliTheme\Seo\Schema\PersonSchema;
use PHPUnit\Framework\TestCase;

/**
 * Tests du PersonSchema.
 *
 * @package OliTheme\Tests\Unit\Seo\Schema
 *
 * @since 1.0.0
 */
final class PersonSchemaTest extends TestCase
{
    public function testToArrayFull(): void
    {
        $schema = new PersonSchema(
            'Jean Dupont',
            'https://example.com/auteur/jean-dupont',
            ['https://twitter.com/jean', 'https://linkedin.com/in/jean'],
        );
        $result = $schema->toArray();

        self::assertSame('Person', $result['@type']);
        self::assertSame('Jean Dupont', $result['name']);
        self::assertStringContainsString('#person-', $result['@id']);
        self::assertSame('https://example.com/auteur/jean-dupont', $result['url']);
        self::assertCount(2, $result['sameAs']);
    }

    public function testToArrayMinimalWithoutUrl(): void
    {
        $schema = new PersonSchema('Marie Martin');
        $result = $schema->toArray();

        self::assertSame('Person', $result['@type']);
        self::assertSame('Marie Martin', $result['name']);
        self::assertArrayNotHasKey('@id', $result);
        self::assertArrayNotHasKey('url', $result);
        self::assertArrayNotHasKey('sameAs', $result);
    }
}
