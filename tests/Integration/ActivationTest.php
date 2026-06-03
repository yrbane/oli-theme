<?php

declare(strict_types=1);

namespace OliTheme\Tests\Integration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Test d'intégration : vérifie que le bootstrap du thème ne lève aucune
 * exception et que les hooks d'activation/désactivation tournent
 * sans erreur fatale (mocks Brain Monkey).
 */
final class ActivationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\stubs([
            'get_template_directory_uri' => 'https://example.test/wp-content/themes/oli-theme',
            'home_url'                   => 'https://example.test',
            'wp_head'                    => '',
            'wp_footer'                  => '',
        ]);
        Functions\when('get_bloginfo')->alias(static function (string $show): string {
            return match ($show) {
                'name'    => 'Test Site',
                'charset' => 'UTF-8',
                default   => '',
            };
        });
        Functions\when('add_action')->justReturn(true);
        Functions\when('get_option')->alias(static fn (string $k, $d = false) => $k === 'oli_languages' ? ['enabled' => ['fr', 'en', 'it', 'es'], 'default' => 'fr'] : $d);
    }

    protected function tearDown(): void
    {
        Theme::reset();
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_boot_runs_without_throwing(): void
    {
        Theme::boot(\dirname(__DIR__, 2));
        self::assertNotNull(Theme::container());
    }

    public function test_activation_hook_calls_flush_rewrite_rules(): void
    {
        Functions\expect('flush_rewrite_rules')->once();
        Functions\expect('dbDelta')->once()->andReturn([]);
        Functions\when('update_option')->justReturn(true);
        Functions\when('delete_option')->justReturn(true);
        Functions\when('get_template_directory')->justReturn(sys_get_temp_dir() . '/oli-theme-activation-int-' . uniqid());

        $GLOBALS['wpdb'] = new class () {
            public string $prefix = 'wp_';

            public function get_charset_collate(): string
            {
                return 'DEFAULT CHARACTER SET utf8mb4';
            }
        };

        Theme::onActivation();

        unset($GLOBALS['wpdb']);
        $this->addToAssertionCount(1);
    }

    public function test_deactivation_hook_calls_flush_rewrite_rules(): void
    {
        Functions\expect('flush_rewrite_rules')->once();
        Theme::onDeactivation();
        $this->addToAssertionCount(1);
    }
}
