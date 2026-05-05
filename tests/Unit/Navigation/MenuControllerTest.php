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
        Functions\when('get_nav_menu_locations')->justReturn(['primary_fr' => 7]);
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
        Functions\when('get_nav_menu_locations')->justReturn(['footer_fr' => 8]);
        Functions\when('wp_get_nav_menu_items')->justReturn([]);
        Functions\when('get_queried_object_id')->justReturn(0);

        $this->model->method('toTree')->willReturn([]);

        $controller = new MenuController($this->locations, $this->model);

        self::assertSame([], $controller->buildFooter($this->french));
    }

    /**
     * Régression #5 : `wp_get_nav_menu_items()` attend un identifiant de menu,
     * pas une `theme_location`. Si le controller passe directement la location,
     * la fonction WP retourne `false` et le menu reste vide même quand un menu
     * est correctement assigné.
     */
    public function testBuildPrimaryResolvesLocationToMenuIdBeforeFetching(): void
    {
        $items   = [new stdClass()];
        $entity  = new MenuItemEntity(1, 'A', '/', '', false, false, 0, []);
        $menuId  = 42;
        $passedArgument = null;

        Functions\when('has_nav_menu')->justReturn(true);
        Functions\when('get_nav_menu_locations')->justReturn(['primary_fr' => $menuId]);
        Functions\when('wp_get_nav_menu_items')->alias(
            static function (mixed $argument) use (&$passedArgument, $items): array {
                $passedArgument = $argument;

                return $items;
            },
        );
        Functions\when('get_queried_object_id')->justReturn(0);

        $this->model->method('toTree')->willReturn([$entity]);

        $controller = new MenuController($this->locations, $this->model);
        $result     = $controller->buildPrimary($this->french);

        self::assertSame([$entity], $result);
        self::assertSame($menuId, $passedArgument, 'Le controller doit transmettre un menu ID, pas la location.');
    }

    public function testBuildPrimaryReturnsEmptyWhenLocationNotMappedToMenu(): void
    {
        Functions\when('has_nav_menu')->justReturn(true);
        Functions\when('get_nav_menu_locations')->justReturn([]);
        Functions\when('wp_get_nav_menu_items')->justReturn(false);
        Functions\when('get_queried_object_id')->justReturn(0);

        $controller = new MenuController($this->locations, $this->model);

        self::assertSame([], $controller->buildPrimary($this->french));
    }
}
