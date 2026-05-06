<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\AssetManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests d'enregistrement des assets (CSS / JS modules) avec versioning auto.
 */
final class AssetManagerTest extends TestCase
{
    private string $themePath;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        // Pas de variation par défaut : enqueueVariation() devient un no-op.
        Functions\when('get_option')->justReturn('');
        Functions\when('sanitize_key')->returnArg(1);
        $this->themePath = sys_get_temp_dir() . '/oli-asset-test-' . uniqid();
        mkdir($this->themePath . '/assets/css', recursive: true);
        mkdir($this->themePath . '/assets/js', recursive: true);
        file_put_contents($this->themePath . '/assets/css/main.css', 'body{}');
        file_put_contents($this->themePath . '/assets/css/admin.css', '/* admin */');
        file_put_contents($this->themePath . '/assets/js/main.js', '// js');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        $this->rrmdir($this->themePath);
        parent::tearDown();
    }

    public function test_it_should_enqueue_main_stylesheet_with_filemtime_version(): void
    {
        $expectedVersion = (string) filemtime($this->themePath . '/assets/css/main.css');

        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('oli-theme', 'https://example.test/wp-content/themes/oli/assets/css/main.css', [], $expectedVersion);

        Functions\expect('wp_enqueue_script_module')
            ->once();

        $manager = new AssetManager($this->themePath, 'https://example.test/wp-content/themes/oli');
        $manager->enqueueFront();

        $this->addToAssertionCount(1);
    }

    public function test_it_should_enqueue_main_script_module(): void
    {
        // Crée admin-bar.css pour que enqueueFront ne tombe pas en erreur ailleurs.
        file_put_contents($this->themePath . '/assets/css/admin-bar.css', '/* admin */');
        $expectedVersion = (string) filemtime($this->themePath . '/assets/js/main.js');

        // Deux wp_enqueue_style attendus : main.css + admin-bar.css.
        Functions\expect('wp_enqueue_style')->twice();
        Functions\expect('wp_enqueue_script_module')
            ->once()
            ->with('oli-theme', 'https://example.test/wp-content/themes/oli/assets/js/main.js', [], $expectedVersion);

        $manager = new AssetManager($this->themePath, 'https://example.test/wp-content/themes/oli');
        $manager->enqueueFront();

        $this->addToAssertionCount(1);
    }

    public function test_it_should_use_fallback_version_when_file_missing(): void
    {
        unlink($this->themePath . '/assets/css/main.css');
        // admin-bar.css absent aussi → fallback '1.0.0' également.

        Functions\expect('wp_enqueue_style')
            ->twice()
            ->with(\Mockery::type('string'), \Mockery::any(), \Mockery::type('array'), '1.0.0');
        Functions\expect('wp_enqueue_script_module')->once();

        $manager = new AssetManager($this->themePath, 'https://example.test/wp-content/themes/oli');
        $manager->enqueueFront();

        $this->addToAssertionCount(1);
    }

    public function testEnqueueFrontEnqueuesMainCssWithFilemtimeVersion(): void
    {
        $themePath = sys_get_temp_dir() . '/oli-theme-' . uniqid();
        mkdir($themePath . '/assets/css', 0o777, true);
        file_put_contents($themePath . '/assets/css/main.css', '/* test */');
        file_put_contents($themePath . '/assets/css/admin-bar.css', '/* admin bar */');
        $expectedVersion = (string) filemtime($themePath . '/assets/css/main.css');

        /** @var array<int, array{string, string, array<int, string>, string}> $calls */
        $calls = [];
        Functions\when('wp_enqueue_style')->alias(static function (string $handle, string $src, array $deps, string $ver) use (&$calls): void {
            $calls[] = [$handle, $src, $deps, $ver];
        });
        Functions\when('wp_enqueue_script_module')->justReturn();

        $manager = new AssetManager($themePath, 'https://example.com/wp-content/themes/oli-theme');
        $manager->enqueueFront();

        self::assertSame('oli-theme', $calls[0][0]);
        self::assertSame('https://example.com/wp-content/themes/oli-theme/assets/css/main.css', $calls[0][1]);
        self::assertSame([], $calls[0][2]);
        self::assertSame($expectedVersion, $calls[0][3]);

        unlink($themePath . '/assets/css/main.css');
        unlink($themePath . '/assets/css/admin-bar.css');
        rmdir($themePath . '/assets/css');
        rmdir($themePath . '/assets');
        rmdir($themePath);
    }

    public function testEnqueueFrontEnqueuesAdminBarStylesheetAfterMain(): void
    {
        $themePath = sys_get_temp_dir() . '/oli-theme-' . uniqid();
        mkdir($themePath . '/assets/css', 0o777, true);
        file_put_contents($themePath . '/assets/css/main.css', '/* test */');
        file_put_contents($themePath . '/assets/css/admin-bar.css', '/* admin bar */');

        /** @var array<int, array{string, string, array<int, string>, string}> $calls */
        $calls = [];
        Functions\when('wp_enqueue_style')->alias(static function (string $handle, string $src, array $deps, string $ver) use (&$calls): void {
            $calls[] = [$handle, $src, $deps, $ver];
        });
        Functions\when('wp_enqueue_script_module')->justReturn();

        (new AssetManager($themePath, 'https://example.com/wp-content/themes/oli-theme'))->enqueueFront();

        $handles = array_column($calls, 0);
        self::assertContains('oli-theme-admin-bar', $handles);

        // admin-bar doit être enqueué APRÈS main + dépendre de oli-theme (pas de variation).
        $adminBar = array_values(array_filter($calls, static fn ($c) => $c[0] === 'oli-theme-admin-bar'));
        self::assertSame(['oli-theme'], $adminBar[0][2]);

        unlink($themePath . '/assets/css/main.css');
        unlink($themePath . '/assets/css/admin-bar.css');
        rmdir($themePath . '/assets/css');
        rmdir($themePath . '/assets');
        rmdir($themePath);
    }

    public function testEnqueueFrontMakesAdminBarDependOnVariationWhenSelected(): void
    {
        $themePath = sys_get_temp_dir() . '/oli-theme-' . uniqid();
        mkdir($themePath . '/assets/css/variations', 0o777, true);
        file_put_contents($themePath . '/assets/css/main.css', '/* test */');
        file_put_contents($themePath . '/assets/css/admin-bar.css', '/* admin bar */');
        file_put_contents($themePath . '/assets/css/variations/dark.css', '/* dark */');

        Functions\when('get_option')->justReturn('dark');
        Functions\when('sanitize_key')->returnArg(1);

        /** @var array<int, array{string, string, array<int, string>, string}> $calls */
        $calls = [];
        Functions\when('wp_enqueue_style')->alias(static function (string $handle, string $src, array $deps, string $ver) use (&$calls): void {
            $calls[] = [$handle, $src, $deps, $ver];
        });
        Functions\when('wp_enqueue_script_module')->justReturn();

        (new AssetManager($themePath, 'https://example.com/wp-content/themes/oli-theme'))->enqueueFront();

        $adminBar = array_values(array_filter($calls, static fn ($c) => $c[0] === 'oli-theme-admin-bar'));
        self::assertCount(1, $adminBar);
        self::assertSame(['oli-theme-variation'], $adminBar[0][2]);

        unlink($themePath . '/assets/css/main.css');
        unlink($themePath . '/assets/css/admin-bar.css');
        unlink($themePath . '/assets/css/variations/dark.css');
        rmdir($themePath . '/assets/css/variations');
        rmdir($themePath . '/assets/css');
        rmdir($themePath . '/assets');
        rmdir($themePath);
    }

    public function testEnqueueAdminLoadsSeoAssetsOnEditScreens(): void
    {
        file_put_contents($this->themePath . '/assets/css/seo-admin.css', '/* css */');
        file_put_contents($this->themePath . '/assets/js/seo-metabox.js', '/* js */');

        $captured = [];
        Functions\when('wp_enqueue_style')->alias(static function (...$args) use (&$captured): void {
            $captured['style'][] = $args;
        });
        Functions\when('wp_enqueue_script_module')->alias(static function (...$args) use (&$captured): void {
            $captured['script'] = $args;
        });

        $manager = new AssetManager($this->themePath, 'https://example.test/oli');
        $manager->enqueueAdmin('post.php');

        // Recherche le handle SEO parmi tous les appels wp_enqueue_style.
        $styleHandles = array_column($captured['style'] ?? [], 0);
        self::assertContains('oli-theme-seo-admin', $styleHandles);
        self::assertSame('oli-theme-seo-admin', $captured['script'][0] ?? null);
    }

    public function testEnqueueAdminSkipsUnrelatedScreens(): void
    {
        Functions\when('wp_enqueue_style')->alias(static function (...$args): void {
            // Seul admin.css est autorisé, pas seo-admin.css.
        });
        Functions\expect('wp_enqueue_script_module')->never();

        $manager = new AssetManager($this->themePath, 'https://example.test/oli');
        $manager->enqueueAdmin('options-general.php');

        $this->addToAssertionCount(1);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
