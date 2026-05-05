<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use OliTheme\Settings\LanguagesSettings;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de LanguagesSettings.
 *
 * @package OliTheme\Tests\Unit\Settings
 *
 * @since 1.0.0
 */
final class LanguagesSettingsTest extends TestCase
{
    public function testItExposesAllProperties(): void
    {
        $settings = new LanguagesSettings(
            enabled: ['fr', 'en'],
            default: 'fr',
            fallbackBehavior: LanguagesSettings::FALLBACK_HOME,
        );

        self::assertSame(['fr', 'en'], $settings->enabled);
        self::assertSame('fr', $settings->default);
        self::assertSame(LanguagesSettings::FALLBACK_HOME, $settings->fallbackBehavior);
    }

    public function testConstantsAreCorrect(): void
    {
        self::assertSame('home', LanguagesSettings::FALLBACK_HOME);
        self::assertSame('show_source', LanguagesSettings::FALLBACK_SHOW_SOURCE);
        self::assertSame('message', LanguagesSettings::FALLBACK_MESSAGE);
    }

    public function testItAcceptsShowSourceFallback(): void
    {
        $settings = new LanguagesSettings(
            enabled: ['fr'],
            default: 'fr',
            fallbackBehavior: LanguagesSettings::FALLBACK_SHOW_SOURCE,
        );

        self::assertSame(LanguagesSettings::FALLBACK_SHOW_SOURCE, $settings->fallbackBehavior);
    }
}
