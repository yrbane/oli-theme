<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Navigation;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\Navigation\MenuController;
use OliTheme\Navigation\MenuItemEntity;
use OliTheme\Navigation\MenuLocations;
use OliTheme\Navigation\MenuModelInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

final class MenuControllerTest extends TestCase
{
    private Language $french;

    /** @var MockObject&MenuModelInterface */
    private MockObject $model;

    private MenuLocations $locations;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $this->model = $this->createMock(MenuModelInterface::class);
        $this->locations = new MenuLocations($this->createMock(LanguageRegistryInterface::class));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testBuildPrimaryReturnsEmptyWhenNoMenu(): void
    {
        Functions\when('has_nav_menu')->justReturn(false);

        $controller = new MenuController($this->locations, $this->model);

        self::assertSame([], $controller->buildPrimary($this->french));
    }

    public function testBuildPrimaryReturnsTreeFromModel(): void
    {
        $items = [(new stdClass())];
        $entity = new MenuItemEntity(1, 'A', '/', '', false, false, 0, []);

        Functions\when('has_nav_menu')->justReturn(true);
        Functions\when('wp_get_nav_menu_items')->justReturn($items);
        Functions\when('get_queried_object_id')->justReturn(42);

        $this->model
            ->expects(self::once())
            ->method('toTree')
            ->with($items, 42)
            ->willReturn([$entity]);

        $controller = new MenuController($this->locations, $this->model);

        self::assertSame([$entity], $controller->buildPrimary($this->french));
    }

    public function testBuildFooterUsesFooterLocation(): void
    {
        Functions\when('has_nav_menu')->justReturn(true);
        Functions\when('wp_get_nav_menu_items')->justReturn([]);
        Functions\when('get_queried_object_id')->justReturn(0);

        $this->model->method('toTree')->willReturn([]);

        $controller = new MenuController($this->locations, $this->model);

        self::assertSame([], $controller->buildFooter($this->french));
    }
}
