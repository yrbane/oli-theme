<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModelInterface;
use OliTheme\Seo\Admin\SeoOverviewPage;
use OliTheme\Seo\ScoreCalculatorInterface;
use OliTheme\Seo\SeoMeta;
use OliTheme\Seo\SeoMetaModelInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de SeoOverviewPage (dashboard SEO admin).
 *
 * @package OliTheme\Tests\Unit\Seo\Admin
 *
 * @since 1.0.0
 */
final class SeoOverviewPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $_GET     = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        $_GET     = [];
        $_REQUEST = [];
        parent::tearDown();
    }

    public function testImplementsAdminTabInterface(): void
    {
        $page = $this->makePage();

        self::assertSame('dashboard', $page->id());
        self::assertSame('seo', $page->group());
        self::assertSame('manage_options', $page->capability());
    }

    public function testLabelIsTranslated(): void
    {
        Functions\when('__')->returnArg(1);

        self::assertSame('Dashboard', $this->makePage()->label());
    }

    /**
     * render() doit récupérer tous les contenus, calculer leurs scores et les passer au template.
     */
    public function testRenderListsContentsWithScores(): void
    {
        $this->stubCommonFunctions();
        Functions\when('get_posts')->justReturn([1, 2]);
        Functions\when('wp_count_posts')->justReturn((object) ['publish' => 2]);

        $post1 = $this->makePostEntity(1, 'Article un', 'page');
        $post2 = $this->makePostEntity(2, 'Article deux', 'oli_event');

        $postModel = $this->createMock(PostModelInterface::class);
        $postModel->method('find')->willReturnMap([
            [1, $post1],
            [2, $post2],
        ]);

        $metaModel = $this->createMock(SeoMetaModelInterface::class);
        $metaModel->method('find')->willReturn(new SeoMeta(focusKeyword: 'test'));

        $score = $this->createMock(ScoreCalculatorInterface::class);
        \assert($score instanceof ScoreCalculatorInterface);
        $score->method('calculate')->willReturnOnConsecutiveCalls(72, 35);

        /** @var array<string, mixed> $captured */
        $captured = [];

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                'admin/seo-overview.html',
                self::callback(static function (array $vars) use (&$captured): bool {
                    $captured = $vars;
                    return true;
                }),
            )
            ->willReturn('');

        (new SeoOverviewPage($renderer, $postModel, $metaModel, $score))->renderPanel();

        self::assertCount(2, $captured['items']);
        self::assertSame(1, $captured['items'][0]['id']);
        self::assertSame('Article un', $captured['items'][0]['title']);
        self::assertSame(72, $captured['items'][0]['score']);
        self::assertSame(35, $captured['items'][1]['score']);
        self::assertSame('page', $captured['items'][0]['type']);
        self::assertSame('oli_event', $captured['items'][1]['type']);
    }

    /**
     * Le filtre min_score doit exclure les contenus sous le seuil.
     */
    public function testRenderAppliesMinScoreFilter(): void
    {
        $this->stubCommonFunctions();
        $_GET['min_score'] = '50';
        Functions\when('get_posts')->justReturn([1, 2]);
        Functions\when('wp_count_posts')->justReturn((object) ['publish' => 2]);

        $postModel = $this->createMock(PostModelInterface::class);
        $postModel->method('find')->willReturnMap([
            [1, $this->makePostEntity(1, 'Bon', 'post')],
            [2, $this->makePostEntity(2, 'Mauvais', 'post')],
        ]);

        $metaModel = $this->createMock(SeoMetaModelInterface::class);
        $metaModel->method('find')->willReturn(new SeoMeta());

        $score = $this->createMock(ScoreCalculatorInterface::class);
        \assert($score instanceof ScoreCalculatorInterface);
        $score->method('calculate')->willReturnOnConsecutiveCalls(80, 20);

        $captured = [];
        $renderer = $this->createMock(RendererInterface::class);
        $renderer->method('render')->willReturnCallback(
            static function (string $tpl, array $vars) use (&$captured): string {
                $captured = $vars;
                return '';
            },
        );

        (new SeoOverviewPage($renderer, $postModel, $metaModel, $score))->renderPanel();

        self::assertCount(1, $captured['items']);
        self::assertSame(1, $captured['items'][0]['id']);
        self::assertSame(80, $captured['items'][0]['score']);
    }

    /**
     * Le filtre type ne doit interroger get_posts qu'avec ce type.
     */
    public function testRenderAppliesTypeFilter(): void
    {
        $this->stubCommonFunctions();
        $_GET['type'] = 'oli_event';

        $capturedArgs = null;
        Functions\when('get_posts')->alias(
            static function (array $args) use (&$capturedArgs): array {
                $capturedArgs = $args;
                return [];
            },
        );
        Functions\when('wp_count_posts')->justReturn((object) ['publish' => 0]);

        $postModel = $this->createMock(PostModelInterface::class);
        $metaModel = $this->createMock(SeoMetaModelInterface::class);
        $score = $this->createMock(ScoreCalculatorInterface::class);
        \assert($score instanceof ScoreCalculatorInterface);

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->method('render')->willReturn('');

        (new SeoOverviewPage($renderer, $postModel, $metaModel, $score))->renderPanel();

        self::assertNotNull($capturedArgs);
        self::assertSame(['oli_event'], $capturedArgs['post_type']);
    }

    /**
     * handleExportCsv doit envoyer les en-têtes CSV et écrire les lignes.
     */
    public function testHandleExportCsvOutputsCsvWithFilteredItems(): void
    {
        $this->stubCommonFunctions();
        Functions\when('get_posts')->justReturn([1]);

        $postModel = $this->createMock(PostModelInterface::class);
        $postModel->method('find')->willReturn($this->makePostEntity(1, 'Mon article', 'post'));

        $metaModel = $this->createMock(SeoMetaModelInterface::class);
        $metaModel->method('find')->willReturn(new SeoMeta(
            description: 'Une meta description suffisamment longue pour passer la validation seo et atteindre 120 caracteres minimum requise pour la regle.',
            focusKeyword: 'mot-cle',
        ));

        $score = $this->createMock(ScoreCalculatorInterface::class);
        \assert($score instanceof ScoreCalculatorInterface);
        $score->method('calculate')->willReturn(85);

        $renderer = $this->createMock(RendererInterface::class);

        $page = new SeoOverviewPage($renderer, $postModel, $metaModel, $score);

        ob_start();
        $page->handleExportCsv();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('Mon article', $output);
        self::assertStringContainsString('85', $output);
        self::assertStringContainsString('mot-cle', $output);
    }

    private function makePage(): SeoOverviewPage
    {
        return new SeoOverviewPage(
            $this->createMock(RendererInterface::class),
            $this->createMock(PostModelInterface::class),
            $this->createMock(SeoMetaModelInterface::class),
            $this->createMock(ScoreCalculatorInterface::class),
        );
    }

    private function makePostEntity(int $id, string $title, string $type): PostEntity
    {
        return new PostEntity(
            id: $id,
            type: $type,
            title: $title,
            content: 'Contenu de test.',
            excerpt: null,
            slug: 'slug-' . $id,
            language: new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR'),
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'http://example.com/' . $id,
            publishedAt: new DateTimeImmutable('2026-01-01 00:00:00'),
            updatedAt: null,
            author: 'Auteur',
        );
    }

    private function stubCommonFunctions(): void
    {
        Functions\when('__')->returnArg(1);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_admin_referer')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('admin_url')->alias(
            static fn (string $path = ''): string => 'http://example.test/wp-admin/' . ltrim($path, '/'),
        );
        Functions\when('get_edit_post_link')->alias(
            static fn (int $id): string => 'http://example.test/wp-admin/post.php?post=' . $id . '&action=edit',
        );
        Functions\when('get_post_status')->justReturn('publish');
        Functions\when('add_query_arg')->alias(
            static fn (array|string $key, string $value = '', string $url = ''): string => \is_array($key) ? $url . '?' . http_build_query($key) : $url . '?' . $key . '=' . $value,
        );
        Functions\when('sanitize_key')->returnArg(1);
        Functions\when('wp_create_nonce')->justReturn('test-nonce');
        Functions\when('wp_verify_nonce')->justReturn(true);
    }
}
