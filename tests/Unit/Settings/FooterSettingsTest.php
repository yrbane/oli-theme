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
            legalByLanguage: ['fr' => '<p>Mentions légales</p>', 'en' => '<p>Legal</p>'],
            copyrightTemplate: '© {year} {site}',
            showLegal: true,
            showSocial: false,
            showMenu: true,
        );

        self::assertSame(['fr' => '<p>Mentions légales</p>', 'en' => '<p>Legal</p>'], $settings->legalByLanguage);
        self::assertSame('© {year} {site}', $settings->copyrightTemplate);
        self::assertTrue($settings->showLegal);
        self::assertFalse($settings->showSocial);
        self::assertTrue($settings->showMenu);
    }

    public function testItAcceptsEmptyLegal(): void
    {
        $settings = new FooterSettings(
            legalByLanguage: [],
            copyrightTemplate: '',
            showLegal: false,
            showSocial: false,
            showMenu: false,
        );

        self::assertSame([], $settings->legalByLanguage);
        self::assertSame('', $settings->copyrightTemplate);
        self::assertFalse($settings->showLegal);
        self::assertFalse($settings->showSocial);
        self::assertFalse($settings->showMenu);
    }
}
