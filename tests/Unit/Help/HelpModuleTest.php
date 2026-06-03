<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Help;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use OliTheme\Admin\AdminTabRegistry;
use OliTheme\Container;
use OliTheme\Help\HelpAdminPage;
use OliTheme\Help\HelpModule;
use OliTheme\Help\HelpRegistry;
use OliTheme\Help\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

final class HelpModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg(1);
        Functions\when('get_template_directory')->justReturn(\dirname(__DIR__, 3));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_register_factories_in_container(): void
    {
        $container = new Container();
        // Préenregistrer le registre d'onglets (dépendance du closure admin_menu).
        $container->factory(AdminTabRegistry::class, static fn (): AdminTabRegistry => new AdminTabRegistry());

        (new HelpModule($container))->register();

        self::assertTrue($container->has(HelpRegistry::class));
        self::assertTrue($container->has(MarkdownRenderer::class));
        self::assertTrue($container->has(HelpAdminPage::class));

        $page = $container->get(HelpAdminPage::class);
        self::assertInstanceOf(HelpAdminPage::class, $page);
    }

    public function test_register_attaches_admin_menu_hook(): void
    {
        $container = new Container();
        $container->factory(AdminTabRegistry::class, static fn (): AdminTabRegistry => new AdminTabRegistry());

        Actions\expectAdded('admin_menu')->once();

        (new HelpModule($container))->register();

        // L'expectation Brain Monkey ci-dessus est validée au tearDown ;
        // cette assertion sert uniquement à éviter le flag PHPUnit « risky ».
        self::assertTrue(true);
    }
}
