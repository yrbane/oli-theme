<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;
use OliTheme\Seo\OpenGraphBuilder;
use OliTheme\Seo\SeoMeta;
use PHPUnit\Framework\TestCase;

/**
 * Tests du OpenGraphBuilder.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class OpenGraphBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testBuildForPostIncludesArticleTags(): void
    {
        Functions\when('get_bloginfo')->justReturn('Mon site');

        $meta = new SeoMeta(title: 'Titre OG', description: 'Description OG');
        $builder = new OpenGraphBuilder();
        $result = $builder->build($meta, $this->makePost(), $this->makeLang(), 'https://example.com/fr/mon-article');

        self::assertSame('article', $result['og:type']);
        self::assertSame('fr_FR', $result['og:locale']);
        self::assertSame('Titre OG', $result['og:title']);
        self::assertSame('Description OG', $result['og:description']);
        self::assertSame('https://example.com/fr/mon-article', $result['og:url']);
        self::assertSame('Mon site', $result['og:site_name']);
        self::assertArrayHasKey('article:published_time', $result);
        self::assertArrayHasKey('article:modified_time', $result);
        self::assertArrayHasKey('article:author', $result);
        self::assertSame('Jean Dupont', $result['article:author']);
    }

    public function testBuildForArchiveOmitsArticleTags(): void
    {
        Functions\when('get_bloginfo')->justReturn('Mon site');

        $meta = new SeoMeta();
        $builder = new OpenGraphBuilder();
        $result = $builder->build($meta, null, $this->makeLang(), 'https://example.com/');

        self::assertSame('website', $result['og:type']);
        self::assertArrayNotHasKey('article:published_time', $result);
        self::assertArrayNotHasKey('article:modified_time', $result);
        self::assertArrayNotHasKey('article:author', $result);
    }

    public function testBuildUsesOgImageOverride(): void
    {
        Functions\when('get_bloginfo')->justReturn('Mon site');
        Functions\when('wp_get_attachment_image_src')->justReturn(['https://example.com/og.jpg', 1200, 630, false]);

        $meta = new SeoMeta(ogImageId: 42);
        $builder = new OpenGraphBuilder();
        $result = $builder->build($meta, null, $this->makeLang(), 'https://example.com/');

        self::assertSame('https://example.com/og.jpg', $result['og:image']);
        self::assertSame(1200, $result['og:image:width']);
        self::assertSame(630, $result['og:image:height']);
    }

    private function makeLang(): Language
    {
        return new Language(
            code: 'fr',
            label: 'Français',
            nativeLabel: 'Français',
            flag: 'fr',
            locale: 'fr_FR',
        );
    }

    private function makePost(): PostEntity
    {
        return new PostEntity(
            id: 1,
            type: 'post',
            title: 'Mon article',
            content: '<p>Contenu</p>',
            excerpt: 'Extrait court',
            slug: 'mon-article',
            language: $this->makeLang(),
            featuredImageUrl: 'https://example.com/image.jpg',
            featuredImageAlt: 'Image',
            permalink: 'https://example.com/fr/mon-article',
            publishedAt: new DateTimeImmutable('2024-01-15T10:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2024-02-01T12:00:00+00:00'),
            author: 'Jean Dupont',
        );
    }
}
