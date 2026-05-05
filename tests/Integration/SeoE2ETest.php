<?php

declare(strict_types=1);

namespace OliTheme\Tests\Integration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Seo\SeoControllerInterface;
use OliTheme\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie qu'après le boot complet du thème, le SeoController produit un
 * SeoHeadViewModel cohérent et que son JSON-LD contient les bons schémas.
 */
final class SeoE2ETest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Theme::reset();

        $GLOBALS['wpdb'] = new class () {
            public string $prefix = 'wp_';

            public function get_charset_collate(): string
            {
                return '';
            }

            public function get_row(): ?object
            {
                return null;
            }

            /** @return array<mixed> */
            public function get_results(): array
            {
                return [];
            }
        };
    }

    protected function tearDown(): void
    {
        Theme::reset();
        Monkey\tearDown();
        unset($GLOBALS['wpdb']);
        parent::tearDown();
    }

    public function testSeoControllerProducesHeadViewModelForPost(): void
    {
        $themePath = \dirname(__DIR__, 2);

        Functions\when('wp_head')->justReturn('');
        Functions\when('wp_footer')->justReturn('');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('get_bloginfo')->justReturn('Oli');
        Functions\when('get_template_directory_uri')->justReturn('https://example.com/theme');
        Functions\when('get_option')->justReturn(['enabled' => ['fr'], 'default' => 'fr']);
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_permalink')->justReturn('https://example.com/fr/about');
        Functions\when('wp_get_attachment_image_src')->justReturn(false);
        Functions\when('__')->returnArg(1);

        Theme::boot($themePath);

        $controller = Theme::container()->get(SeoControllerInterface::class);
        self::assertInstanceOf(SeoControllerInterface::class, $controller);

        $french = new \OliTheme\I18n\Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $post = new \OliTheme\Posts\PostEntity(
            id: 99,
            type: 'post',
            title: 'Le yoga au quotidien',
            content: '<p>Bonjour</p>',
            excerpt: 'Court extrait pour SEO.',
            slug: 'yoga-quotidien',
            language: $french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/fr/yoga-quotidien',
            publishedAt: new \DateTimeImmutable('2026-05-01'),
            updatedAt: null,
            author: 'Oli',
        );

        $vm = $controller->buildForPost($post);

        self::assertNotEmpty($vm->title);
        self::assertSame('index, follow', $vm->robots);
        self::assertStringContainsString('https://example.com', $vm->canonical);

        // og array contient au moins og:type
        self::assertArrayHasKey('og:type', $vm->og);
        self::assertSame('article', $vm->og['og:type']);

        // jsonLd décodé contient WebSite + Organization + Article + BreadcrumbList
        $decoded = json_decode($vm->jsonLd, true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('@graph', $decoded);
        $types = array_map(static fn (array $s): string => (string) ($s['@type'] ?? ''), $decoded['@graph']);
        self::assertContains('WebSite', $types);
        self::assertContains('Organization', $types);
        self::assertContains('Article', $types);
        self::assertContains('BreadcrumbList', $types);
    }
}
