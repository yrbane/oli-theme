<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Events\EventEntity;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;
use OliTheme\Seo\BreadcrumbsController;
use PHPUnit\Framework\TestCase;

/**
 * Tests de BreadcrumbsController.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class BreadcrumbsControllerTest extends TestCase
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

    public function testBuildForPagePost(): void
    {
        $lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');
        $post = $this->makePost(type: 'page', title: 'À propos', lang: $lang);

        $controller = new BreadcrumbsController();
        $crumbs = $controller->buildForPost($post);

        self::assertCount(2, $crumbs);
        self::assertSame('Accueil', $crumbs[0]->label);
        self::assertFalse($crumbs[0]->isCurrent);
        self::assertSame('À propos', $crumbs[1]->label);
        self::assertTrue($crumbs[1]->isCurrent);
    }

    public function testBuildForBlogPost(): void
    {
        $lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');
        $post = $this->makePost(type: 'post', title: 'Mon article', lang: $lang);

        $controller = new BreadcrumbsController();
        $crumbs = $controller->buildForPost($post);

        self::assertCount(3, $crumbs);
        self::assertSame('Accueil', $crumbs[0]->label);
        self::assertFalse($crumbs[0]->isCurrent);
        self::assertSame('Actualités', $crumbs[1]->label);
        self::assertFalse($crumbs[1]->isCurrent);
        self::assertSame('Mon article', $crumbs[2]->label);
        self::assertTrue($crumbs[2]->isCurrent);
    }

    public function testBuildForEvent(): void
    {
        $lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');
        $event = $this->makeEvent(title: 'Stage yoga', lang: $lang);

        $controller = new BreadcrumbsController();
        $crumbs = $controller->buildForEvent($event);

        self::assertCount(3, $crumbs);
        self::assertSame('Accueil', $crumbs[0]->label);
        self::assertFalse($crumbs[0]->isCurrent);
        self::assertSame('Événements', $crumbs[1]->label);
        self::assertFalse($crumbs[1]->isCurrent);
        self::assertSame('Stage yoga', $crumbs[2]->label);
        self::assertTrue($crumbs[2]->isCurrent);
    }

    public function testBuildForSearch(): void
    {
        $lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');

        $controller = new BreadcrumbsController();
        $crumbs = $controller->buildForSearch('yoga', $lang);

        self::assertCount(2, $crumbs);
        self::assertSame('Accueil', $crumbs[0]->label);
        self::assertFalse($crumbs[0]->isCurrent);
        self::assertSame('Recherche : yoga', $crumbs[1]->label);
        self::assertTrue($crumbs[1]->isCurrent);
    }

    public function testBuildFor404(): void
    {
        $lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');

        $controller = new BreadcrumbsController();
        $crumbs = $controller->buildFor404($lang);

        self::assertCount(2, $crumbs);
        self::assertSame('Accueil', $crumbs[0]->label);
        self::assertFalse($crumbs[0]->isCurrent);
        self::assertSame('Page introuvable', $crumbs[1]->label);
        self::assertTrue($crumbs[1]->isCurrent);
    }

    public function testBuildForPostInEnglish(): void
    {
        $lang = new Language('en', 'English', 'English', '🇬🇧', 'en_US');
        $post = $this->makePost(type: 'post', title: 'My article', lang: $lang);

        $crumbs = (new BreadcrumbsController())->buildForPost($post);

        self::assertSame('Home', $crumbs[0]->label);
        self::assertSame('News', $crumbs[1]->label);
    }

    public function testBuildForEventInEnglish(): void
    {
        $lang  = new Language('en', 'English', 'English', '🇬🇧', 'en_US');
        $event = $this->makeEvent(title: 'Yoga retreat', lang: $lang);

        $crumbs = (new BreadcrumbsController())->buildForEvent($event);

        self::assertSame('Home', $crumbs[0]->label);
        self::assertSame('Events', $crumbs[1]->label);
    }

    public function testBuildForSearchInEnglish(): void
    {
        $lang = new Language('en', 'English', 'English', '🇬🇧', 'en_US');

        $crumbs = (new BreadcrumbsController())->buildForSearch('yoga', $lang);

        self::assertSame('Home', $crumbs[0]->label);
        self::assertSame('Search: yoga', $crumbs[1]->label);
    }

    public function testBuildFor404InEnglish(): void
    {
        $lang = new Language('en', 'English', 'English', '🇬🇧', 'en_US');

        $crumbs = (new BreadcrumbsController())->buildFor404($lang);

        self::assertSame('Home', $crumbs[0]->label);
        self::assertSame('Page not found', $crumbs[1]->label);
    }

    public function testBuildForArchivePostInEnglish(): void
    {
        $lang = new Language('en', 'English', 'English', '🇬🇧', 'en_US');

        $crumbs = (new BreadcrumbsController())->buildForArchive('post', $lang);

        self::assertSame('Home', $crumbs[0]->label);
        self::assertSame('News', $crumbs[1]->label);
    }

    public function testBuildForArchiveEventInItalian(): void
    {
        $lang = new Language('it', 'Italian', 'Italiano', '🇮🇹', 'it_IT');

        $crumbs = (new BreadcrumbsController())->buildForArchive('oli_event', $lang);

        self::assertSame('Home', $crumbs[0]->label);
        self::assertSame('Eventi', $crumbs[1]->label);
    }

    public function testBuildForPostInSpanish(): void
    {
        $lang = new Language('es', 'Spanish', 'Español', '🇪🇸', 'es_ES');
        $post = $this->makePost(type: 'post', title: 'Mi artículo', lang: $lang);

        $crumbs = (new BreadcrumbsController())->buildForPost($post);

        self::assertSame('Inicio', $crumbs[0]->label);
        self::assertSame('Noticias', $crumbs[1]->label);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePost(string $type, string $title, Language $lang): PostEntity
    {
        return new PostEntity(
            id: 1,
            type: $type,
            title: $title,
            content: '',
            excerpt: null,
            slug: 'test-slug',
            language: $lang,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/' . $lang->code . '/' . $title,
            publishedAt: new DateTimeImmutable('2024-01-01'),
            updatedAt: null,
            author: null,
        );
    }

    private function makeEvent(string $title, Language $lang): EventEntity
    {
        return new EventEntity(
            id: 2,
            title: $title,
            description: '',
            excerpt: null,
            slug: 'event-slug',
            startDate: new DateTimeImmutable('2024-06-01'),
            endDate: null,
            location: null,
            address: null,
            flyerUrl: null,
            registrationUrl: null,
            price: null,
            language: $lang,
            permalink: 'https://example.com/' . $lang->code . '/evenements/' . $title,
            isPast: false,
            isOngoing: false,
        );
    }
}
