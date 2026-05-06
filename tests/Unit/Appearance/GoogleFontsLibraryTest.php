<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Appearance;

use OliTheme\Appearance\GoogleFontsLibrary;
use PHPUnit\Framework\TestCase;

/**
 * @package OliTheme\Tests\Unit\Appearance
 *
 * @since 1.0.0
 */
final class GoogleFontsLibraryTest extends TestCase
{
    public function testReturnsCuratedListWithExpectedShape(): void
    {
        $fonts = (new GoogleFontsLibrary())->all();

        self::assertNotEmpty($fonts);
        self::assertGreaterThan(20, \count($fonts));
        self::assertArrayHasKey('family', $fonts[0]);
        self::assertArrayHasKey('category', $fonts[0]);
    }

    public function testHasReturnsTrueForKnownFamily(): void
    {
        $library = new GoogleFontsLibrary();

        self::assertTrue($library->has('Inter'));
        self::assertTrue($library->has('Playfair Display'));
        self::assertFalse($library->has('NonExistantFontXYZ'));
    }

    public function testCssUrlEncodesSpacesAsPlus(): void
    {
        $url = (new GoogleFontsLibrary())->cssUrlFor('Playfair Display');

        self::assertStringContainsString('family=Playfair+Display', $url);
        self::assertStringContainsString('display=swap', $url);
        self::assertStringContainsString('wght@400;500;700', $url);
    }

    public function testCssUrlForSimpleFamily(): void
    {
        $url = (new GoogleFontsLibrary())->cssUrlFor('Inter');

        self::assertStringContainsString('family=Inter', $url);
    }
}
