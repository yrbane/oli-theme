<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Schema;

use OliTheme\Seo\Schema\SchemaContext;
use OliTheme\Seo\Schema\SchemaInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests du SchemaContext.
 *
 * @package OliTheme\Tests\Unit\Seo\Schema
 *
 * @since 1.0.0
 */
final class SchemaContextTest extends TestCase
{
    public function testToJsonLdReturnsContextWithGraph(): void
    {
        $schema1 = $this->createMock(SchemaInterface::class);
        $schema1->method('toArray')->willReturn(['@type' => 'WebSite', 'name' => 'Foo']);

        $schema2 = $this->createMock(SchemaInterface::class);
        $schema2->method('toArray')->willReturn(['@type' => 'Organization', 'name' => 'Bar']);

        $context = new SchemaContext();
        $context->add($schema1);
        $context->add($schema2);

        $json = $context->toJsonLd();
        $decoded = json_decode($json, true);

        self::assertSame('https://schema.org', $decoded['@context']);
        self::assertIsArray($decoded['@graph']);
        self::assertCount(2, $decoded['@graph']);
    }

    public function testEmptyContextReturnsEmptyGraph(): void
    {
        $context = new SchemaContext();
        $json = $context->toJsonLd();
        $decoded = json_decode($json, true);

        self::assertSame('https://schema.org', $decoded['@context']);
        self::assertSame([], $decoded['@graph']);
    }
}
