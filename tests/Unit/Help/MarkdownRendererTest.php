<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Help;

use OliTheme\Help\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

final class MarkdownRendererTest extends TestCase
{
    private MarkdownRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new MarkdownRenderer();
    }

    public function test_renders_h1_h2_h3(): void
    {
        self::assertSame('<h1>Titre 1</h1>', trim($this->renderer->render('# Titre 1')));
        self::assertSame('<h2>Titre 2</h2>', trim($this->renderer->render('## Titre 2')));
        self::assertSame('<h3>Titre 3</h3>', trim($this->renderer->render('### Titre 3')));
    }

    public function test_renders_paragraphs(): void
    {
        $html = $this->renderer->render("Premier paragraphe.\n\nDeuxième paragraphe.");
        self::assertStringContainsString('<p>Premier paragraphe.</p>', $html);
        self::assertStringContainsString('<p>Deuxième paragraphe.</p>', $html);
    }

    public function test_renders_unordered_list(): void
    {
        $html = $this->renderer->render("- item un\n- item deux\n- item trois");
        self::assertStringContainsString('<ul>', $html);
        self::assertStringContainsString('<li>item un</li>', $html);
        self::assertStringContainsString('<li>item deux</li>', $html);
        self::assertStringContainsString('<li>item trois</li>', $html);
        self::assertStringContainsString('</ul>', $html);
    }

    public function test_renders_ordered_list(): void
    {
        $html = $this->renderer->render("1. premier\n2. second");
        self::assertStringContainsString('<ol>', $html);
        self::assertStringContainsString('<li>premier</li>', $html);
        self::assertStringContainsString('<li>second</li>', $html);
        self::assertStringContainsString('</ol>', $html);
    }

    public function test_renders_bold_and_italic(): void
    {
        $html = $this->renderer->render('Texte **gras** et _italique_.');
        self::assertStringContainsString('<strong>gras</strong>', $html);
        self::assertStringContainsString('<em>italique</em>', $html);
    }

    public function test_renders_inline_code(): void
    {
        $html = $this->renderer->render('Voir `wp_enqueue_script` ici.');
        self::assertStringContainsString('<code>wp_enqueue_script</code>', $html);
    }

    public function test_renders_fenced_code_block(): void
    {
        $md = "Texte\n\n```\nphp -v\n```\n\nSuite";
        $html = $this->renderer->render($md);
        self::assertStringContainsString('<pre><code>php -v</code></pre>', $html);
    }

    public function test_renders_links(): void
    {
        $html = $this->renderer->render('Voir [WordPress](https://wordpress.org).');
        self::assertStringContainsString('<a href="https://wordpress.org">WordPress</a>', $html);
    }

    public function test_escapes_html_special_chars(): void
    {
        $html = $this->renderer->render('Texte <script>alert("x")</script> dangereux.');
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_escapes_inside_code_block(): void
    {
        $html = $this->renderer->render("```\n<script>x</script>\n```");
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_empty_input_yields_empty_output(): void
    {
        self::assertSame('', trim($this->renderer->render('')));
    }

    public function test_link_url_is_escaped(): void
    {
        $html = $this->renderer->render('[bad]("onerror=alert(1) x)');
        // L'URL est échappée : pas d'attribut HTML brisé.
        self::assertStringNotContainsString('onerror=alert', $html);
    }
}
