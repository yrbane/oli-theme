<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use Brain\Monkey;
use Brain\Monkey\Functions;
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
        // Stubs WP requis par la bulle d'aide contextuelle injectée dans les titres de champs.
        Functions\when('admin_url')->alias(static fn (string $p = ''): string => '/wp-admin/' . $p);
        Functions\when('add_query_arg')->alias(static function (array $a, string $url = ''): string {
            return $url . '?' . http_build_query($a);
        });
        Functions\when('esc_url')->returnArg(1);
        Functions\when('esc_attr__')->returnArg(1);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
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

        $settings = $this->createMock(ThemeSettingsModelInterface::class);
        $settings->method('all')->willReturn(SettingsBag::default());

        (new ThemeSettingsPage($settings))->registerSettings();

        self::assertSame('oli_theme_settings_group', $capturedGroup);
        self::assertSame('oli_theme_settings', $capturedOptionKey);
        self::assertSame(5, $addSectionCount);
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

        $settings = $this->createMock(ThemeSettingsModelInterface::class);
        $settings->method('all')->willReturn(SettingsBag::default());

        (new ThemeSettingsPage($settings))->registerSettings();

        // Une page distincte par onglet pour que do_settings_sections puisse filtrer par tab.
        self::assertArrayHasKey('oli-theme-settings-banner', $fieldsPerPage);
        self::assertArrayHasKey('oli-theme-settings-languages', $fieldsPerPage);
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

        $settings = $this->createMock(ThemeSettingsModelInterface::class);

        $page   = new ThemeSettingsPage($settings);
        $result = $page->sanitize(['contact' => ['email' => 'hello@example.com']]);

        // La section banner non soumise est préservée par le merge de premier niveau.
        self::assertSame(['logoId' => 7], $result['banner']);
        self::assertSame('hello@example.com', $result['contact']['email']);
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

        $settings = $this->createMock(ThemeSettingsModelInterface::class);

        $page = new ThemeSettingsPage($settings);
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

        $settings = $this->createMock(ThemeSettingsModelInterface::class);

        $page   = new ThemeSettingsPage($settings);
        $result = $page->sanitize(['contact' => ['email' => 'a@b.fr']]);

        self::assertFalse($result['contact']['autoreplyEnabled']);
        self::assertFalse($result['contact']['loggingEnabled']);
    }
}
