<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use DateTimeImmutable;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;
use PHPUnit\Framework\TestCase;

final class PostEntityTest extends TestCase
{
    public function testItExposesEveryConstructorPropertyAsReadonly(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $publishedAt = new DateTimeImmutable('2026-05-05T10:00:00+00:00');

        $entity = new PostEntity(
            id: 42,
            type: 'post',
            title: 'Bonjour',
            content: '<p>Hello</p>',
            excerpt: 'Short',
            slug: 'bonjour',
            language: $french,
            featuredImageUrl: 'https://example.com/img.jpg',
            featuredImageAlt: 'Une image',
            permalink: 'https://example.com/fr/bonjour',
            publishedAt: $publishedAt,
            updatedAt: null,
            author: 'Olivier',
        );

        self::assertSame(42, $entity->id);
        self::assertSame('post', $entity->type);
        self::assertSame('Bonjour', $entity->title);
        self::assertSame('<p>Hello</p>', $entity->content);
        self::assertSame('Short', $entity->excerpt);
        self::assertSame('bonjour', $entity->slug);
        self::assertSame($french, $entity->language);
        self::assertSame('https://example.com/img.jpg', $entity->featuredImageUrl);
        self::assertSame('Une image', $entity->featuredImageAlt);
        self::assertSame('https://example.com/fr/bonjour', $entity->permalink);
        self::assertSame($publishedAt, $entity->publishedAt);
        self::assertNull($entity->updatedAt);
        self::assertSame('Olivier', $entity->author);
    }

    public function testItAcceptsMissingOptionalFields(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $entity = new PostEntity(
            id: 1,
            type: 'page',
            title: 'Accueil',
            content: '',
            excerpt: null,
            slug: 'accueil',
            language: $french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/fr/',
            publishedAt: new DateTimeImmutable('2026-01-01'),
            updatedAt: null,
            author: null,
        );

        self::assertNull($entity->excerpt);
        self::assertNull($entity->featuredImageUrl);
        self::assertNull($entity->featuredImageAlt);
        self::assertNull($entity->author);
    }
}
