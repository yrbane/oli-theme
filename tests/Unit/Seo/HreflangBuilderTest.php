<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\TranslationModelInterface;
use OliTheme\Seo\HreflangBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests du HreflangBuilder.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class HreflangBuilderTest extends TestCase
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

    public function testBuildIncludesAllAvailableTranslations(): void
    {
        $fr = $this->makeLang('fr', 'fr_FR');
        $en = $this->makeLang('en', 'en_US');

        $registry = $this->createMock(LanguageRegistryInterface::class);
        $registry->method('all')->willReturn([$fr, $en]);
        $registry->method('default')->willReturn($fr);

        $translation = $this->createMock(TranslationModelInterface::class);
        $translation->method('getTranslations')->with(10)->willReturn(['fr' => 10, 'en' => 20]);

        Functions\when('get_permalink')->alias(static fn (int $id) => $id === 10 ? 'https://example.com/fr/about' : 'https://example.com/en/about');

        $builder = new HreflangBuilder($registry, $translation);
        $result = $builder->build(10);

        self::assertCount(3, $result);
        self::assertSame(['code' => 'fr', 'url' => 'https://example.com/fr/about'], $result[0]);
        self::assertSame(['code' => 'en', 'url' => 'https://example.com/en/about'], $result[1]);
        self::assertSame(['code' => 'x-default', 'url' => 'https://example.com/fr/about'], $result[2]);
    }

    public function testBuildSkipsAbsentTranslations(): void
    {
        $fr = $this->makeLang('fr', 'fr_FR');
        $en = $this->makeLang('en', 'en_US');

        $registry = $this->createMock(LanguageRegistryInterface::class);
        $registry->method('all')->willReturn([$fr, $en]);
        $registry->method('default')->willReturn($fr);

        $translation = $this->createMock(TranslationModelInterface::class);
        $translation->method('getTranslations')->with(10)->willReturn(['fr' => 10]);

        Functions\when('get_permalink')->justReturn('https://example.com/fr/about');

        $builder = new HreflangBuilder($registry, $translation);
        $result = $builder->build(10);

        self::assertCount(2, $result);
        self::assertSame(['code' => 'fr', 'url' => 'https://example.com/fr/about'], $result[0]);
        self::assertSame(['code' => 'x-default', 'url' => 'https://example.com/fr/about'], $result[1]);
    }

    private function makeLang(string $code, string $locale): Language
    {
        return new Language(
            code: $code,
            label: strtoupper($code),
            nativeLabel: strtoupper($code),
            flag: $code,
            locale: $locale,
        );
    }
}
