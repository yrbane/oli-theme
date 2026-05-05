<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\TemplateRouter;
use PHPUnit\Framework\TestCase;

/**
 * Tests du résolveur qui aiguille WP vers les fichiers theme-bridge/*.php.
 *
 * Issue : github.com/yrbane/oli-theme/issues/4 — sans filtre `template_include`,
 * WordPress ne trouve que index.php à la racine et tous les rendus deviennent
 * l'archive des posts. Le router doit retourner le bon fichier bridge selon
 * la condition WP active (is_singular, is_page, is_archive, …).
 */
final class TemplateRouterTest extends TestCase
{
    private string $bridgePath;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->bridgePath = sys_get_temp_dir() . '/oli-bridge-' . uniqid();
        mkdir($this->bridgePath, 0o755, true);
        foreach (
            [
                '404.php',
                'archive-oli_event.php',
                'archive.php',
                'front-page.php',
                'page.php',
                'search.php',
                'single-oli_event.php',
                'single.php',
            ] as $file
        ) {
            file_put_contents($this->bridgePath . '/' . $file, '<?php');
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->bridgePath . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->bridgePath);
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_resolve_returns_front_page_when_static_front_page_active(): void
    {
        Functions\when('is_front_page')->justReturn(true);
        Functions\when('get_option')->alias(static fn (string $key, $default = false) => $key === 'page_on_front' ? 56 : $default);

        $router = new TemplateRouter($this->bridgePath);

        self::assertSame(
            $this->bridgePath . '/front-page.php',
            $router->resolve('original.php'),
        );
    }

    public function test_resolve_returns_single_event_when_oli_event(): void
    {
        Functions\when('is_front_page')->justReturn(false);
        Functions\when('get_option')->justReturn(0);
        Functions\when('is_singular')->alias(static fn (string $type) => $type === 'oli_event');
        Functions\when('is_post_type_archive')->justReturn(false);
        Functions\when('is_page')->justReturn(false);
        Functions\when('is_single')->justReturn(true);
        Functions\when('is_search')->justReturn(false);
        Functions\when('is_archive')->justReturn(false);
        Functions\when('is_404')->justReturn(false);

        $router = new TemplateRouter($this->bridgePath);

        self::assertSame(
            $this->bridgePath . '/single-oli_event.php',
            $router->resolve('original.php'),
        );
    }

    public function test_resolve_returns_archive_event_when_post_type_archive(): void
    {
        Functions\when('is_front_page')->justReturn(false);
        Functions\when('get_option')->justReturn(0);
        Functions\when('is_singular')->justReturn(false);
        Functions\when('is_post_type_archive')->alias(static fn (string $type) => $type === 'oli_event');
        Functions\when('is_page')->justReturn(false);
        Functions\when('is_single')->justReturn(false);
        Functions\when('is_search')->justReturn(false);
        Functions\when('is_archive')->justReturn(true);
        Functions\when('is_404')->justReturn(false);

        $router = new TemplateRouter($this->bridgePath);

        self::assertSame(
            $this->bridgePath . '/archive-oli_event.php',
            $router->resolve('original.php'),
        );
    }

    public function test_resolve_returns_page_for_page_request(): void
    {
        Functions\when('is_front_page')->justReturn(false);
        Functions\when('get_option')->justReturn(0);
        Functions\when('is_singular')->justReturn(false);
        Functions\when('is_post_type_archive')->justReturn(false);
        Functions\when('is_page')->justReturn(true);
        Functions\when('is_single')->justReturn(false);
        Functions\when('is_search')->justReturn(false);
        Functions\when('is_archive')->justReturn(false);
        Functions\when('is_404')->justReturn(false);

        $router = new TemplateRouter($this->bridgePath);

        self::assertSame($this->bridgePath . '/page.php', $router->resolve('original.php'));
    }

