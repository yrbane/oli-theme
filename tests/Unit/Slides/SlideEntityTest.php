<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Slides;

use DateTimeImmutable;
use OliTheme\I18n\Language;
use OliTheme\Slides\SlideEntity;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de SlideEntity (DTO immuable de slide).
 *
 * @package OliTheme\Tests\Unit\Slides
 *
 * @since 1.0.0
 */
final class SlideEntityTest extends TestCase
{
    private Language $french;

    protected function setUp(): void
    {
        parent::setUp();
        $this->french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
    }

    public function testItExposesAllProperties(): void
    {
        $expires = new DateTimeImmutable('2026-12-31 23:59:59');

        $entity = new SlideEntity(
            id: 1,
            title: 'Mon slide',
            caption: 'Une légende',
            imageUrl: 'https://cdn/slide.jpg',
            imageAlt: 'Alt du slide',
            linkUrl: 'https://example.com',
            linkLabel: 'En savoir plus',
            order: 3,
            expiresAt: $expires,
            language: $this->french,
        );

        self::assertSame(1, $entity->id);
        self::assertSame('Mon slide', $entity->title);
        self::assertSame('Une légende', $entity->caption);
        self::assertSame('https://cdn/slide.jpg', $entity->imageUrl);
        self::assertSame('Alt du slide', $entity->imageAlt);
        self::assertSame('https://example.com', $entity->linkUrl);
        self::assertSame('En savoir plus', $entity->linkLabel);
        self::assertSame(3, $entity->order);
        self::assertSame($expires, $entity->expiresAt);
        self::assertSame($this->french, $entity->language);
    }

    public function testItAcceptsNullableOptionals(): void
    {
        $entity = new SlideEntity(
            id: 2,
            title: 'Slide minimal',
            caption: null,
            imageUrl: 'https://cdn/img.jpg',
            imageAlt: null,
            linkUrl: null,
            linkLabel: null,
            order: 0,
            expiresAt: null,
            language: $this->french,
        );

        self::assertNull($entity->caption);
        self::assertNull($entity->imageAlt);
        self::assertNull($entity->linkUrl);
        self::assertNull($entity->linkLabel);
        self::assertNull($entity->expiresAt);
    }
}
