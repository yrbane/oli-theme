<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gabarits;

use OliTheme\Gabarits\Zone;
use OliTheme\Gabarits\ZoneContent;
use OliTheme\Gabarits\ZoneType;
use PHPUnit\Framework\TestCase;

final class ZoneTest extends TestCase
{
    public function test_zone_type_label(): void
    {
        self::assertSame('Texte', ZoneType::Text->label());
        self::assertSame('Image', ZoneType::Image->label());
        self::assertSame('Galerie', ZoneType::Gallery->label());
    }

    public function test_zone_from_array_valid(): void
    {
        $z = Zone::fromArray(['id' => 'hero', 'type' => 'image', 'label' => 'Image héros']);
        self::assertNotNull($z);
        self::assertSame('hero', $z->id);
        self::assertSame(ZoneType::Image, $z->type);
        self::assertSame('Image héros', $z->label);
    }

    public function test_zone_from_array_sanitizes_id(): void
    {
        $z = Zone::fromArray(['id' => 'HERO/Block !', 'type' => 'text']);
        self::assertNotNull($z);
        self::assertSame('heroblock', $z->id);
    }

    public function test_zone_from_array_returns_null_on_invalid_type(): void
    {
        self::assertNull(Zone::fromArray(['id' => 'x', 'type' => 'video']));
        self::assertNull(Zone::fromArray(['id' => '', 'type' => 'text']));
    }

    public function test_zone_content_is_empty(): void
    {
        self::assertTrue((new ZoneContent(ZoneType::Text))->isEmpty());
        self::assertTrue((new ZoneContent(ZoneType::Text, text: '   '))->isEmpty());
        self::assertFalse((new ZoneContent(ZoneType::Text, text: 'hello'))->isEmpty());

        self::assertTrue((new ZoneContent(ZoneType::Image))->isEmpty());
        self::assertFalse((new ZoneContent(ZoneType::Image, imageId: 42))->isEmpty());

        self::assertTrue((new ZoneContent(ZoneType::Gallery))->isEmpty());
        self::assertFalse((new ZoneContent(ZoneType::Gallery, imageIds: [1, 2]))->isEmpty());
    }

    public function test_zone_content_serialization_roundtrip(): void
    {
        $cases = [
            new ZoneContent(ZoneType::Text, text: 'Hello'),
            new ZoneContent(ZoneType::Image, imageId: 123),
            new ZoneContent(ZoneType::Gallery, imageIds: [1, 2, 3]),
        ];
        foreach ($cases as $expected) {
            $hydrated = ZoneContent::fromArray($expected->toArray());
            self::assertEquals($expected, $hydrated);
        }
    }

    public function test_zone_content_gallery_filters_invalid_ids(): void
    {
        $c = ZoneContent::fromArray(['type' => 'gallery', 'imageIds' => ['12', 'abc', 0, -1, 7]]);
        self::assertNotNull($c);
        self::assertSame([12, 7], $c->imageIds);
    }
}
