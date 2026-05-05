<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Core\ViewRenderer;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageSwitcherViewModel;
use OliTheme\Posts\PostEntity;
use PHPUnit\Framework\TestCase;

final class PageTemplateRenderTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('wp_head')->justReturn('');
        Functions\when('wp_footer')->justReturn('');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('get_bloginfo')->justReturn('Oli');
        Functions\when('get_template_directory_uri')->justReturn('https://example.com');

        $this->cacheDir = sys_get_temp_dir() . '/oli-theme-cache-page-' . uniqid();
        mkdir($this->cacheDir, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->cacheDir);
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRendersPageWithEntity(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $entity = new PostEntity(
            id: 7,
            type: 'page',
            title: 'À propos',
            content: '<p>Bio</p>',
            excerpt: null,
            slug: 'a-propos',
            language: $french,
            featuredImageUrl: 'https://example.com/img.jpg',
            featuredImageAlt: 'Photo',
            permalink: 'https://example.com/fr/a-propos',
            publishedAt: new DateTimeImmutable('2026-01-01'),
            updatedAt: null,
            author: null,
        );

        $renderer = new ViewRenderer(__DIR__ . '/../../../templates', $this->cacheDir);
        $renderer->setDefaultVariables([
            'wpHead' => '', 'wpFooter' => '',
            'siteName' => 'Oli', 'siteUrl' => 'https://example.com', 'homeUrl' => 'https://example.com',
            'themeUri' => 'https://example.com', 'currentYear' => '2026', 'charset' => 'UTF-8',
        ]);

        $html = $renderer->render('pages/page.html', [
            'post' => $entity,
            'lang' => $french,
            'languageSwitcher' => new LanguageSwitcherViewModel($french, []),
            'bodyClasses' => 'page',
        ]);

        self::assertStringContainsString('À propos', $html);
        self::assertStringContainsString('<p>Bio</p>', $html);
        self::assertStringContainsString('https://example.com/img.jpg', $html);
        self::assertStringContainsString('lang="fr"', $html);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
