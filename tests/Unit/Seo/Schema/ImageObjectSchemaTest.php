<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Schema;

use OliTheme\Seo\Schema\ImageObjectSchema;
use PHPUnit\Framework\TestCase;

/**
 * Tests du ImageObjectSchema.
 *
 * @package OliTheme\Tests\Unit\Seo\Schema
 *
 * @since 1.0.0
 */
final class ImageObjectSchemaTest extends TestCase
{
    public function testToArrayFull(): void
    {
        $schema = new ImageObjectSchema(
            'https://example.com/image.jpg',
            'Une belle photo',
            1200,
            630,
        );
        $result = $schema->toArray();

        self::assertSame('ImageObject', $result['@type']);
        self::assertSame('https://example.com/image.jpg', $result['url']);
        self::assertSame('Une belle photo', $result['caption']);
        self::assertSame(1200, $result['width']);
        self::assertSame(630, $result['height']);
    }

    public function testToArrayWithNullables(): void
    {
        $schema = new ImageObjectSchema('https://example.com/image.jpg');
        $result = $schema->toArray();

        self::assertSame('ImageObject', $result['@type']);
        self::assertSame('https://example.com/image.jpg', $result['url']);
        self::assertArrayNotHasKey('caption', $result);
        self::assertArrayNotHasKey('width', $result);
        self::assertArrayNotHasKey('height', $result);
    }
}
