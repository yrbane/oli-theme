<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use OliTheme\Container;
use OliTheme\Core\RendererInterface;
use OliTheme\Settings\SettingsModule;
use OliTheme\Settings\ThemeSettingsModel;
use OliTheme\Settings\ThemeSettingsModelInterface;
use OliTheme\Settings\ThemeSettingsPage;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de SettingsModule.
 *
 * @package OliTheme\Tests\Unit\Settings
 *
 * @since 1.0.0
 */
final class SettingsModuleTest extends TestCase
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

    public function testRegisterBindsAllSettingsServices(): void
    {
        Functions\when('add_action')->justReturn(true);

        $container = new Container();
        $container->set(RendererInterface::class, $this->createMock(RendererInterface::class));

        (new SettingsModule($container))->register();

        self::assertTrue($container->has(ThemeSettingsModel::class));
        self::assertTrue($container->has(ThemeSettingsModelInterface::class));
        self::assertTrue($container->has(ThemeSettingsPage::class));
    }

    public function testRegisterHooksAdminMenuAndAdminInit(): void
    {
        Actions\expectAdded('admin_menu')->once();
        Actions\expectAdded('admin_init')->once();

        $container = new Container();
        $container->set(RendererInterface::class, $this->createMock(RendererInterface::class));

        (new SettingsModule($container))->register();

        $this->addToAssertionCount(1);
    }
}
