<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Navigation;

use Brain\Monkey;
use Brain\Monkey\Actions;
use OliTheme\Container;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\Navigation\MenuController;
use OliTheme\Navigation\MenuControllerInterface;
use OliTheme\Navigation\MenuLocations;
use OliTheme\Navigation\MenuModel;
use OliTheme\Navigation\MenuModelInterface;
use OliTheme\Navigation\NavigationModule;
use PHPUnit\Framework\TestCase;

final class NavigationModuleTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->container = new Container();
        $this->container->set(LanguageRegistryInterface::class, $this->createMock(LanguageRegistryInterface::class));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRegisterBindsAllNavigationServices(): void
    {
        (new NavigationModule($this->container))->register();

        self::assertInstanceOf(MenuModel::class, $this->container->get(MenuModel::class));
        self::assertInstanceOf(MenuModel::class, $this->container->get(MenuModelInterface::class));
        self::assertInstanceOf(MenuLocations::class, $this->container->get(MenuLocations::class));
        self::assertInstanceOf(MenuController::class, $this->container->get(MenuController::class));
        self::assertInstanceOf(MenuController::class, $this->container->get(MenuControllerInterface::class));
    }

    public function testRegisterHooksAfterSetupTheme(): void
    {
        Actions\expectAdded('after_setup_theme')->once();

        (new NavigationModule($this->container))->register();

        $this->addToAssertionCount(1);
    }
}
