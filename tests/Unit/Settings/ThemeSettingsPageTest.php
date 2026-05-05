<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\RendererInterface;
use OliTheme\Settings\ThemeSettingsModelInterface;
use OliTheme\Settings\ThemeSettingsPage;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ThemeSettingsPage.
 *
 * @package OliTheme\Tests\Unit\Settings
 *
 * @since 1.0.0
 */
final class ThemeSettingsPageTest extends TestCase
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

    public function testRegisterAddsThemePage(): void
    {
        Functions\when('__')->returnArg(1);

        $capturedSlug       = null;
        $capturedCapability = null;

        Functions\when('add_theme_page')->alias(
            static function (string $pageTitle, string $menuTitle, string $capability, string $menuSlug) use (&$capturedSlug, &$capturedCapability): void {
                $capturedSlug       = $menuSlug;
                $capturedCapability = $capability;
            },
        );

        $renderer = $this->createMock(RendererInterface::class);
        $settings = $this->createMock(ThemeSettingsModelInterface::class);

        (new ThemeSettingsPage($renderer, $settings))->register();

        self::assertSame('oli-theme-settings', $capturedSlug);
        self::assertSame('manage_options', $capturedCapability);
    }

    public function testRegisterSettingsCallsRegisterSettingAndAddsSections(): void
    {
        Functions\when('__')->returnArg(1);

        $capturedGroup      = null;
        $capturedOptionKey  = null;
        $addSectionCount    = 0;

        Functions\when('register_setting')->alias(
            static function (string $group, string $optionKey) use (&$capturedGroup, &$capturedOptionKey): void {
                $capturedGroup     = $group;
                $capturedOptionKey = $optionKey;
            },
        );

        Functions\when('add_settings_section')->alias(
            static function () use (&$addSectionCount): void {
                ++$addSectionCount;
            },
        );

        $renderer = $this->createMock(RendererInterface::class);
        $settings = $this->createMock(ThemeSettingsModelInterface::class);

        (new ThemeSettingsPage($renderer, $settings))->registerSettings();

        self::assertSame('oli_theme_settings_group', $capturedGroup);
        self::assertSame('oli_theme_settings', $capturedOptionKey);
        self::assertSame(6, $addSectionCount);
    }

    public function testSanitizeMergesWithExisting(): void
    {
        Functions\when('get_option')->justReturn(['banner' => ['logoId' => 7]]);

        $renderer = $this->createMock(RendererInterface::class);
        $settings = $this->createMock(ThemeSettingsModelInterface::class);

        $page   = new ThemeSettingsPage($renderer, $settings);
        $result = $page->sanitize(['social' => ['facebook' => 'fb']]);

        self::assertSame([
            'banner' => ['logoId' => 7],
            'social' => ['facebook' => 'fb'],
        ], $result);
    }
}
