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
        // Stub par défaut : aucune page d'accueil configurée → désactive la
        // détection page_on_front, qui ne concerne que des tests dédiés.
        Functions\when('get_option')->justReturn(false);
        Functions\when('home_url')->alias(static fn (string $p = '') => 'https://example.com' . $p);
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

    /**
     * Sur /en/, get_permalink(76) retourne /en/accueil/ (filtré par LanguageUrlFilter).
     * Pour la langue FR (cible), l'URL hreflang doit être /accueil/ — pas /en/accueil/.
     */
    public function testBuildStripsActiveLanguagePrefixFromTargetUrl(): void
    {
        $fr = $this->makeLang('fr', 'fr_FR');
        $en = $this->makeLang('en', 'en_US');

        $registry = $this->createMock(LanguageRegistryInterface::class);
        $registry->method('all')->willReturn([$fr, $en]);
        $registry->method('default')->willReturn($fr);

        $translation = $this->createMock(TranslationModelInterface::class);
        $translation->method('getTranslations')->willReturn(['fr' => 76, 'en' => 77]);

        // Simule LanguageUrlFilter sur /en/ : get_permalink est préfixé /en/ pour tous.
        Functions\when('get_permalink')->alias(static fn (int $id) => $id === 76 ? 'https://example.com/en/accueil' : 'https://example.com/en/home');
        Functions\when('get_option')->justReturn(false);

        $builder = new HreflangBuilder($registry, $translation);
        $result = $builder->build(77);

        $fr = array_values(array_filter($result, static fn ($r) => $r['code'] === 'fr'));
        $en = array_values(array_filter($result, static fn ($r) => $r['code'] === 'en'));
        self::assertSame('https://example.com/accueil', $fr[0]['url']);
        self::assertSame('https://example.com/en/home', $en[0]['url']);
    }

    /**
     * Quand une traduction est la page d'accueil de sa langue, l'URL hreflang doit
     * pointer vers la home racine (pas vers /accueil/ ou /en/home/).
     */
    public function testBuildUsesHomeRootWhenTranslationIsFrontPage(): void
    {
        $fr = $this->makeLang('fr', 'fr_FR');
        $en = $this->makeLang('en', 'en_US');

        $registry = $this->createMock(LanguageRegistryInterface::class);
        $registry->method('all')->willReturn([$fr, $en]);
        $registry->method('default')->willReturn($fr);

        $translation = $this->createMock(TranslationModelInterface::class);
        $translation->method('getTranslations')->willReturn(['fr' => 76, 'en' => 77]);

        Functions\when('get_permalink')->alias(static fn (int $id) => $id === 76 ? 'https://example.com/accueil' : 'https://example.com/en/home');
        Functions\when('get_option')->alias(static fn (string $k, $d = false) => match ($k) {
            'show_on_front' => 'page',
            'page_on_front' => 76,
            default => $d,
        });
        Functions\when('home_url')->alias(static fn (string $p = '') => 'https://example.com' . $p);

        $builder = new HreflangBuilder($registry, $translation);
        $result = $builder->build(76);

        $frEntry = array_values(array_filter($result, static fn ($r) => $r['code'] === 'fr'));
        $enEntry = array_values(array_filter($result, static fn ($r) => $r['code'] === 'en'));
        // FR (défaut, page_on_front=76) → racine.
        self::assertSame('https://example.com/', $frEntry[0]['url']);
        // EN trad. de 76 (front_page traduite) → racine EN /en/.
        self::assertSame('https://example.com/en/', $enEntry[0]['url']);
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