    public function test_resolve_returns_single_for_single_post(): void
    {
        Functions\when('is_front_page')->justReturn(false);
        Functions\when('get_option')->justReturn(0);
        Functions\when('is_singular')->justReturn(false);
        Functions\when('is_post_type_archive')->justReturn(false);
        Functions\when('is_page')->justReturn(false);
        Functions\when('is_single')->justReturn(true);
        Functions\when('is_search')->justReturn(false);
        Functions\when('is_archive')->justReturn(false);
        Functions\when('is_404')->justReturn(false);

        $router = new TemplateRouter($this->bridgePath);

        self::assertSame($this->bridgePath . '/single.php', $router->resolve('original.php'));
    }

    public function test_resolve_returns_search(): void
    {
        Functions\when('is_front_page')->justReturn(false);
        Functions\when('get_option')->justReturn(0);
        Functions\when('is_singular')->justReturn(false);
        Functions\when('is_post_type_archive')->justReturn(false);
        Functions\when('is_page')->justReturn(false);
        Functions\when('is_single')->justReturn(false);
        Functions\when('is_search')->justReturn(true);
        Functions\when('is_archive')->justReturn(false);
        Functions\when('is_404')->justReturn(false);

        $router = new TemplateRouter($this->bridgePath);

        self::assertSame($this->bridgePath . '/search.php', $router->resolve('original.php'));
    }

    public function test_resolve_returns_archive_for_generic_archive(): void
    {
        Functions\when('is_front_page')->justReturn(false);
        Functions\when('get_option')->justReturn(0);
        Functions\when('is_singular')->justReturn(false);
        Functions\when('is_post_type_archive')->justReturn(false);
        Functions\when('is_page')->justReturn(false);
        Functions\when('is_single')->justReturn(false);
        Functions\when('is_search')->justReturn(false);
        Functions\when('is_archive')->justReturn(true);
        Functions\when('is_404')->justReturn(false);

        $router = new TemplateRouter($this->bridgePath);

        self::assertSame($this->bridgePath . '/archive.php', $router->resolve('original.php'));
    }

    public function test_resolve_returns_404(): void
    {
        Functions\when('is_front_page')->justReturn(false);
        Functions\when('get_option')->justReturn(0);
        Functions\when('is_singular')->justReturn(false);
        Functions\when('is_post_type_archive')->justReturn(false);
        Functions\when('is_page')->justReturn(false);
        Functions\when('is_single')->justReturn(false);
        Functions\when('is_search')->justReturn(false);
        Functions\when('is_archive')->justReturn(false);
        Functions\when('is_404')->justReturn(true);

        $router = new TemplateRouter($this->bridgePath);

        self::assertSame($this->bridgePath . '/404.php', $router->resolve('original.php'));
    }

    public function test_resolve_falls_back_to_original_when_no_condition_matches(): void
    {
        Functions\when('is_front_page')->justReturn(false);
        Functions\when('get_option')->justReturn(0);
        Functions\when('is_singular')->justReturn(false);
        Functions\when('is_post_type_archive')->justReturn(false);
        Functions\when('is_page')->justReturn(false);
        Functions\when('is_single')->justReturn(false);
        Functions\when('is_search')->justReturn(false);
        Functions\when('is_archive')->justReturn(false);
        Functions\when('is_404')->justReturn(false);

        $router = new TemplateRouter($this->bridgePath);

        self::assertSame('original.php', $router->resolve('original.php'));
    }

    public function test_resolve_falls_back_to_original_if_bridge_file_missing(): void
    {
        // On supprime le fichier page.php mais on déclenche is_page().
        @unlink($this->bridgePath . '/page.php');

        Functions\when('is_front_page')->justReturn(false);
        Functions\when('get_option')->justReturn(0);
        Functions\when('is_singular')->justReturn(false);
        Functions\when('is_post_type_archive')->justReturn(false);
        Functions\when('is_page')->justReturn(true);
        Functions\when('is_single')->justReturn(false);
        Functions\when('is_search')->justReturn(false);
        Functions\when('is_archive')->justReturn(false);
        Functions\when('is_404')->justReturn(false);

        $router = new TemplateRouter($this->bridgePath);

        self::assertSame('original.php', $router->resolve('original.php'));
    }
}
