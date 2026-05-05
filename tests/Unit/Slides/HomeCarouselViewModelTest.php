<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Slides;

use OliTheme\Slides\HomeCarouselViewModel;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de HomeCarouselViewModel.
 *
 * @package OliTheme\Tests\Unit\Slides
 *
 * @since 1.0.0
 */
final class HomeCarouselViewModelTest extends TestCase
{
    public function testItExposesPropertiesAndDefaults(): void
    {
        $vm = new HomeCarouselViewModel(slides: []);

        self::assertSame([], $vm->slides);
        self::assertTrue($vm->autoplay);
        self::assertSame(5000, $vm->intervalMs);
        self::assertTrue($vm->loop);
    }
}
