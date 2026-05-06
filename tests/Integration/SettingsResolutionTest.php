<?php

declare(strict_types=1);

namespace OliTheme\Tests\Integration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Settings\SettingsBag;
use OliTheme\Settings\ThemeSettingsModelInterface;
use OliTheme\Settings\ThemeSettingsPage;
use OliTheme\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie qu'après le boot complet du thème, le module Settings est résolvable
 * et son `all()` retourne un SettingsBag exploitable.
 */
final class SettingsResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Theme::reset();

        $GLOBALS['wpdb'] = new class () {
            public string $prefix = 'wp_';
            public function get_charset_collate(): string
            {
                return '';
            }
            public function get_var(): ?string
            {
                return null;
            }
        };
    }

    protected function tearDown(): void
    {
        Theme::reset();
        Monkey\tearDown();
        unset($GLOBALS['wpdb']);
        parent::tearDown();
    }

    public function testSettingsModelIsResolvableAfterBoot(): void
    {
        $themePath = \dirname(__DIR__, 2);

        Functions\when('wp_head')->justReturn('');
        Functions\when('wp_footer')->justReturn('');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('get_bloginfo')->justReturn('Oli');
        Functions\when('get_template_directory_uri')->justReturn('https://example.com/theme');
        Functions\when('get_template_directory')->justReturn($themePath);
        Functions\when('get_option')->justReturn(false);
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);

        Theme::boot($themePath);

        $model = Theme::container()->get(ThemeSettingsModelInterface::class);
        self::assertInstanceOf(ThemeSettingsModelInterface::class, $model);

        $bag = $model->all();
        self::assertInstanceOf(SettingsBag::class, $bag);
        self::assertSame(['fr'], $bag->languages->enabled);

        $page = Theme::container()->get(ThemeSettingsPage::class);
        self::assertInstanceOf(ThemeSettingsPage::class, $page);
    }
}
