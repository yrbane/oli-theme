<?php

declare(strict_types=1);

namespace OliTheme\Tests\Integration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Slides\HomeCarouselControllerInterface;
use OliTheme\Slides\HomeCarouselViewModel;
use OliTheme\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie qu'après le boot complet du thème, le HomeCarouselController est
 * résolu via le container et produit un view-model cohérent (slides vides
 * mais structure valide).
 */
final class CarouselFrontPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Theme::reset();
    }

    protected function tearDown(): void
    {
        Theme::reset();
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testHomeCarouselControllerIsResolvableAfterBoot(): void
    {
        $themePath = \dirname(__DIR__, 2);

        Functions\when('wp_head')->justReturn('');
        Functions\when('wp_footer')->justReturn('');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('get_bloginfo')->justReturn('Oli');
        Functions\when('get_template_directory_uri')->justReturn('https://example.com/theme');
        Functions\when('get_option')->justReturn(['enabled' => ['fr'], 'default' => 'fr']);
        Functions\when('get_posts')->justReturn([]);
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);

        Theme::boot($themePath);

        $controller = Theme::container()->get(HomeCarouselControllerInterface::class);
        self::assertInstanceOf(HomeCarouselControllerInterface::class, $controller);

        $viewModel = $controller->build();
        self::assertInstanceOf(HomeCarouselViewModel::class, $viewModel);
        self::assertSame([], $viewModel->slides);
        self::assertTrue($viewModel->autoplay);
        self::assertSame(5000, $viewModel->intervalMs);
    }
}
