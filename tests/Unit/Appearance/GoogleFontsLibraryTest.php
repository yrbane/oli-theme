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
    public function testReturnsFullCatalog(): void
    {
        $fonts = (new GoogleFontsLibrary())->all();

        self::assertNotEmpty($fonts);
        // Le catalogue Google Fonts complet contient ~1900 familles.
        self::assertGreaterThan(1500, \count($fonts));
        self::assertArrayHasKey('family', $fonts[0]);
        self::assertArrayHasKey('category', $fonts[0]);
    }

    public function testHasReturnsTrueForKnownFamilies(): void
    {
        $library = new GoogleFontsLibrary();

        self::assertTrue($library->has('Inter'));
        self::assertTrue($library->has('Playfair Display'));
        // Régression : la liste curated précédente ne contenait pas
        // Bricolage Grotesque, qui doit maintenant être disponible.
        self::assertTrue($library->has('Bricolage Grotesque'));
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
