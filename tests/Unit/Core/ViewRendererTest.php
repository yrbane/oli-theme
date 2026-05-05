<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use OliTheme\Core\ViewRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Tests du wrapper Lunar Template (ViewRenderer).
 *
 * On crée un répertoire de templates temporaire pour chaque test, on y écrit
 * un .tpl, on appelle render() et on vérifie la sortie HTML.
 */
final class ViewRendererTest extends TestCase
{
    private string $templateDir;
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateDir = sys_get_temp_dir() . '/oli-theme-templates-' . uniqid();
        $this->cacheDir = sys_get_temp_dir() . '/oli-theme-cache-' . uniqid();
        mkdir($this->templateDir, recursive: true);
        mkdir($this->cacheDir, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->templateDir);
        $this->rrmdir($this->cacheDir);
        parent::tearDown();
    }

    public function test_it_should_render_a_template_with_variables(): void
    {
        $this->writeTemplate('hello.html.tpl', '<p>Bonjour [[ name ]]</p>');

        $renderer = new ViewRenderer($this->templateDir, $this->cacheDir);
        $output = $renderer->render('hello.html', ['name' => 'Olivier']);

        self::assertSame('<p>Bonjour Olivier</p>', trim($output));
    }

    public function test_it_should_escape_variables_by_default(): void
    {
        $this->writeTemplate('escape.html.tpl', '<p>[[ raw ]]</p>');

        $renderer = new ViewRenderer($this->templateDir, $this->cacheDir);
        $output = $renderer->render('escape.html', ['raw' => '<script>alert(1)</script>']);

        self::assertStringContainsString('&lt;script&gt;', $output);
        self::assertStringNotContainsString('<script>', $output);
    }

    public function test_it_should_inject_default_variables_into_every_render(): void
    {
        $this->writeTemplate('site.html.tpl', '<title>[[ siteName ]]</title>');

        $renderer = new ViewRenderer($this->templateDir, $this->cacheDir);
        $renderer->setDefaultVariables(['siteName' => 'Olikalari']);
        $output = $renderer->render('site.html', []);

        self::assertStringContainsString('Olikalari', $output);
    }

    private function writeTemplate(string $name, string $content): void
    {
        file_put_contents($this->templateDir . '/' . $name, $content);
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
