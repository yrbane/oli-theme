<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;
use OliTheme\I18n\LanguageSwitcherViewModel;
use OliTheme\Navigation\MenuControllerInterface;
use OliTheme\Posts\PageController;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModelInterface;
use OliTheme\Seo\BreadcrumbsControllerInterface;
use OliTheme\Seo\SeoControllerInterface;
use OliTheme\Seo\SeoHeadViewModel;
use OliTheme\Slides\HomeCarouselControllerInterface;
use OliTheme\Slides\HomeCarouselViewModel;
use PHPUnit\Framework\TestCase;

final class PageControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRendersPageTemplateWithEntity(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $entity = new PostEntity(
            id: 7,
            type: 'page',
            title: 'À propos',
            content: '<p>Bio</p>',
            excerpt: null,
            slug: 'a-propos',
            language: $french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/fr/a-propos',
            publishedAt: new DateTimeImmutable('2026-01-01', new DateTimeZone('UTC')),
            updatedAt: null,
            author: null,
        );

        $model = $this->createMock(PostModelInterface::class);
        $model->method('find')->with(7)->willReturn($entity);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($french);

        $switcherVm = new LanguageSwitcherViewModel(current: $french, items: []);
        $switcher = $this->createMock(LanguageSwitcherControllerInterface::class);
        $switcher->method('build')->with(7)->willReturn($switcherVm);

        $menus = $this->createMock(MenuControllerInterface::class);
        $menus->method('buildPrimary')->willReturn([]);
        $menus->method('buildFooter')->willReturn([]);

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/page.html',
                self::callback(fn (array $vm): bool => $vm['post'] === $entity
                        && $vm['languageSwitcher'] === $switcherVm
                        && $vm['bodyClasses'] === 'page page-id-7 lang-fr'),
            )
            ->willReturn('<html>page</html>');

        Functions\when('get_queried_object_id')->justReturn(7);
        Functions\when('get_option')->justReturn(0);

        $carousel = $this->createMock(HomeCarouselControllerInterface::class);
        $carousel->expects(self::never())->method('build');

        $controller = new PageController(
            $model,
            $resolver,
            $switcher,
            $menus,
            $carousel,
            $this->buildSeoMock(),
            $this->buildBreadcrumbsMock(),
            $renderer,
        );

        self::assertSame('<html>page</html>', $controller->renderSingular());
    }

    public function testItRenders404WhenPostMissing(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $model = $this->createMock(PostModelInterface::class);
        $model->method('find')->willReturn(null);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($french);

        $switcher = $this->createMock(LanguageSwitcherControllerInterface::class);
        $switcher->method('build')->with(0)->willReturn(new LanguageSwitcherViewModel(current: $french, items: []));

        $menus = $this->createMock(MenuControllerInterface::class);
        $menus->method('buildPrimary')->willReturn([]);
        $menus->method('buildFooter')->willReturn([]);

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with('pages/404.html', self::isType('array'))
            ->willReturn('<html>404</html>');

        Functions\when('get_queried_object_id')->justReturn(0);
        Functions\when('get_option')->justReturn(0);

        $carousel = $this->createMock(HomeCarouselControllerInterface::class);

        $controller = new PageController(
            $model,
            $resolver,
            $switcher,
            $menus,
            $carousel,
            $this->buildSeoMock(),
            $this->buildBreadcrumbsMock(),
            $renderer,
        );

        self::assertSame('<html>404</html>', $controller->renderSingular());
    }

    public function testRenderSingularInjectsCarouselWhenFrontPage(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $entity = new PostEntity(
            id: 7,
            type: 'page',
            title: 'Accueil',
            content: '<p>Welcome</p>',
            excerpt: null,
            slug: 'accueil',
            language: $french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/fr/',
            publishedAt: new DateTimeImmutable('2026-01-01', new DateTimeZone('UTC')),
            updatedAt: null,
            author: null,
        );

        $model = $this->createMock(PostModelInterface::class);
        $model->method('find')->with(7)->willReturn($entity);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($french);

        $switcherVm = new LanguageSwitcherViewModel(current: $french, items: []);
        $switcher = $this->createMock(LanguageSwitcherControllerInterface::class);
        $switcher->method('build')->with(7)->willReturn($switcherVm);

        $menus = $this->createMock(MenuControllerInterface::class);
        $menus->method('buildPrimary')->willReturn([]);
        $menus->method('buildFooter')->willReturn([]);

        $carouselVm = new HomeCarouselViewModel(slides: [], autoplay: true, intervalMs: 5000, loop: true);
        $carousel = $this->createMock(HomeCarouselControllerInterface::class);
        $carousel->expects(self::once())->method('build')->willReturn($carouselVm);

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/page.html',
                self::callback(fn (array $vm): bool => $vm['post'] === $entity
                    && isset($vm['carousel'])
                    && $vm['carousel'] === $carouselVm),
            )
            ->willReturn('<html>home</html>');

        Functions\when('get_queried_object_id')->justReturn(7);
        Functions\when('get_option')->justReturn(7);

        $controller = new PageController(
            $model,
            $resolver,
            $switcher,
            $menus,
            $carousel,
            $this->buildSeoMock(),
            $this->buildBreadcrumbsMock(),
            $renderer,
        );

        self::assertSame('<html>home</html>', $controller->renderSingular());
    }

    private function buildSeoMock(): SeoControllerInterface
    {
        $seoVm = new SeoHeadViewModel('T', 'D', 'index', 'https://ex.com', [], [], [], '{}');
        $seo = $this->createMock(SeoControllerInterface::class);
        $seo->method('buildForPost')->willReturn($seoVm);
        $seo->method('buildFor404')->willReturn($seoVm);
        return $seo;
    }

    private function buildBreadcrumbsMock(): BreadcrumbsControllerInterface
    {
        $breadcrumbs = $this->createMock(BreadcrumbsControllerInterface::class);
        $breadcrumbs->method('buildForPost')->willReturn([]);
        $breadcrumbs->method('buildFor404')->willReturn([]);
        return $breadcrumbs;
    }
}
