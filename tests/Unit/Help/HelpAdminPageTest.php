<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Help;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Help\HelpAdminPage;
use OliTheme\Help\HelpRegistry;
use OliTheme\Help\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

final class HelpAdminPageTest extends TestCase
{
    private HelpAdminPage $page;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('esc_url')->returnArg(1);
        Functions\when('sanitize_key')->alias(static fn (string $v): string => preg_replace('/[^a-z0-9_\-]/', '', strtolower($v)) ?? '');
        Functions\when('add_query_arg')->alias(static fn (array $a): string => '?guide=' . ($a['guide'] ?? ''));
        Functions\when('remove_query_arg')->justReturn('?page=oli-theme-settings&tab=aide');

        // Thème : racine du projet (les .md sont à docs/admin/).
        $themePath = \dirname(__DIR__, 3);

        $this->page = new HelpAdminPage(new HelpRegistry(), new MarkdownRenderer(), $themePath);
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_identity_metadata(): void
    {
        self::assertSame('aide', $this->page->id());
        self::assertSame('aide', $this->page->group());
        self::assertSame('Aide', $this->page->label());
        self::assertSame('manage_options', $this->page->capability());
    }

    public function test_render_panel_outputs_index_when_no_guide_param(): void
    {
        ob_start();
        $this->page->renderPanel();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('oli-help-index', $html);
        self::assertStringContainsString('Bienvenue Olivier', $html);
        // Au moins un lien vers un guide connu.
        self::assertStringContainsString('?guide=banniere', $html);
    }

    public function test_render_panel_outputs_guide_content_when_guide_param_known(): void
    {
        $_GET['guide'] = 'banniere';

        ob_start();
        $this->page->renderPanel();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('oli-help-guide', $html);
        self::assertStringContainsString('<h1>Bannière responsive</h1>', $html);
        // Le bouton retour est présent.
        self::assertStringContainsString('Retour à l\'index', $html);
    }

    public function test_render_panel_renders_not_found_when_guide_unknown(): void
    {
        $_GET['guide'] = 'inexistant-xyz';

        ob_start();
        $this->page->renderPanel();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Guide inconnu', $html);
        self::assertStringContainsString('inexistant-xyz', $html);
    }

    public function test_render_panel_handles_missing_file_gracefully(): void
    {
        // Thème pointant vers un dossier vide → fichier introuvable.
        $tmp = sys_get_temp_dir() . '/oli-help-empty-' . uniqid('', true);
        mkdir($tmp . '/docs/admin', 0o777, true);

        $page = new HelpAdminPage(new HelpRegistry(), new MarkdownRenderer(), $tmp);
        $_GET['guide'] = 'banniere';

        ob_start();
        $page->renderPanel();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('introuvable', $html);

        // Nettoyage.
        rmdir($tmp . '/docs/admin');
        rmdir($tmp . '/docs');
        rmdir($tmp);
    }
}
