<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Events\EventEntity;
use OliTheme\Events\EventModelInterface;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModelInterface;
use OliTheme\Seo\SitemapController;
use OliTheme\Seo\SitemapEntryBuilder;
use OliTheme\Seo\SitemapIndexBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests de SitemapController.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class SitemapControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('home_url')->alias(static fn (string $path = '') => 'https://example.com' . $path);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testGetIndexBuildsUrlsForEachLangAndType(): void
    {
        $fr = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');
        $en = new Language('en', 'English', 'English', '🇬🇧', 'en_US');

        $registry = $this->createRegistryMock([$fr, $en]);

        $controller = new SitemapController(
            registry: $registry,
            posts: $this->createPostsModelMock([]),
            events: $this->createEventsModelMock([]),
            entryBuilder: new SitemapEntryBuilder(),
            indexBuilder: new SitemapIndexBuilder(),
        );

        $xml = $controller->getIndex();

        self::assertSame(6, substr_count($xml, '<sitemap>'));
        self::assertStringContainsString('sitemap-post-fr.xml', $xml);
        self::assertStringContainsString('sitemap-page-fr.xml', $xml);
        self::assertStringContainsString('sitemap-oli_event-fr.xml', $xml);
        self::assertStringContainsString('sitemap-post-en.xml', $xml);
        self::assertStringContainsString('sitemap-page-en.xml', $xml);
        self::assertStringContainsString('sitemap-oli_event-en.xml', $xml);
    }

    public function testGetSubsitemapForPostsBuildsUrlset(): void
    {
        $lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');

        $posts = [
            $this->makePost(id: 1, permalink: 'https://example.com/fr/article-1', publishedAt: '2024-01-01'),
            $this->makePost(id: 2, permalink: 'https://example.com/fr/article-2', publishedAt: '2024-02-01'),
        ];

        $controller = new SitemapController(
            registry: $this->createRegistryMock([]),
            posts: $this->createPostsModelMock($posts),
            events: $this->createEventsModelMock([]),
            entryBuilder: new SitemapEntryBuilder(),
            indexBuilder: new SitemapIndexBuilder(),
        );

        $xml = $controller->getSubsitemap('post', $lang);

        self::assertStringContainsString('<urlset', $xml);
        self::assertSame(2, substr_count($xml, '<url>'));
        self::assertStringContainsString('https://example.com/fr/article-1', $xml);
        self::assertStringContainsString('https://example.com/fr/article-2', $xml);
    }

    public function testGetSubsitemapForEventsUsesEventModel(): void
    {
        $lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');

        $events = [
            $this->makeEvent(id: 10, permalink: 'https://example.com/fr/evenements/yoga', startDate: '2024-06-15'),
        ];

        $controller = new SitemapController(
            registry: $this->createRegistryMock([]),
            posts: $this->createPostsModelMock([]),
            events: $this->createEventsModelMock($events),
            entryBuilder: new SitemapEntryBuilder(),
            indexBuilder: new SitemapIndexBuilder(),
        );

        $xml = $controller->getSubsitemap('oli_event', $lang);

        self::assertStringContainsString('<urlset', $xml);
        self::assertSame(1, substr_count($xml, '<url>'));
        self::assertStringContainsString('https://example.com/fr/evenements/yoga', $xml);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param Language[] $languages
     *
     * @return LanguageRegistryInterface&MockObject
     */
    private function createRegistryMock(array $languages): LanguageRegistryInterface
    {
        $mock = $this->createMock(LanguageRegistryInterface::class);
        $mock->method('all')->willReturn($languages);

        return $mock;
    }

    /**
     * @param PostEntity[] $posts
     *
     * @return PostModelInterface&MockObject
     */
    private function createPostsModelMock(array $posts): PostModelInterface
    {
        $mock = $this->createMock(PostModelInterface::class);
        $mock->method('findByLanguage')->willReturn($posts);

        return $mock;
    }

    /**
     * @param EventEntity[] $events
     *
     * @return EventModelInterface&MockObject
     */
    private function createEventsModelMock(array $events): EventModelInterface
    {
        $mock = $this->createMock(EventModelInterface::class);
        $mock->method('findUpcoming')->willReturn($events);

        return $mock;
    }

    private function makePost(int $id, string $permalink, string $publishedAt): PostEntity
    {
        $lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');

        return new PostEntity(
            id: $id,
            type: 'post',
            title: 'Article ' . $id,
            content: '',
            excerpt: null,
            slug: 'article-' . $id,
            language: $lang,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: $permalink,
            publishedAt: new DateTimeImmutable($publishedAt),
            updatedAt: null,
            author: null,
        );
    }

    private function makeEvent(int $id, string $permalink, string $startDate): EventEntity
    {
        $lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');

        return new EventEntity(
            id: $id,
            title: 'Événement ' . $id,
            description: '',
            excerpt: null,
            slug: 'evenement-' . $id,
            startDate: new DateTimeImmutable($startDate),
            endDate: null,
            location: null,
            address: null,
            flyerUrl: null,
            registrationUrl: null,
            price: null,
            language: $lang,
            permalink: $permalink,
            isPast: false,
            isOngoing: false,
        );
    }
}
