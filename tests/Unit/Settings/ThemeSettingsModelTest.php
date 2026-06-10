<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Settings\LanguagesSettings;
use OliTheme\Settings\SettingsBag;
use OliTheme\Settings\ThemeSettingsModel;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ThemeSettingsModel.
 *
 * @package OliTheme\Tests\Unit\Settings
 *
 * @since 1.0.0
 */
final class ThemeSettingsModelTest extends TestCase
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

    public function testGetReturnsDefaultWhenAbsent(): void
    {
        Functions\when('get_option')->justReturn([]);

        $model = new ThemeSettingsModel();

        self::assertSame('default_value', $model->get('missing_key', 'default_value'));
    }

    public function testGetReturnsExistingValue(): void
    {
        Functions\when('get_option')->justReturn(['banner' => ['logoId' => 7]]);

        $model = new ThemeSettingsModel();

        self::assertSame(['logoId' => 7], $model->get('banner'));
    }

    public function testSetPersistsValue(): void
    {
        $capturedKey   = null;
        $capturedValue = null;

        Functions\when('get_option')->justReturn(['banner' => ['logoId' => 7]]);
        Functions\when('update_option')->alias(
            static function (string $key, mixed $value) use (&$capturedKey, &$capturedValue): bool {
                $capturedKey   = $key;
                $capturedValue = $value;

                return true;
            },
        );

        $model  = new ThemeSettingsModel();
        $result = $model->set('social', ['facebook' => 'https://fb.com']);

        self::assertTrue($result);
        self::assertSame('oli_theme_settings', $capturedKey);
        self::assertSame(['banner' => ['logoId' => 7], 'social' => ['facebook' => 'https://fb.com']], $capturedValue);
    }

    public function testAllReturnsDefaultsWhenEmpty(): void
    {
        Functions\when('get_option')->justReturn([]);

        $model = new ThemeSettingsModel();
        $bag   = $model->all();

        self::assertInstanceOf(SettingsBag::class, $bag);
        self::assertNull($bag->banner->logoId);
        self::assertSame('© {year} {site}', $bag->footer->copyrightTemplate);
        self::assertSame(['fr'], $bag->languages->enabled);
        self::assertTrue($bag->seo->sitemapEnabled);
    }

    public function testAllHydratesFromRaw(): void
    {
        Functions\when('get_option')->justReturn([
            'banner' => [
                'logoId'          => '10',
                'bannerDesktopId' => '20',
                'bannerMobileId'  => '30',
                'altByLanguage'   => ['fr' => 'Bannière', 'en' => 'Banner'],
            ],
            'footer' => [
                'copyrightTemplate' => '© {year} Oli',
                'showSocial'        => false,
                'showMenu'          => true,
            ],
            'social' => [
                'facebook'  => 'https://fb.com/oli',
                'instagram' => '',
                'youtube'   => null,
                'linkedin'  => 'https://linkedin.com/oli',
                'twitter'   => 'https://twitter.com/oli',
            ],
            'languages' => [
                'enabled'          => ['fr', 'en'],
                'default'          => 'en',
                'fallbackBehavior' => LanguagesSettings::FALLBACK_SHOW_SOURCE,
            ],
            'contact' => [
                'email'            => 'contact@example.com',
                'autoreplyBody'    => 'Merci.',
                'autoreplyEnabled' => true,
                'loggingEnabled'   => false,
            ],
            'seo' => [
                'ogImageId'           => '42',
                'twitterHandle'       => 'olikasite',
                'organizationName'    => 'Oli Kalari',
                'organizationLogoUrl' => 'https://example.com/logo.png',
                'sitemapEnabled'      => false,
                'robotsTxtCustom'     => 'User-agent: *',
            ],
        ]);

        $model = new ThemeSettingsModel();
        $bag   = $model->all();

        // Banner
        self::assertSame(10, $bag->banner->logoId);
        self::assertSame(20, $bag->banner->bannerDesktopId);
        self::assertSame(30, $bag->banner->bannerMobileId);
        self::assertSame(['fr' => 'Bannière', 'en' => 'Banner'], $bag->banner->altByLanguage);

        // Footer
        self::assertSame('© {year} Oli', $bag->footer->copyrightTemplate);
        self::assertFalse($bag->footer->showSocial);
        self::assertTrue($bag->footer->showMenu);

        // Social
        self::assertSame('https://fb.com/oli', $bag->social->facebook);
        self::assertNull($bag->social->instagram); // chaîne vide → null
        self::assertNull($bag->social->youtube);   // null → null
        self::assertSame('https://linkedin.com/oli', $bag->social->linkedin);
        self::assertSame('https://twitter.com/oli', $bag->social->twitter);

        // Languages
        self::assertSame(['fr', 'en'], $bag->languages->enabled);
        self::assertSame('en', $bag->languages->default);
        self::assertSame(LanguagesSettings::FALLBACK_SHOW_SOURCE, $bag->languages->fallbackBehavior);

        // Contact
        self::assertSame('contact@example.com', $bag->contact->email);
        self::assertSame('Merci.', $bag->contact->autoreplyBody);
        self::assertTrue($bag->contact->autoreplyEnabled);
        self::assertFalse($bag->contact->loggingEnabled);

        // Seo
        self::assertSame(42, $bag->seo->ogImageId);
        self::assertSame('olikasite', $bag->seo->twitterHandle);
        self::assertSame('Oli Kalari', $bag->seo->organizationName);
        self::assertSame('https://example.com/logo.png', $bag->seo->organizationLogoUrl);
        self::assertFalse($bag->seo->sitemapEnabled);
        self::assertSame('User-agent: *', $bag->seo->robotsTxtCustom);
    }
}
