<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Container;
use OliTheme\Core\AssetManager;
use OliTheme\Core\HookRegistrar;
use OliTheme\Core\RequestContext;
use OliTheme\Core\ViewRenderer;
use OliTheme\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Tests du bootstrap principal du thème.
 */
final class ThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\stubs([
            'get_template_directory_uri' => 'https://example.test/wp-content/themes/oli-theme',
        ]);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Theme::reset();
        parent::tearDown();
    }

    public function test_it_should_register_core_services_in_container(): void
    {
        Functions\when('add_action')->justReturn(true);

        Theme::boot(sys_get_temp_dir());
        $container = Theme::container();

        self::assertInstanceOf(Container::class, $container);
        self::assertInstanceOf(ViewRenderer::class, $container->get(ViewRenderer::class));
        self::assertInstanceOf(AssetManager::class, $container->get(AssetManager::class));
        self::assertInstanceOf(RequestContext::class, $container->get(RequestContext::class));
        self::assertInstanceOf(HookRegistrar::class, $container->get(HookRegistrar::class));
    }

    public function test_it_should_register_enqueue_hook_on_boot(): void
    {
        Functions\expect('add_action')
            ->atLeast()->once()
            ->with('wp_enqueue_scripts', \Mockery::any());

        Theme::boot(sys_get_temp_dir());

        $this->addToAssertionCount(1);
    }

    public function test_it_should_be_idempotent_on_boot(): void
    {
        Functions\when('add_action')->justReturn(true);

        Theme::boot(sys_get_temp_dir());
        $first = Theme::container();

        Theme::boot(sys_get_temp_dir());
        $second = Theme::container();

        self::assertSame($first, $second);
    }

    public function test_it_should_resolve_i18n_services_from_container(): void
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('get_option')->justReturn(false);

        Theme::boot(\sys_get_temp_dir());
        $container = Theme::container();

        self::assertInstanceOf(\OliTheme\I18n\LanguageRegistry::class, $container->get(\OliTheme\I18n\LanguageRegistry::class));
        self::assertInstanceOf(\OliTheme\I18n\LanguageResolver::class, $container->get(\OliTheme\I18n\LanguageResolver::class));
        self::assertInstanceOf(\OliTheme\I18n\TranslationModel::class, $container->get(\OliTheme\I18n\TranslationModel::class));
    }
}
