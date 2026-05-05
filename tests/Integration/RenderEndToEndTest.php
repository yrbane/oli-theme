<?php

declare(strict_types=1);

namespace OliTheme\Tests\Integration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\ViewRenderer;
use OliTheme\Posts\PageController;
use OliTheme\Theme;
use PHPUnit\Framework\TestCase;
use stdClass;

final class RenderEndToEndTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Theme::reset();
    }

    protected function tearDown(): void
    {
        Theme::reset();
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFullyBootedThemeRendersSingularPage(): void
    {
        $themePath = \dirname(__DIR__, 2);

        // Fonctions WP appelées lors du boot (registerCoreHooks + modules)
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);

        Functions\when('wp_head')->justReturn('');
        Functions\when('wp_footer')->justReturn('');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('get_bloginfo')->justReturn('Oli');
        Functions\when('get_template_directory_uri')->justReturn('https://example.com/wp-content/themes/oli-theme');
        Functions\when('get_option')->justReturn(['enabled' => ['fr'], 'default' => 'fr']);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_permalink')->justReturn('https://example.com/fr/about');
        Functions\when('get_the_post_thumbnail_url')->justReturn(false);
        Functions\when('get_post_thumbnail_id')->justReturn(0);
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_the_author_meta')->justReturn('');
        Functions\when('mysql2date')->returnArg(2);
        Functions\when('wp_get_object_terms')->justReturn([(object) ['slug' => 'fr']]);

        $post = new stdClass();
        $post->ID = 99;
        $post->post_type = 'page';
        $post->post_title = 'À propos';
        $post->post_content = '<p>Bio</p>';
        $post->post_excerpt = '';
        $post->post_name = 'about';
        $post->post_date_gmt = '2026-05-05 10:00:00';
        $post->post_modified_gmt = '';
        $post->post_author = 1;
        $post->post_status = 'publish';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_queried_object_id')->justReturn(99);

        Theme::boot($themePath);

        // Injecte les variables globales attendues par le layout de base.
        Theme::container()->get(ViewRenderer::class)->setDefaultVariables([
            'wpHead'      => '',
            'wpFooter'    => '',
            'siteName'    => 'Oli',
            'siteUrl'     => 'https://example.com',
            'homeUrl'     => 'https://example.com',
            'themeUri'    => 'https://example.com/wp-content/themes/oli-theme',
            'currentYear' => '2026',
            'charset'     => 'UTF-8',
        ]);

        $controller = Theme::container()->get(PageController::class);

        self::assertInstanceOf(PageController::class, $controller);
        $html = $controller->renderSingular();

        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('À propos', $html);
        self::assertStringContainsString('<p>Bio</p>', $html);
        self::assertStringContainsString('lang="fr"', $html);
    }
}
