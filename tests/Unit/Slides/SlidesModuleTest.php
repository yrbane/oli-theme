<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Slides;

use Brain\Monkey;
use Brain\Monkey\Actions;
use OliTheme\Container;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\Slides\SlideCpt;
use OliTheme\Slides\SlideModel;
use OliTheme\Slides\SlideModelInterface;
use OliTheme\Slides\SlidesModule;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de SlidesModule.
 *
 * @package OliTheme\Tests\Unit\Slides
 *
 * @since 1.0.0
 */
final class SlidesModuleTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->container = new Container();
        $this->container->set(LanguageRegistryInterface::class, $this->createMock(LanguageRegistryInterface::class));
        $this->container->set(LanguageResolverInterface::class, $this->createMock(LanguageResolverInterface::class));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRegisterBindsAllSlidesServices(): void
    {
        $module = new SlidesModule($this->container);
        $module->register();

        self::assertTrue($this->container->has(SlideCpt::class));
        self::assertTrue($this->container->has(SlideModel::class));
        self::assertTrue($this->container->has(SlideModelInterface::class));

        self::assertInstanceOf(SlideCpt::class, $this->container->get(SlideCpt::class));
        self::assertInstanceOf(SlideModel::class, $this->container->get(SlideModel::class));
        self::assertInstanceOf(SlideModelInterface::class, $this->container->get(SlideModelInterface::class));
    }

    public function testRegisterHooksInit(): void
    {
        Actions\expectAdded('init')->once();

        $module = new SlidesModule($this->container);
        $module->register();

        $this->addToAssertionCount(1);
    }
}
