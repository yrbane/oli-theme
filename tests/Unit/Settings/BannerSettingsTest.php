<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use OliTheme\Settings\BannerSettings;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de BannerSettings.
 *
 * @package OliTheme\Tests\Unit\Settings
 *
 * @since 1.0.0
 */
final class BannerSettingsTest extends TestCase
{
    public function testItExposesAllProperties(): void
    {
        $settings = new BannerSettings(
            bannerDesktopId: 20,
            bannerMobileId: 30,
            altByLanguage: ['fr' => 'Bannière', 'en' => 'Banner'],
        );

        self::assertSame(20, $settings->bannerDesktopId);
        self::assertSame(30, $settings->bannerMobileId);
        self::assertSame(['fr' => 'Bannière', 'en' => 'Banner'], $settings->altByLanguage);
    }

    public function testItAcceptsNullValues(): void
    {
        $settings = new BannerSettings(
            bannerDesktopId: null,
            bannerMobileId: null,
            altByLanguage: [],
        );

        self::assertNull($settings->bannerDesktopId);
        self::assertNull($settings->bannerMobileId);
        self::assertSame([], $settings->altByLanguage);
    }
}
