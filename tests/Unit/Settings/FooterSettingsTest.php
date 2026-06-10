<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use OliTheme\Settings\FooterSettings;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de FooterSettings.
 *
 * @package OliTheme\Tests\Unit\Settings
 *
 * @since 1.0.0
 */
final class FooterSettingsTest extends TestCase
{
    public function testItExposesAllProperties(): void
    {
        $settings = new FooterSettings(
            copyrightTemplate: '© {year} {site}',
            showSocial: false,
            showMenu: true,
            logoId: 42,
            text: 'Texte libre',
        );

        self::assertSame('© {year} {site}', $settings->copyrightTemplate);
        self::assertFalse($settings->showSocial);
        self::assertTrue($settings->showMenu);
        self::assertSame(42, $settings->logoId);
        self::assertSame('Texte libre', $settings->text);
    }

    public function testItAcceptsMinimalConstructorArguments(): void
    {
        $settings = new FooterSettings(
            copyrightTemplate: '',
            showSocial: false,
            showMenu: false,
        );

        self::assertSame('', $settings->copyrightTemplate);
        self::assertFalse($settings->showSocial);
        self::assertFalse($settings->showMenu);
        self::assertNull($settings->logoId);
        self::assertSame('', $settings->text);
    }
}
