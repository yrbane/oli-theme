<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use OliTheme\Settings\ContactSettings;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ContactSettings.
 *
 * @package OliTheme\Tests\Unit\Settings
 *
 * @since 1.0.0
 */
final class ContactSettingsTest extends TestCase
{
    public function testItExposesAllProperties(): void
    {
        $settings = new ContactSettings(
            email: 'contact@example.com',
            autoreplyBody: 'Merci pour votre message.',
            autoreplyEnabled: true,
            loggingEnabled: true,
        );

        self::assertSame('contact@example.com', $settings->email);
        self::assertSame('Merci pour votre message.', $settings->autoreplyBody);
        self::assertTrue($settings->autoreplyEnabled);
        self::assertTrue($settings->loggingEnabled);
    }

    public function testItAcceptsNullValues(): void
    {
        $settings = new ContactSettings(
            email: null,
            autoreplyBody: null,
            autoreplyEnabled: false,
            loggingEnabled: false,
        );

        self::assertNull($settings->email);
        self::assertNull($settings->autoreplyBody);
        self::assertFalse($settings->autoreplyEnabled);
        self::assertFalse($settings->loggingEnabled);
    }
}
