<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use OliTheme\Seo\SitemapIndexBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests de SitemapIndexBuilder.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class SitemapIndexBuilderTest extends TestCase
{
    public function testBuildsIndexWithTwoSitemaps(): void
    {
        $builder = new SitemapIndexBuilder();
        $xml = $builder->build([
            'https://example.com/sitemap-post-fr.xml',
            'https://example.com/sitemap-post-en.xml',
        ]);

        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<sitemapindex', $xml);
        self::assertStringContainsString('https://example.com/sitemap-post-fr.xml', $xml);
        self::assertStringContainsString('https://example.com/sitemap-post-en.xml', $xml);
        self::assertStringContainsString('</sitemapindex>', $xml);
        self::assertSame(2, substr_count($xml, '<sitemap>'));
    }
}
