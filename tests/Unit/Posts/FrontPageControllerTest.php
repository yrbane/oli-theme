<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\TranslationModelInterface;
use OliTheme\Posts\ArchiveRendererInterface;
use OliTheme\Posts\FrontPageController;
use OliTheme\Posts\PageRendererInterface;
use PHPUnit\Framework\TestCase;

final class FrontPageControllerTest extends TestCase
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

    public function test_renders_archive_when_no_page_on_front(): void
    {
        Functions\when('get_option')->justReturn(0);

        $post = $this->createMock(ArchiveRendererInterface::class);
        $post->expects(self::once())->method('renderArchive')->willReturn('ARCHIVE_HTML');

        $page = $this->createMock(PageRendererInterface::class);
        $page->expects(self::never())->method('renderById');

        $translations = $this->createMock(TranslationModelInterface::class);
        $resolver = $this->createMock(LanguageResolverInterface::class);

        $controller = new FrontPageController($page, $post, $translations, $resolver, 'fr');
        self::assertSame('ARCHIVE_HTML', $controller->render());
    }

    public function test_renders_default_front_page_when_current_lang_is_default(): void
    {
        Functions\when('get_option')->justReturn(56);

        $page = $this->createMock(PageRendererInterface::class);
        $page->expects(self::once())->method('renderById')->with(56)->willReturn('FR_HOME_HTML');

        $post = $this->createMock(ArchiveRendererInterface::class);
        $translations = $this->createMock(TranslationModelInterface::class);
        $translations->expects(self::never())->method('getTranslations');

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn(new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR'));

        $controller = new FrontPageController($page, $post, $translations, $resolver, 'fr');
        self::assertSame('FR_HOME_HTML', $controller->render());
    }

    public function test_renders_translated_front_page_for_non_default_language(): void
    {
        Functions\when('get_option')->justReturn(56);

        $page = $this->createMock(PageRendererInterface::class);
        // Doit rendre la traduction EN (57) et NON la page FR (56).
        $page->expects(self::once())->method('renderById')->with(57)->willReturn('EN_HOME_HTML');

        $post = $this->createMock(ArchiveRendererInterface::class);

        $translations = $this->createMock(TranslationModelInterface::class);
        $translations->expects(self::once())->method('getTranslations')->with(56)->willReturn(['fr' => 56, 'en' => 57]);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn(new Language('en', 'English', 'English', '🇬🇧', 'en_US'));

        $controller = new FrontPageController($page, $post, $translations, $resolver, 'fr');
        self::assertSame('EN_HOME_HTML', $controller->render());
    }

    public function test_falls_back_to_default_front_page_when_translation_missing(): void
    {
        Functions\when('get_option')->justReturn(56);

        $page = $this->createMock(PageRendererInterface::class);
        // Pas de traduction EN → on rend la page par défaut (56).
        $page->expects(self::once())->method('renderById')->with(56)->willReturn('FR_HOME_FALLBACK');

        $post = $this->createMock(ArchiveRendererInterface::class);

        $translations = $this->createMock(TranslationModelInterface::class);
        $translations->expects(self::once())->method('getTranslations')->with(56)->willReturn(['fr' => 56]);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn(new Language('en', 'English', 'English', '🇬🇧', 'en_US'));

        $controller = new FrontPageController($page, $post, $translations, $resolver, 'fr');
        self::assertSame('FR_HOME_FALLBACK', $controller->render());
    }
}
