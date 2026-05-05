<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;
use OliTheme\Seo\SeoMeta;
use OliTheme\Seo\TwitterCardBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests du TwitterCardBuilder.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class TwitterCardBuilderTest extends TestCase
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

    public function testBuildIncludesAllStandardKeys(): void
    {
        Functions\when('wp_get_attachment_image_src')->justReturn(['https://example.com/og.jpg', 1200, 630, false]);

        $meta = new SeoMeta(
            title: 'Mon titre Twitter',
            description: 'Ma description Twitter',
            ogImageId: 42,
            twitterCardType: 'summary_large_image',
        );

        $builder = new TwitterCardBuilder();
        $result = $builder->build($meta, null);

        self::assertSame('summary_large_image', $result['twitter:card']);
        self::assertSame('Mon titre Twitter', $result['twitter:title']);
        self::assertSame('Ma description Twitter', $result['twitter:description']);
        self::assertSame('https://example.com/og.jpg', $result['twitter:image']);
    }

    public function testBuildUsesEntityFallbacksWhenMetaEmpty(): void
    {
        $lang = new Language(
            code: 'fr',
            label: 'Français',
            nativeLabel: 'Français',
            flag: 'fr',
            locale: 'fr_FR',
        );

        $post = new PostEntity(
            id: 1,
            type: 'post',
            title: 'Titre de l\'article',
            content: '<p>Contenu</p>',
            excerpt: 'Extrait de l\'article',
            slug: 'article',
            language: $lang,
            featuredImageUrl: 'https://example.com/featured.jpg',
            featuredImageAlt: 'Image',
            permalink: 'https://example.com/fr/article',
            publishedAt: new DateTimeImmutable('2024-01-15T10:00:00+00:00'),
            updatedAt: null,
            author: null,
        );

        $meta = new SeoMeta();
        $builder = new TwitterCardBuilder();
        $result = $builder->build($meta, $post);

        self::assertSame('summary_large_image', $result['twitter:card']);
        self::assertSame('Titre de l\'article', $result['twitter:title']);
        self::assertSame('Extrait de l\'article', $result['twitter:description']);
        self::assertSame('https://example.com/featured.jpg', $result['twitter:image']);
    }
}
