<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Events\EventEntity;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\TranslationModelInterface;
use OliTheme\Posts\PostEntity;
use OliTheme\Seo\BreadcrumbItemEntity;
use OliTheme\Seo\BreadcrumbsControllerInterface;
use OliTheme\Seo\CanonicalBuilder;
use OliTheme\Seo\HreflangBuilder;
use OliTheme\Seo\OpenGraphBuilder;
use OliTheme\Seo\RobotsBuilder;
use OliTheme\Seo\SeoController;
use OliTheme\Seo\SeoMeta;
use OliTheme\Seo\SeoMetaModelInterface;
use OliTheme\Seo\TwitterCardBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests du SeoController.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class SeoControllerTest extends TestCase
{
    private SeoMetaModelInterface $metaModel;
    private BreadcrumbsControllerInterface $breadcrumbsController;
    private SeoController $controller;
    private Language $french;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Stubs WP globaux
        Functions\when('home_url')->alias(static fn (string $path = '') => 'https://example.com' . $path);
        Functions\when('get_bloginfo')->justReturn('Mon Site');
        Functions\when('get_permalink')->justReturn('https://example.com/fr/post/');
        Functions\when('apply_filters')->alias(static fn (string $tag, mixed $value) => $value);
        Functions\when('__')->alias(static fn (string $text, string $domain = 'default') => $text);
        Functions\when('wp_get_attachment_image_src')->justReturn(false);

        $this->french = new Language(
            code: 'fr',
            label: 'Français',
            nativeLabel: 'Français',
            flag: 'fr',
            locale: 'fr_FR',
        );

        // Mock SeoMetaModelInterface
        $this->metaModel = $this->createMock(SeoMetaModelInterface::class);
        $this->metaModel
            ->method('find')
            ->willReturn(new SeoMeta(
                title: null,
                description: null,
                focusKeyword: null,
                additionalKeywords: [],
                ogImageId: null,
                twitterCardType: 'summary',
                noindex: false,
                nofollow: false,
                canonical: null,
                priority: null,
                changefreq: null,
                readabilityScore: null,
                seoScore: null,
            ));

        // Mock BreadcrumbsControllerInterface
        $this->breadcrumbsController = $this->createMock(BreadcrumbsControllerInterface::class);
        $this->breadcrumbsController->method('buildForPost')->willReturn([
            new BreadcrumbItemEntity('Accueil', 'https://example.com/', false),
            new BreadcrumbItemEntity('Mon article', 'https://example.com/fr/post/', true),
        ]);
        $this->breadcrumbsController->method('buildForEvent')->willReturn([
            new BreadcrumbItemEntity('Accueil', 'https://example.com/', false),
            new BreadcrumbItemEntity('Mon événement', 'https://example.com/fr/event/', true),
        ]);
        $this->breadcrumbsController->method('buildForArchive')->willReturn([
            new BreadcrumbItemEntity('Accueil', 'https://example.com/', false),
        ]);
        $this->breadcrumbsController->method('buildForSearch')->willReturn([]);
        $this->breadcrumbsController->method('buildFor404')->willReturn([]);

        // Mocks LanguageRegistryInterface et TranslationModelInterface pour HreflangBuilder
        $registry = $this->createMock(LanguageRegistryInterface::class);
        $registry->method('all')->willReturn([]);
        $registry->method('default')->willReturn($this->french);

        $translation = $this->createMock(TranslationModelInterface::class);
        $translation->method('getTranslations')->willReturn([]);

        $this->controller = new SeoController(
            meta: $this->metaModel,
            canonical: new CanonicalBuilder(),
            hreflang: new HreflangBuilder($registry, $translation),
            robots: new RobotsBuilder(),
            og: new OpenGraphBuilder(),
            twitter: new TwitterCardBuilder(),
            breadcrumbs: $this->breadcrumbsController,
        );
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testBuildForPostProducesFullHeadViewModel(): void
    {
        $post = $this->makePost();

        $vm = $this->controller->buildForPost($post);

        self::assertStringContainsString('Mon Site', $vm->title);
        self::assertSame('index, follow', $vm->robots);
        self::assertSame('article', $vm->og['og:type']);

        $graph = $this->decodeGraph($vm->jsonLd);
        $types = array_column($graph, '@type');

        self::assertContains('WebSite', $types);
        self::assertContains('Organization', $types);
        self::assertContains('Article', $types);
        self::assertContains('BreadcrumbList', $types);
    }

    public function testBuildForEventIncludesEventSchema(): void
    {
        $event = $this->makeEvent();

        $vm = $this->controller->buildForEvent($event);

        $graph = $this->decodeGraph($vm->jsonLd);
        $types = array_column($graph, '@type');

        self::assertContains('Event', $types);
    }

    public function testBuildForArchiveProducesIndexFollow(): void
    {
        $vm = $this->controller->buildForArchive('post', $this->french);

        self::assertSame('index, follow', $vm->robots);
    }

    public function testBuildForSearchSetsNoindex(): void
    {
        $vm = $this->controller->buildForSearch('jazz', $this->french);

        self::assertSame('noindex, follow', $vm->robots);
    }

    public function testBuildFor404SetsNoindex(): void
    {
        $vm = $this->controller->buildFor404($this->french);

        self::assertSame('noindex, follow', $vm->robots);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePost(): PostEntity
    {
        return new PostEntity(
            id: 1,
            type: 'post',
            title: 'Mon article de test',
            content: '<p>Contenu</p>',
            excerpt: 'Un extrait court.',
            slug: 'mon-article-de-test',
            language: $this->french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/fr/post/',
            publishedAt: new DateTimeImmutable('2026-01-01'),
            updatedAt: null,
            author: 'Auteur Test',
        );
    }

    private function makeEvent(): EventEntity
    {
        return new EventEntity(
            id: 2,
            title: 'Mon événement de test',
            description: '<p>Description de l\'événement.</p>',
            excerpt: 'Un résumé court.',
            slug: 'mon-evenement-de-test',
            startDate: new DateTimeImmutable('2026-06-15 20:00:00'),
            endDate: null,
            location: null,
            address: null,
            flyerUrl: null,
            registrationUrl: null,
            price: null,
            language: $this->french,
            permalink: 'https://example.com/fr/event/',
            isPast: false,
            isOngoing: false,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeGraph(string $jsonLd): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = (array) json_decode($jsonLd, true);
        /** @var array<int, array<string, mixed>> $graph */
        $graph = (array) ($decoded['@graph'] ?? []);
        return $graph;
    }
}
