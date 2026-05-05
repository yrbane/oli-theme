<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Slides;

use Brain\Monkey;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\Slides\HomeCarouselController;
use OliTheme\Slides\HomeCarouselViewModel;
use OliTheme\Slides\SlideEntity;
use OliTheme\Slides\SlideModelInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de HomeCarouselController.
 *
 * @package OliTheme\Tests\Unit\Slides
 *
 * @since 1.0.0
 */
final class HomeCarouselControllerTest extends TestCase
{
    private Language $french;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testBuildReturnsViewModelWithSlides(): void
    {
        $slide1 = new SlideEntity(1, 'Slide 1', null, '', null, null, null, 0, null, $this->french);
        $slide2 = new SlideEntity(2, 'Slide 2', null, '', null, null, null, 1, null, $this->french);

        $model = $this->createMock(SlideModelInterface::class);
        $model->method('findActive')->willReturn([$slide1, $slide2]);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($this->french);

        $controller = new HomeCarouselController($model, $resolver);
        $vm = $controller->build();

        self::assertInstanceOf(HomeCarouselViewModel::class, $vm);
        self::assertCount(2, $vm->slides);
    }

    public function testBuildReturnsEmptyViewModelWhenNoSlides(): void
    {
        $model = $this->createMock(SlideModelInterface::class);
        $model->method('findActive')->willReturn([]);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($this->french);

        $controller = new HomeCarouselController($model, $resolver);
        $vm = $controller->build();

        self::assertSame([], $vm->slides);
        self::assertTrue($vm->autoplay);
    }
}
