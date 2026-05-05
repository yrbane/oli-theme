<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Events;

use Brain\Monkey;
use DateTimeImmutable;
use OliTheme\Core\RendererInterface;
use OliTheme\Events\EventArchiveController;
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
 * Tests unitaires de EventArchiveController.
 *
 * @package OliTheme\Tests\Unit\Events
 *
 * @since 1.0.0
 */
final class EventArchiveControllerTest extends TestCase
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

    public function testRenderArchiveReturnsBothLists(): void
    {
        $upcoming1 = $this->buildEntity(10, 'Événement A');
        $upcoming2 = $this->buildEntity(11, 'Événement B');
        $past1 = $this->buildEntity(20, 'Vieux événement');

        $events = $this->createMock(EventModelInterface::class);
        $events->method('findUpcoming')->willReturn([$upcoming1, $upcoming2]);
        $events->method('findPast')->willReturn([$past1]);

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
        $seo->method('buildForArchive')->willReturn($seoVm);

        $breadcrumbs = $this->createMock(BreadcrumbsControllerInterface::class);
        $breadcrumbs->method('buildForArchive')->willReturn([]);

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

        $controller = new EventArchiveController($events, $resolver, $switcher, $menus, $seo, $breadcrumbs, $renderer);
        $html = $controller->renderArchive();

        self::assertSame('<html>', $html);
        self::assertSame('pages/archive-event.html', $capturedTemplate);
        self::assertIsArray($capturedViewModel);
        self::assertCount(2, $capturedViewModel['upcomingEvents']);
        self::assertCount(1, $capturedViewModel['pastEvents']);
        self::assertInstanceOf(EventEntity::class, $capturedViewModel['upcomingEvents'][0]);
        self::assertInstanceOf(EventEntity::class, $capturedViewModel['pastEvents'][0]);
    }

    /**
     * Construit un EventEntity de test avec les valeurs minimales.
     */
    private function buildEntity(int $id, string $title): EventEntity
    {
        return new EventEntity(
            id: $id,
            title: $title,
            description: '',
            excerpt: null,
            slug: 'test-' . $id,
            startDate: new DateTimeImmutable('2026-06-01 18:00:00'),
            endDate: null,
            location: null,
            address: null,
            flyerUrl: null,
            registrationUrl: null,
            price: null,
            language: $this->french,
            permalink: 'https://example.com/evenements/test-' . $id,
            isPast: false,
            isOngoing: false,
        );
    }
}
