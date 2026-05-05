<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use OliTheme\Settings\SeoSettings;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de SeoSettings.
 *
 * @package OliTheme\Tests\Unit\Settings
 *
 * @since 1.0.0
 */
final class SeoSettingsTest extends TestCase
{
    public function testItExposesAllProperties(): void
    {
        $settings = new SeoSettings(
            ogImageId: 42,
            twitterHandle: 'olikasite',
            organizationName: 'Oli Kalari',
            organizationLogoUrl: 'https://example.com/logo.png',
            sitemapEnabled: true,
            robotsTxtCustom: 'User-agent: *',
        );

        self::assertSame(42, $settings->ogImageId);
        self::assertSame('olikasite', $settings->twitterHandle);
        self::assertSame('Oli Kalari', $settings->organizationName);
        self::assertSame('https://example.com/logo.png', $settings->organizationLogoUrl);
        self::assertTrue($settings->sitemapEnabled);
        self::assertSame('User-agent: *', $settings->robotsTxtCustom);
    }

    public function testItAcceptsNullValues(): void
    {
        $settings = new SeoSettings(
            ogImageId: null,
            twitterHandle: null,
            organizationName: null,
            organizationLogoUrl: null,
            sitemapEnabled: false,
            robotsTxtCustom: null,
        );

        self::assertNull($settings->ogImageId);
        self::assertNull($settings->twitterHandle);
        self::assertNull($settings->organizationName);
        self::assertNull($settings->organizationLogoUrl);
        self::assertFalse($settings->sitemapEnabled);
        self::assertNull($settings->robotsTxtCustom);
    }
}
