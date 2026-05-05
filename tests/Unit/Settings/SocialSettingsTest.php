<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use OliTheme\Settings\SocialSettings;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de SocialSettings.
 *
 * @package OliTheme\Tests\Unit\Settings
 *
 * @since 1.0.0
 */
final class SocialSettingsTest extends TestCase
{
    public function testItExposesAllProperties(): void
    {
        $settings = new SocialSettings(
            facebook: 'https://facebook.com/oli',
            instagram: 'https://instagram.com/oli',
            youtube: 'https://youtube.com/oli',
            linkedin: 'https://linkedin.com/company/oli',
            twitter: 'https://twitter.com/oli',
        );

        self::assertSame('https://facebook.com/oli', $settings->facebook);
        self::assertSame('https://instagram.com/oli', $settings->instagram);
        self::assertSame('https://youtube.com/oli', $settings->youtube);
        self::assertSame('https://linkedin.com/company/oli', $settings->linkedin);
        self::assertSame('https://twitter.com/oli', $settings->twitter);
    }

    public function testItAcceptsAllNulls(): void
    {
        $settings = new SocialSettings(
            facebook: null,
            instagram: null,
            youtube: null,
            linkedin: null,
            twitter: null,
        );

        self::assertNull($settings->facebook);
        self::assertNull($settings->instagram);
        self::assertNull($settings->youtube);
        self::assertNull($settings->linkedin);
        self::assertNull($settings->twitter);
    }
}
