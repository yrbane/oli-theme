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

final class SinglePostTemplateRenderTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('wp_head')->justReturn('');
        Functions\when('wp_footer')->justReturn('');

        $this->cacheDir = sys_get_temp_dir() . '/oli-theme-cache-single-' . uniqid();
        mkdir($this->cacheDir, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->cacheDir);
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRendersPostMetadata(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $entity = new PostEntity(
            id: 11,
            type: 'post',
            title: 'Hello',
            content: '<p>x</p>',
            excerpt: null,
            slug: 'hello',
            language: $french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/fr/hello',
            publishedAt: new DateTimeImmutable('2026-05-05'),
            updatedAt: null,
            author: 'Olivier',
        );

        $renderer = new ViewRenderer(__DIR__ . '/../../../templates', $this->cacheDir);
        $renderer->setDefaultVariables([
            'wpHead' => '', 'wpFooter' => '',
            'siteName' => 'Oli', 'siteUrl' => 'https://example.com', 'homeUrl' => 'https://example.com',
            'themeUri' => 'https://example.com', 'currentYear' => '2026', 'charset' => 'UTF-8',
        ]);

        $html = $renderer->render('pages/single-post.html', [
            'post' => $entity,
            'lang' => $french,
            'languageSwitcher' => new LanguageSwitcherViewModel($french, []),
            'bodyClasses' => 'single',
        ]);

        self::assertStringContainsString('05/05/2026', $html);
        self::assertStringContainsString('Olivier', $html);
        self::assertStringContainsString('Hello', $html);
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
