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
        $this->themePath = sys_get_temp_dir() . '/oli-asset-test-' . uniqid();
        mkdir($this->themePath . '/assets/css', recursive: true);
        mkdir($this->themePath . '/assets/js', recursive: true);
        file_put_contents($this->themePath . '/assets/css/main.css', 'body{}');
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
        $expectedVersion = (string) filemtime($this->themePath . '/assets/js/main.js');

        Functions\expect('wp_enqueue_style')->once();
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

        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('oli-theme', \Mockery::any(), [], '1.0.0');
        Functions\expect('wp_enqueue_script_module')->once();

        $manager = new AssetManager($this->themePath, 'https://example.test/wp-content/themes/oli');
        $manager->enqueueFront();

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
