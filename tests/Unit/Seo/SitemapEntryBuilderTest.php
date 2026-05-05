<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use OliTheme\Seo\SitemapEntryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests de SitemapEntryBuilder.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class SitemapEntryBuilderTest extends TestCase
{
    public function testBuildsMinimalEntry(): void
    {
        $builder = new SitemapEntryBuilder();
        $xml = $builder->build('https://example.com/page');

        self::assertStringContainsString('<url>', $xml);
        self::assertStringContainsString('<loc>https://example.com/page</loc>', $xml);
        self::assertStringContainsString('</url>', $xml);
        self::assertStringNotContainsString('<lastmod>', $xml);
        self::assertStringNotContainsString('<changefreq>', $xml);
        self::assertStringNotContainsString('<priority>', $xml);
    }

    public function testBuildsFullEntryWithHreflangs(): void
    {
        $builder = new SitemapEntryBuilder();
        $xml = $builder->build(
            loc: 'https://example.com/fr/page',
            lastmod: '2024-01-15T10:00:00+00:00',
            changefreq: 'weekly',
            priority: 0.8,
            hreflangs: [
                'fr' => 'https://example.com/fr/page',
                'en' => 'https://example.com/en/page',
            ],
        );

        self::assertStringContainsString('<loc>https://example.com/fr/page</loc>', $xml);
        self::assertStringContainsString('<lastmod>2024-01-15T10:00:00+00:00</lastmod>', $xml);
        self::assertStringContainsString('<changefreq>weekly</changefreq>', $xml);
        self::assertStringContainsString('<priority>0.8</priority>', $xml);
        self::assertStringContainsString('hreflang="fr"', $xml);
        self::assertStringContainsString('hreflang="en"', $xml);
        self::assertStringContainsString('href="https://example.com/fr/page"', $xml);
        self::assertStringContainsString('href="https://example.com/en/page"', $xml);
    }
}
