<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\RendererInterface;
use OliTheme\Settings\SettingsBag;
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
        Functions\when('add_settings_field')->justReturn(null);

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
        $settings->method('all')->willReturn(SettingsBag::default());

        (new ThemeSettingsPage($renderer, $settings))->registerSettings();

        self::assertSame('oli_theme_settings_group', $capturedGroup);
        self::assertSame('oli_theme_settings', $capturedOptionKey);
        self::assertSame(6, $addSectionCount);
    }

    /**
     * Chaque section doit déclarer au moins un champ via `add_settings_field()` ;
     * sans cela, `do_settings_sections()` ne rend que les titres de section vides.
     * Régression critique : la page Identité du site n'affichait aucun champ.
     */
    public function testRegisterSettingsAddsFieldsForEachSection(): void
    {
        Functions\when('__')->returnArg(1);
        Functions\when('register_setting')->justReturn(null);
        Functions\when('add_settings_section')->justReturn(null);

        /** @var array<string, int> $fieldsPerPage */
        $fieldsPerPage = [];

        Functions\when('add_settings_field')->alias(
            static function (string $id, string $title, callable $callback, string $page, string $section) use (&$fieldsPerPage): void {
                $fieldsPerPage[$page] = ($fieldsPerPage[$page] ?? 0) + 1;
            },
        );

        $renderer = $this->createMock(RendererInterface::class);
        $settings = $this->createMock(ThemeSettingsModelInterface::class);
        $settings->method('all')->willReturn(SettingsBag::default());

        (new ThemeSettingsPage($renderer, $settings))->registerSettings();

        // Une page distincte par onglet pour que do_settings_sections puisse filtrer par tab.
        self::assertArrayHasKey('oli-theme-settings-banner', $fieldsPerPage);
        self::assertArrayHasKey('oli-theme-settings-languages', $fieldsPerPage);
        self::assertArrayHasKey('oli-theme-settings-social', $fieldsPerPage);
        self::assertArrayHasKey('oli-theme-settings-footer', $fieldsPerPage);
        self::assertArrayHasKey('oli-theme-settings-contact', $fieldsPerPage);
        self::assertArrayHasKey('oli-theme-settings-seo', $fieldsPerPage);

        // Au moins un champ par section : aucune section vide.
        foreach ($fieldsPerPage as $page => $count) {
            self::assertGreaterThan(0, $count, \sprintf('La page %s n\'a aucun champ.', $page));
        }
    }

    public function testSanitizeMergesWithExisting(): void
    {
        Functions\when('get_option')->justReturn(['banner' => ['logoId' => 7]]);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('sanitize_email')->returnArg(1);
        Functions\when('esc_url_raw')->returnArg(1);
        Functions\when('wp_kses_post')->returnArg(1);

        $renderer = $this->createMock(RendererInterface::class);
        $settings = $this->createMock(ThemeSettingsModelInterface::class);

        $page   = new ThemeSettingsPage($renderer, $settings);
        $result = $page->sanitize(['social' => ['facebook' => 'https://facebook.com/oli']]);

        self::assertSame(['logoId' => 7], $result['banner']);
        self::assertSame('https://facebook.com/oli', $result['social']['facebook']);
    }

    /**
     * Les URLs des réseaux sociaux doivent passer par esc_url_raw.
     */
    public function testSanitizeFiltersSocialUrls(): void
    {
        Functions\when('get_option')->justReturn([]);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('sanitize_email')->returnArg(1);
        Functions\when('wp_kses_post')->returnArg(1);

        $passedUrls = [];
        Functions\when('esc_url_raw')->alias(
            static function (string $url) use (&$passedUrls): string {
                $passedUrls[] = $url;

                return $url;
            },
        );

        $renderer = $this->createMock(RendererInterface::class);
        $settings = $this->createMock(ThemeSettingsModelInterface::class);

        $page = new ThemeSettingsPage($renderer, $settings);
        $page->sanitize([
            'social' => [
                'facebook'  => 'https://facebook.com/oli',
                'instagram' => 'https://instagram.com/oli',
            ],
        ]);

        self::assertContains('https://facebook.com/oli', $passedUrls);
        self::assertContains('https://instagram.com/oli', $passedUrls);
    }

    /**
     * L'email contact doit passer par sanitize_email.
     */
    public function testSanitizeFiltersContactEmail(): void
    {
        Functions\when('get_option')->justReturn([]);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('esc_url_raw')->returnArg(1);
        Functions\when('wp_kses_post')->returnArg(1);

        $passedEmails = [];
        Functions\when('sanitize_email')->alias(
            static function (string $email) use (&$passedEmails): string {
                $passedEmails[] = $email;

                return $email;
            },
        );

        $renderer = $this->createMock(RendererInterface::class);
        $settings = $this->createMock(ThemeSettingsModelInterface::class);

        $page = new ThemeSettingsPage($renderer, $settings);
        $page->sanitize([
            'contact' => ['email' => 'hello@example.com'],
        ]);

        self::assertContains('hello@example.com', $passedEmails);
    }

    /**
     * Les checkboxes ne sont pas envoyées par le navigateur quand décochées.
     * Le sanitize doit produire false (et non null) pour ces champs.
     */
    public function testSanitizeNormalizesUncheckedBooleans(): void
    {
        Functions\when('get_option')->justReturn([]);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('sanitize_email')->returnArg(1);
        Functions\when('esc_url_raw')->returnArg(1);
        Functions\when('wp_kses_post')->returnArg(1);

        $renderer = $this->createMock(RendererInterface::class);
        $settings = $this->createMock(ThemeSettingsModelInterface::class);

        $page   = new ThemeSettingsPage($renderer, $settings);
        $result = $page->sanitize(['contact' => ['email' => 'a@b.fr']]);

        self::assertFalse($result['contact']['autoreplyEnabled']);
        self::assertFalse($result['contact']['loggingEnabled']);
    }
}
