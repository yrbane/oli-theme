<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use OliTheme\Seo\SeoMeta;
use PHPUnit\Framework\TestCase;

/**
 * Tests du DTO SeoMeta.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class SeoMetaTest extends TestCase
{
    public function testItExposesAllProperties(): void
    {
        $meta = new SeoMeta(
            title: 'Mon titre SEO',
            description: 'Ma description',
            focusKeyword: 'mot-clé',
            additionalKeywords: ['php', 'wordpress'],
            ogImageId: 42,
            twitterCardType: 'summary',
            noindex: true,
            nofollow: true,
            canonical: 'https://example.com/page',
            priority: 0.8,
            changefreq: 'weekly',
            readabilityScore: 75,
            seoScore: 90,
        );

        self::assertSame('Mon titre SEO', $meta->title);
        self::assertSame('Ma description', $meta->description);
        self::assertSame('mot-clé', $meta->focusKeyword);
        self::assertSame(['php', 'wordpress'], $meta->additionalKeywords);
        self::assertSame(42, $meta->ogImageId);
        self::assertSame('summary', $meta->twitterCardType);
        self::assertTrue($meta->noindex);
        self::assertTrue($meta->nofollow);
        self::assertSame('https://example.com/page', $meta->canonical);
        self::assertSame(0.8, $meta->priority);
        self::assertSame('weekly', $meta->changefreq);
        self::assertSame(75, $meta->readabilityScore);
        self::assertSame(90, $meta->seoScore);
    }

    public function testItUsesSensibleDefaults(): void
    {
        $meta = new SeoMeta();

        self::assertNull($meta->title);
        self::assertNull($meta->description);
        self::assertNull($meta->focusKeyword);
        self::assertSame([], $meta->additionalKeywords);
        self::assertNull($meta->ogImageId);
        self::assertSame('summary_large_image', $meta->twitterCardType);
        self::assertFalse($meta->noindex);
        self::assertFalse($meta->nofollow);
        self::assertNull($meta->canonical);
        self::assertNull($meta->priority);
        self::assertNull($meta->changefreq);
        self::assertNull($meta->readabilityScore);
        self::assertNull($meta->seoScore);
    }
}
