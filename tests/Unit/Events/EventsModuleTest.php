<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Events;

use Brain\Monkey;
use Brain\Monkey\Actions;
use OliTheme\Container;
use OliTheme\Core\RendererInterface;
use OliTheme\Events\EventArchiveController;
use OliTheme\Events\EventArchiveControllerInterface;
use OliTheme\Events\EventController;
use OliTheme\Events\EventControllerInterface;
use OliTheme\Events\EventCpt;
use OliTheme\Events\EventMetabox;
use OliTheme\Events\EventModel;
use OliTheme\Events\EventModelInterface;
use OliTheme\Events\EventsModule;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;
use OliTheme\Navigation\MenuControllerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de EventsModule.
 *
 * @package OliTheme\Tests\Unit\Events
 *
 * @since 1.0.0
 */
final class EventsModuleTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->container = new Container();
        $this->container->set(LanguageRegistryInterface::class, $this->createMock(LanguageRegistryInterface::class));
        $this->container->set(LanguageResolverInterface::class, $this->createMock(LanguageResolverInterface::class));
        $this->container->set(LanguageSwitcherControllerInterface::class, $this->createMock(LanguageSwitcherControllerInterface::class));
        $this->container->set(MenuControllerInterface::class, $this->createMock(MenuControllerInterface::class));
        $this->container->set(RendererInterface::class, $this->createMock(RendererInterface::class));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRegisterBindsAllEventsServices(): void
    {
        $module = new EventsModule($this->container);
        $module->register();

        self::assertTrue($this->container->has(EventCpt::class));
        self::assertTrue($this->container->has(EventModel::class));
        self::assertTrue($this->container->has(EventModelInterface::class));
        self::assertTrue($this->container->has(EventController::class));
        self::assertTrue($this->container->has(EventControllerInterface::class));
        self::assertTrue($this->container->has(EventArchiveController::class));
        self::assertTrue($this->container->has(EventArchiveControllerInterface::class));
        self::assertTrue($this->container->has(EventMetabox::class));

        self::assertInstanceOf(EventCpt::class, $this->container->get(EventCpt::class));
        self::assertInstanceOf(EventModel::class, $this->container->get(EventModel::class));
        self::assertInstanceOf(EventModelInterface::class, $this->container->get(EventModelInterface::class));
        self::assertInstanceOf(EventController::class, $this->container->get(EventController::class));
        self::assertInstanceOf(EventControllerInterface::class, $this->container->get(EventControllerInterface::class));
        self::assertInstanceOf(EventArchiveController::class, $this->container->get(EventArchiveController::class));
        self::assertInstanceOf(EventArchiveControllerInterface::class, $this->container->get(EventArchiveControllerInterface::class));
        self::assertInstanceOf(EventMetabox::class, $this->container->get(EventMetabox::class));
    }

    public function testRegisterHooksAllEvents(): void
    {
        Actions\expectAdded('init')->once();
        Actions\expectAdded('add_meta_boxes')->once();
        Actions\expectAdded('save_post_oli_event')->once();

        $module = new EventsModule($this->container);
        $module->register();

        $this->addToAssertionCount(1);
    }
}
