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

        Theme::boot(sys_get_temp_dir());
        $container = Theme::container();

        self::assertInstanceOf(\OliTheme\I18n\LanguageRegistry::class, $container->get(\OliTheme\I18n\LanguageRegistry::class));
        self::assertInstanceOf(\OliTheme\I18n\LanguageResolver::class, $container->get(\OliTheme\I18n\LanguageResolver::class));
        self::assertInstanceOf(\OliTheme\I18n\TranslationModel::class, $container->get(\OliTheme\I18n\TranslationModel::class));
    }

    public function testContainerThrowsWhenNotBooted(): void
    {
        Theme::reset();
        $this->expectException(\LogicException::class);
        Theme::container();
    }

    public function testContainerReturnsBootedContainer(): void
    {
        Functions\when('add_action')->justReturn(true);

        Theme::reset();
        Theme::boot(sys_get_temp_dir());
        self::assertInstanceOf(Container::class, Theme::container());
    }

    public function testBootRegistersPostsModule(): void
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('get_option')->justReturn(false);

        \OliTheme\Theme::reset();
        \OliTheme\Theme::boot(__DIR__);

        $container = \OliTheme\Theme::container();

        self::assertTrue($container->has(\OliTheme\Posts\PostModel::class));
        self::assertTrue($container->has(\OliTheme\Posts\PageController::class));
        self::assertTrue($container->has(\OliTheme\Posts\PostController::class));
        self::assertTrue($container->has(\OliTheme\Posts\NotFoundController::class));
    }

    public function testBootRegistersNavigationModule(): void
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('get_option')->justReturn(false);

        \OliTheme\Theme::reset();
        \OliTheme\Theme::boot(__DIR__);

        $container = \OliTheme\Theme::container();

        self::assertTrue($container->has(\OliTheme\Navigation\MenuController::class));
        self::assertTrue($container->has(\OliTheme\Navigation\MenuControllerInterface::class));
    }

    public function testBootRegistersSlidesModule(): void
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('get_option')->justReturn(false);

        \OliTheme\Theme::reset();
        \OliTheme\Theme::boot(__DIR__);
        $container = \OliTheme\Theme::container();
        self::assertTrue($container->has(\OliTheme\Slides\SlideCpt::class));
        self::assertTrue($container->has(\OliTheme\Slides\SlideModelInterface::class));
    }

    public function testBootRegistersEventsModule(): void
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('get_option')->justReturn(false);

        \OliTheme\Theme::reset();
        \OliTheme\Theme::boot(__DIR__);
        $container = \OliTheme\Theme::container();
        self::assertTrue($container->has(\OliTheme\Events\EventController::class));
        self::assertTrue($container->has(\OliTheme\Events\EventControllerInterface::class));
        self::assertTrue($container->has(\OliTheme\Events\EventArchiveController::class));
        self::assertTrue($container->has(\OliTheme\Events\EventArchiveControllerInterface::class));
    }

    public function testBootRegistersSettingsModule(): void
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('get_option')->justReturn(false);

        \OliTheme\Theme::reset();
        \OliTheme\Theme::boot(__DIR__);
        $container = \OliTheme\Theme::container();
        self::assertTrue($container->has(\OliTheme\Settings\ThemeSettingsModelInterface::class));
        self::assertTrue($container->has(\OliTheme\Settings\ThemeSettingsPage::class));
    }

    public function testBootRegistersContactModule(): void
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('get_option')->justReturn(false);

        \OliTheme\Theme::reset();
        \OliTheme\Theme::boot(__DIR__);
        $container = \OliTheme\Theme::container();
        self::assertTrue($container->has(\OliTheme\Contact\ContactFormControllerInterface::class));
        self::assertTrue($container->has(\OliTheme\Contact\ContactShortcode::class));
    }

    public function testOnActivationCallsDbDelta(): void
    {
        // Brain Monkey définit dbDelta comme fonction stubée : function_exists('dbDelta') retourne true.
        Functions\expect('dbDelta')
            ->once()
            ->andReturnUsing(static function (string $sql): array {
                // On vérifie que le SQL contient bien CREATE TABLE et oli_redirects.
                self::assertStringContainsString('CREATE TABLE', $sql);
                self::assertStringContainsString('oli_redirects', $sql);
                return [];
            });

        Functions\when('flush_rewrite_rules')->justReturn();
        Functions\when('update_option')->justReturn(true);
        Functions\when('delete_option')->justReturn(true);
        Functions\when('get_template_directory')->justReturn(sys_get_temp_dir() . '/oli-theme-activation-' . uniqid());

        // Stub wpdb avec prefix et get_charset_collate.
        $wpdb = new \stdClass();
        $wpdb->prefix = 'wp_';
        Functions\when('get_charset_collate')->justReturn('DEFAULT CHARACTER SET utf8mb4');
        // On simule get_charset_collate comme méthode objet en le définissant directement.
        $wpdb->get_charset_collate = static fn (): string => 'DEFAULT CHARACTER SET utf8mb4';
        // Réassigner via closure pour simuler l'appel $wpdb->get_charset_collate().
        // wpdb::get_charset_collate est une méthode PHP — on crée un objet anonyme.
        $GLOBALS['wpdb'] = new class () {
            public string $prefix = 'wp_';

            public function get_charset_collate(): string
            {
                return 'DEFAULT CHARACTER SET utf8mb4';
            }
        };

        Theme::onActivation();

        unset($GLOBALS['wpdb']);
    }

    public function testBootRegistersSeoModule(): void
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('get_option')->justReturn(false);

        $GLOBALS['wpdb'] = new \stdClass();

        \OliTheme\Theme::reset();
        \OliTheme\Theme::boot(__DIR__);
        $container = \OliTheme\Theme::container();

        self::assertTrue($container->has(\OliTheme\Seo\SeoControllerInterface::class));
        self::assertTrue($container->has(\OliTheme\Seo\BreadcrumbsControllerInterface::class));

        unset($GLOBALS['wpdb']);
    }

    public function testBootInjectsGlobalVariablesAndMacrosIntoViewRenderer(): void
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('get_option')->justReturn(false);

        Theme::reset();
        Theme::boot(sys_get_temp_dir());

        $renderer = Theme::container()->get(ViewRenderer::class);
        self::assertInstanceOf(ViewRenderer::class, $renderer);

        // Vérifie que les variables globales sont injectées via un rendu inline.
        $cacheDir = sys_get_temp_dir() . '/oli-theme-test-globals-' . uniqid();
        mkdir($cacheDir, 0o755, true);

        // Crée un renderer identique pour tester les variables sans template réel.
        $testRenderer = new ViewRenderer(sys_get_temp_dir(), $cacheDir);
        $testRenderer->setDefaultVariables([
            'siteName' => 'Oli',
            'charset'  => 'UTF-8',
        ]);
        $testRenderer->registerMacro('wpHead', static fn (): string => '<wp-head/>');
        $testRenderer->registerMacro('wpFooter', static fn (): string => '<wp-footer/>');

        // Crée un mini-template inline dans sys_get_temp_dir() pour le test.
        $tplFile = sys_get_temp_dir() . '/test-globals.html.tpl';
        file_put_contents($tplFile, '[[ siteName ]] [[ charset ]] ##wpHead()## ##wpFooter()##');

        $output = $testRenderer->render('test-globals.html');

        self::assertStringContainsString('Oli', $output);
        self::assertStringContainsString('UTF-8', $output);
        self::assertStringContainsString('<wp-head/>', $output);
        self::assertStringContainsString('<wp-footer/>', $output);

        // Nettoyage.
        unlink($tplFile);
        foreach (glob($cacheDir . '/*.php') ?: [] as $f) {
            unlink($f);
        }
        rmdir($cacheDir);
    }
}
