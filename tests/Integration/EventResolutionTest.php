<?php

declare(strict_types=1);

namespace OliTheme\Tests\Integration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Events\EventArchiveControllerInterface;
use OliTheme\Events\EventControllerInterface;
use OliTheme\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie qu'après le boot complet du thème, les controllers des événements
 * sont résolvables via le container et instanciables.
 */
final class EventResolutionTest extends TestCase
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

    public function testEventControllersAreResolvableAfterBoot(): void
    {
        $themePath = \dirname(__DIR__, 2);

        Functions\when('wp_head')->justReturn('');
        Functions\when('wp_footer')->justReturn('');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('get_bloginfo')->justReturn('Oli');
        Functions\when('get_template_directory_uri')->justReturn('https://example.com/theme');
        Functions\when('get_option')->justReturn(['enabled' => ['fr'], 'default' => 'fr']);
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);

        Theme::boot($themePath);

        $single = Theme::container()->get(EventControllerInterface::class);
        $archive = Theme::container()->get(EventArchiveControllerInterface::class);

        self::assertInstanceOf(EventControllerInterface::class, $single);
        self::assertInstanceOf(EventArchiveControllerInterface::class, $archive);
    }
}
