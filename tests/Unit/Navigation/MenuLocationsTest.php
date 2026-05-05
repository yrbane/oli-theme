<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Navigation;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\Navigation\MenuLocations;
use PHPUnit\Framework\TestCase;

final class MenuLocationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRegistersPrimaryAndFooterPerEnabledLanguage(): void
    {
        $registry = $this->createMock(LanguageRegistryInterface::class);
        $registry->method('all')->willReturn([
            new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr'),
            new Language('en', 'English', 'English', '🇬🇧', 'en_US', 'ltr'),
        ]);

        $captured = [];
        Functions\when('register_nav_menus')->alias(static function (array $locations) use (&$captured): void {
            $captured = $locations;
        });
        Functions\when('__')->returnArg(1);

        (new MenuLocations($registry))->register();

        self::assertArrayHasKey('primary_fr', $captured);
        self::assertArrayHasKey('footer_fr', $captured);
        self::assertArrayHasKey('primary_en', $captured);
        self::assertArrayHasKey('footer_en', $captured);
    }

    public function testPrimaryLocationKeyForReturnsExpectedSlug(): void
    {
        $registry = $this->createMock(LanguageRegistryInterface::class);
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $locations = new MenuLocations($registry);

        self::assertSame('primary_fr', $locations->primaryFor($french));
        self::assertSame('footer_fr', $locations->footerFor($french));
    }
}
