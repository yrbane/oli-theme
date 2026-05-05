<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use OliTheme\Container;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;
use OliTheme\Navigation\MenuControllerInterface;
use OliTheme\Posts\NotFoundController;
use OliTheme\Posts\PageController;
use OliTheme\Posts\PostController;
use OliTheme\Posts\PostModel;
use OliTheme\Posts\PostsModule;
use PHPUnit\Framework\TestCase;

final class PostsModuleTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->container = new Container();

        $this->container->set(RendererInterface::class, $this->createMock(RendererInterface::class));
        $this->container->set(LanguageResolverInterface::class, $this->createMock(LanguageResolverInterface::class));
        $this->container->set(LanguageRegistryInterface::class, $this->createMock(LanguageRegistryInterface::class));
        $this->container->set(LanguageSwitcherControllerInterface::class, $this->createMock(LanguageSwitcherControllerInterface::class));
        $this->container->set(MenuControllerInterface::class, $this->createMock(MenuControllerInterface::class));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRegistersAllPostsServices(): void
    {
        $module = new PostsModule($this->container);
        $module->register();

        self::assertInstanceOf(PostModel::class, $this->container->get(PostModel::class));
        self::assertInstanceOf(PageController::class, $this->container->get(PageController::class));
        self::assertInstanceOf(PostController::class, $this->container->get(PostController::class));
        self::assertInstanceOf(NotFoundController::class, $this->container->get(NotFoundController::class));
    }

    public function testRegisterIsIdempotent(): void
    {
        $module = new PostsModule($this->container);
        $module->register();
        $module->register();

        self::assertInstanceOf(PostModel::class, $this->container->get(PostModel::class));
    }
}
