<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use OliTheme\Settings\BannerSettings;
use OliTheme\Settings\ContactSettings;
use OliTheme\Settings\FooterSettings;
use OliTheme\Settings\LanguagesSettings;
use OliTheme\Settings\SeoSettings;
use OliTheme\Settings\SettingsBag;
use OliTheme\Settings\SocialSettings;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de SettingsBag (agrégateur des sous-settings).
 *
 * @package OliTheme\Tests\Unit\Settings
 *
 * @since 1.0.0
 */
final class SettingsBagTest extends TestCase
{
    public function testItExposesAllSubBags(): void
    {
        $banner = new BannerSettings(2, 3, ['fr' => 'Alt']);
        $footer = new FooterSettings('© {year}', false, true);
        $social = new SocialSettings('https://fb.com', null, null, null, null);
        $languages = new LanguagesSettings(['fr', 'en'], 'fr', LanguagesSettings::FALLBACK_HOME);
        $contact = new ContactSettings('test@example.com', null, false, true);
        $seo = new SeoSettings(5, 'handle', 'Org', 'https://logo.png', true, null);

        $bag = new SettingsBag(
            banner: $banner,
            footer: $footer,
            social: $social,
            languages: $languages,
            contact: $contact,
            seo: $seo,
        );

        self::assertSame($banner, $bag->banner);
        self::assertSame($footer, $bag->footer);
        self::assertSame($social, $bag->social);
        self::assertSame($languages, $bag->languages);
        self::assertSame($contact, $bag->contact);
        self::assertSame($seo, $bag->seo);
    }

    public function testDefaultProducesNeutralBag(): void
    {
        $bag = SettingsBag::default();

        // Banner
        self::assertNull($bag->banner->bannerDesktopId);
        self::assertNull($bag->banner->bannerMobileId);
        self::assertSame([], $bag->banner->altByLanguage);

        // Footer
        self::assertSame('© {year} {site}', $bag->footer->copyrightTemplate);
        self::assertTrue($bag->footer->showSocial);
        self::assertTrue($bag->footer->showMenu);

        // Social
        self::assertNull($bag->social->facebook);
        self::assertNull($bag->social->instagram);
        self::assertNull($bag->social->youtube);
        self::assertNull($bag->social->linkedin);
        self::assertNull($bag->social->twitter);

        // Languages
        self::assertSame(['fr'], $bag->languages->enabled);
        self::assertSame('fr', $bag->languages->default);
        self::assertSame(LanguagesSettings::FALLBACK_HOME, $bag->languages->fallbackBehavior);

        // Contact
        self::assertNull($bag->contact->email);
        self::assertNull($bag->contact->autoreplyBody);
        self::assertFalse($bag->contact->autoreplyEnabled);
        self::assertFalse($bag->contact->loggingEnabled);

        // Seo
        self::assertNull($bag->seo->ogImageId);
        self::assertNull($bag->seo->twitterHandle);
        self::assertNull($bag->seo->organizationName);
        self::assertNull($bag->seo->organizationLogoUrl);
        self::assertTrue($bag->seo->sitemapEnabled);
        self::assertNull($bag->seo->robotsTxtCustom);
    }
}
