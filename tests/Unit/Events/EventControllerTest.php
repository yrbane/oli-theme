<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Events;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Core\RendererInterface;
use OliTheme\Events\EventController;
use OliTheme\Events\EventEntity;
use OliTheme\Events\EventModelInterface;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;
use OliTheme\I18n\LanguageSwitcherViewModel;
use OliTheme\Navigation\MenuControllerInterface;
use OliTheme\Seo\BreadcrumbsControllerInterface;
use OliTheme\Seo\SeoControllerInterface;
use OliTheme\Seo\SeoHeadViewModel;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de EventController (rendu single).
 *
 * @package OliTheme\Tests\Unit\Events
 *
 * @since 1.0.0
 */
final class EventControllerTest extends TestCase
{
    private Language $french;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRenderSingleUsesEntity(): void
    {
        $entity = $this->buildEntity(42);

        Functions\when('get_queried_object_id')->justReturn(42);

        $events = $this->createMock(EventModelInterface::class);
        $events->method('findById')->with(42)->willReturn($entity);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($this->french);

        $switcherVm = new LanguageSwitcherViewModel($this->french, []);
        $switcher = $this->createMock(LanguageSwitcherControllerInterface::class);
        $switcher->method('build')->willReturn($switcherVm);

        $menus = $this->createMock(MenuControllerInterface::class);
        $menus->method('buildPrimary')->willReturn([]);
        $menus->method('buildFooter')->willReturn([]);

        $seoVm = new SeoHeadViewModel('T', 'D', 'index', 'https://ex.com', [], [], [], '{}');
        $seo = $this->createMock(SeoControllerInterface::class);
        $seo->method('buildForEvent')->willReturn($seoVm);
        $seo->method('buildFor404')->willReturn($seoVm);

        $breadcrumbs = $this->createMock(BreadcrumbsControllerInterface::class);
        $breadcrumbs->method('buildForEvent')->willReturn([]);
        $breadcrumbs->method('buildFor404')->willReturn([]);

        $capturedTemplate = null;
        $capturedViewModel = null;

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->method('render')->willReturnCallback(
            static function (string $tpl, array $vm) use (&$capturedTemplate, &$capturedViewModel): string {
                $capturedTemplate = $tpl;
                $capturedViewModel = $vm;

                return '<html>';
            },
        );

        $controller = new EventController($events, $resolver, $switcher, $menus, $seo, $breadcrumbs, $renderer);
        $html = $controller->renderSingle();

        self::assertSame('<html>', $html);
        self::assertSame('pages/single-event.html', $capturedTemplate);
        self::assertIsArray($capturedViewModel);
        self::assertInstanceOf(EventEntity::class, $capturedViewModel['event']);
        self::assertSame(42, $capturedViewModel['event']->id);
    }

    public function testRenderSingle404WhenMissing(): void
    {
        Functions\when('get_queried_object_id')->justReturn(0);

        $events = $this->createMock(EventModelInterface::class);
        $events->method('findById')->willReturn(null);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($this->french);

        $switcherVm = new LanguageSwitcherViewModel($this->french, []);
        $switcher = $this->createMock(LanguageSwitcherControllerInterface::class);
        $switcher->method('build')->willReturn($switcherVm);

        $menus = $this->createMock(MenuControllerInterface::class);
        $menus->method('buildPrimary')->willReturn([]);
        $menus->method('buildFooter')->willReturn([]);

        $seoVm = new SeoHeadViewModel('T', 'D', 'noindex', 'https://ex.com', [], [], [], '{}');
        $seo = $this->createMock(SeoControllerInterface::class);
        $seo->method('buildFor404')->willReturn($seoVm);

        $breadcrumbs = $this->createMock(BreadcrumbsControllerInterface::class);
        $breadcrumbs->method('buildFor404')->willReturn([]);

        $capturedTemplate = null;

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->method('render')->willReturnCallback(
            static function (string $tpl, array $vm) use (&$capturedTemplate): string {
                $capturedTemplate = $tpl;

                return '<html>';
            },
        );

        $controller = new EventController($events, $resolver, $switcher, $menus, $seo, $breadcrumbs, $renderer);
        $controller->renderSingle();

        self::assertSame('pages/404.html', $capturedTemplate);
    }

    /**
     * Construit un EventEntity de test avec les valeurs minimales.
     */
    private function buildEntity(int $id): EventEntity
    {
        return new EventEntity(
            id: $id,
            title: 'Test Event',
            description: '<p>Test</p>',
            excerpt: null,
            slug: 'test-event',
            startDate: new DateTimeImmutable('2026-06-01 18:00:00'),
            endDate: null,
            location: null,
            address: null,
            flyerUrl: null,
            registrationUrl: null,
            price: null,
            language: $this->french,
            permalink: 'https://example.com/evenements/test-event',
            isPast: false,
            isOngoing: false,
        );
    }
}
