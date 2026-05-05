<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Schema;

use DateTimeImmutable;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;
use OliTheme\Seo\Schema\ArticleSchema;
use PHPUnit\Framework\TestCase;

/**
 * Tests du ArticleSchema.
 *
 * @package OliTheme\Tests\Unit\Seo\Schema
 *
 * @since 1.0.0
 */
final class ArticleSchemaTest extends TestCase
{
    private Language $language;

    protected function setUp(): void
    {
        parent::setUp();

        $this->language = new Language(
            code: 'fr',
            label: 'Français',
            nativeLabel: 'Français',
            flag: 'fr',
            locale: 'fr_FR',
        );
    }

    public function testToArrayWithFullPost(): void
    {
        $post = new PostEntity(
            id: 1,
            type: 'post',
            title: 'Mon article',
            content: '<p>Contenu</p>',
            excerpt: 'Extrait',
            slug: 'mon-article',
            language: $this->language,
            featuredImageUrl: 'https://example.com/image.jpg',
            featuredImageAlt: 'Image',
            permalink: 'https://example.com/fr/mon-article',
            publishedAt: new DateTimeImmutable('2024-01-15T10:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2024-02-01T12:00:00+00:00'),
            author: 'Jean Dupont',
        );

        $schema = new ArticleSchema($post, 'https://example.com/#organization');
        $result = $schema->toArray();

        self::assertSame('Article', $result['@type']);
        self::assertSame('https://example.com/fr/mon-article#article', $result['@id']);
        self::assertSame('Mon article', $result['headline']);
        self::assertSame('fr', $result['inLanguage']);
        self::assertSame(['@type' => 'Person', 'name' => 'Jean Dupont'], $result['author']);
        self::assertSame(['@id' => 'https://example.com/#organization'], $result['publisher']);
        self::assertSame(['@type' => 'ImageObject', 'url' => 'https://example.com/image.jpg'], $result['image']);
        self::assertSame(['@id' => 'https://example.com/fr/mon-article'], $result['mainEntityOfPage']);
        self::assertStringContainsString('2024-02-01', $result['dateModified']);
    }

    public function testToArrayWithoutAuthorOrImage(): void
    {
        $post = new PostEntity(
            id: 2,
            type: 'post',
            title: 'Article sans auteur',
            content: '<p>Contenu</p>',
            excerpt: null,
            slug: 'article-sans-auteur',
            language: $this->language,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/fr/article-sans-auteur',
            publishedAt: new DateTimeImmutable('2024-03-01T10:00:00+00:00'),
            updatedAt: null,
            author: null,
        );

        $schema = new ArticleSchema($post, 'https://example.com/#organization');
        $result = $schema->toArray();

        self::assertSame('Article', $result['@type']);
        self::assertArrayNotHasKey('author', $result);
        self::assertArrayNotHasKey('image', $result);
        // dateModified falls back to publishedAt
        self::assertSame($result['datePublished'], $result['dateModified']);
    }
}
