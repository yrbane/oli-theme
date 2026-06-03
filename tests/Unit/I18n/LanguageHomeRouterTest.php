<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\LanguageHomeRouter;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\TranslationModelInterface;
use PHPUnit\Framework\TestCase;

final class LanguageHomeRouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('get_option')->justReturn(false);
        Functions\when('__')->returnArg(1);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_injects_translated_page_id_for_non_default_language_home(): void
    {
        Functions\when('get_option')->alias(static function (string $key) {
            return match ($key) {
                'show_on_front' => 'page',
                'page_on_front' => 56,
                default => false,
            };
        });

        $translations = $this->createMock(TranslationModelInterface::class);
        $translations->method('getTranslations')->with(56)->willReturn(['fr' => 56, 'en' => 57]);

        $router = new LanguageHomeRouter(new LanguageRegistry(), $translations);

        $wp = new \stdClass();
        $wp->query_vars = ['oli_lang' => 'en'];

        $router->route($wp);

        self::assertSame(57, $wp->query_vars['page_id']);
    }

    public function test_does_nothing_for_default_language(): void
    {
        Functions\when('get_option')->alias(static fn (string $k) => $k === 'show_on_front' ? 'page' : ($k === 'page_on_front' ? 56 : false));

        $translations = $this->createMock(TranslationModelInterface::class);
        $translations->expects(self::never())->method('getTranslations');

        $router = new LanguageHomeRouter(new LanguageRegistry(), $translations);

        $wp = new \stdClass();
        $wp->query_vars = ['oli_lang' => 'fr'];

        $router->route($wp);

        self::assertArrayNotHasKey('page_id', $wp->query_vars);
    }

    public function test_does_nothing_when_a_specific_page_is_already_targeted(): void
    {
        Functions\when('get_option')->alias(static fn (string $k) => $k === 'show_on_front' ? 'page' : ($k === 'page_on_front' ? 56 : false));

        $translations = $this->createMock(TranslationModelInterface::class);
        $translations->expects(self::never())->method('getTranslations');

        $router = new LanguageHomeRouter(new LanguageRegistry(), $translations);

        $wp = new \stdClass();
        $wp->query_vars = ['oli_lang' => 'en', 'pagename' => 'about'];

        $router->route($wp);

        self::assertArrayNotHasKey('page_id', $wp->query_vars);
    }

    public function test_does_nothing_when_show_on_front_is_posts(): void
    {
        Functions\when('get_option')->alias(static fn (string $k) => $k === 'show_on_front' ? 'posts' : false);

        $translations = $this->createMock(TranslationModelInterface::class);
        $router = new LanguageHomeRouter(new LanguageRegistry(), $translations);

        $wp = new \stdClass();
        $wp->query_vars = ['oli_lang' => 'en'];

        $router->route($wp);

        self::assertArrayNotHasKey('page_id', $wp->query_vars);
    }

    public function test_does_nothing_when_no_translation_exists(): void
    {
        Functions\when('get_option')->alias(static fn (string $k) => $k === 'show_on_front' ? 'page' : ($k === 'page_on_front' ? 56 : false));

        $translations = $this->createMock(TranslationModelInterface::class);
        $translations->method('getTranslations')->with(56)->willReturn(['fr' => 56]);

        $router = new LanguageHomeRouter(new LanguageRegistry(), $translations);

        $wp = new \stdClass();
        $wp->query_vars = ['oli_lang' => 'en'];

        $router->route($wp);

        self::assertArrayNotHasKey('page_id', $wp->query_vars);
    }
}
