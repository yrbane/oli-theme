<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\ViewRenderer;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageSwitcherViewModel;
use PHPUnit\Framework\TestCase;

final class ViewRendererBaseLayoutTest extends TestCase
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
        Functions\when('get_template_directory_uri')->justReturn('https://example.com/wp-content/themes/oli-theme');

        $this->cacheDir = sys_get_temp_dir() . '/oli-theme-cache-base-' . uniqid();
        mkdir($this->cacheDir, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->cacheDir);
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testBaseLayoutRendersHtmlSkeleton(): void
    {
        $renderer = new ViewRenderer(__DIR__ . '/../../../templates', $this->cacheDir);
        $renderer->setDefaultVariables([
            'wpHead' => '',
            'wpFooter' => '',
            'siteName' => 'Oli',
            'siteUrl' => 'https://example.com',
            'homeUrl' => 'https://example.com',
            'themeUri' => 'https://example.com/wp-content/themes/oli-theme',
            'currentYear' => '2026',
            'charset' => 'UTF-8',
        ]);

        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        // Lunar 2de89f0+ supporte l'accès hybride array/objet via
        // Lunar\Template\Runtime\Access::get (issue #14). On passe directement
        // l'objet Language : [[ lang.code ]] résout à $lang->code.
        $html = $renderer->render('layouts/base.html', [
            'lang' => $french,
            'bodyClasses' => 'home',
            'languageSwitcher' => new LanguageSwitcherViewModel($french, []),
        ]);

        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<html lang="fr" dir="ltr">', $html);
        self::assertStringContainsString('<body class="home">', $html);
        self::assertStringContainsString('<main id="main"', $html);
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
