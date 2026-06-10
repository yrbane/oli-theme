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
use OliTheme\I18n\TranslationModelInterface;
use OliTheme\Navigation\MenuControllerInterface;
use OliTheme\Posts\PageController;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModelInterface;
use OliTheme\Seo\BreadcrumbsControllerInterface;
use OliTheme\Seo\SeoControllerInterface;
use OliTheme\Seo\SeoHeadViewModel;
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

        $controller = new PageController(
            $model,
            $resolver,
            $switcher,
            $menus,
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

        $controller = new PageController(
            $model,
            $resolver,
            $switcher,
            $menus,
            $this->buildSeoMock(),
            $this->buildBreadcrumbsMock(),
            $renderer,
        );

        self::assertSame('<html>404</html>', $controller->renderSingular());
    }

    public function testRenderSingularAddsHomeBodyClassWhenFrontPage(): void
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

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/page.html',
                self::callback(fn (array $vm): bool => $vm['post'] === $entity
                    && \str_starts_with((string) $vm['bodyClasses'], 'home ')),
            )
            ->willReturn('<html>home</html>');

        Functions\when('get_queried_object_id')->justReturn(7);
        Functions\when('get_option')->justReturn(7);

        $controller = new PageController(
            $model,
            $resolver,
            $switcher,
            $menus,
            $this->buildSeoMock(),
            $this->buildBreadcrumbsMock(),
            $renderer,
        );

        self::assertSame('<html>home</html>', $controller->renderSingular());
    }

    public function testRenderSingularAddsHomeBodyClassForTranslatedFrontPage(): void
    {
        $english = new Language('en', 'English', 'English', '🇬🇧', 'en_GB', 'ltr');

        $entity = new PostEntity(
            id: 8,
            type: 'page',
            title: 'Home',
            content: '<p>Welcome</p>',
            excerpt: null,
            slug: 'home',
            language: $english,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/en/home',
            publishedAt: new DateTimeImmutable('2026-01-01', new DateTimeZone('UTC')),
            updatedAt: null,
            author: null,
        );

        $model = $this->createMock(PostModelInterface::class);
        $model->method('find')->with(8)->willReturn($entity);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($english);

        $switcherVm = new LanguageSwitcherViewModel(current: $english, items: []);
        $switcher = $this->createMock(LanguageSwitcherControllerInterface::class);
        $switcher->method('build')->with(8)->willReturn($switcherVm);

        $menus = $this->createMock(MenuControllerInterface::class);
        $menus->method('buildPrimary')->willReturn([]);
        $menus->method('buildFooter')->willReturn([]);

        $translations = $this->createMock(TranslationModelInterface::class);
        $translations->method('getTranslations')->with(7)->willReturn(['fr' => 7, 'en' => 8]);

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/page.html',
                self::callback(fn (array $vm): bool => \str_starts_with((string) $vm['bodyClasses'], 'home ')),
            )
            ->willReturn('<html>en-home</html>');

        Functions\when('get_queried_object_id')->justReturn(8);
        Functions\when('get_option')->justReturn(7);

        $controller = new PageController(
            $model,
            $resolver,
            $switcher,
            $menus,
            $this->buildSeoMock(),
            $this->buildBreadcrumbsMock(),
            $renderer,
            new \OliTheme\Posts\CoverExtractor(),
            null,
            $translations,
        );

        self::assertSame('<html>en-home</html>', $controller->renderSingular());
    }

    /**
     * Sur la page galerie photos, le contrôleur doit exposer `galleryDataJson`
     * — un JSON {"all": [...], "folder-<slug>": [...]} consommé par le JS de
     * filtres pour permettre de basculer la galerie principale d'un dossier à
     * un autre sans recharger la page.
     */
    public function testGalleryPhotosPageExposesGalleryDataJson(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $entity = new PostEntity(
            id: 42,
            type: 'page',
            title: 'Photos',
            content: '',
            excerpt: null,
            slug: 'photos',
            language: $french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/galerie/photos',
            publishedAt: new DateTimeImmutable('2026-01-01', new DateTimeZone('UTC')),
            updatedAt: null,
            author: null,
        );

        $model = $this->createMock(PostModelInterface::class);
        $model->method('find')->with(42)->willReturn($entity);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($french);

        $switcher = $this->createMock(LanguageSwitcherControllerInterface::class);
        $switcher->method('build')->willReturn(new LanguageSwitcherViewModel(current: $french, items: []));

        $menus = $this->createMock(MenuControllerInterface::class);
        $menus->method('buildPrimary')->willReturn([]);
        $menus->method('buildFooter')->willReturn([]);

        // Les services finalisés (GalleryRepository, MediaFolderQuery,
        // MediaFoldersGallerySettings) sont instanciés réellement ; on stubbe
        // leurs fonctions WP en aval. Le dossier `test` est coché pour que la
        // page galerie photos l'expose comme bucket `folder-test`.
        Functions\when('get_option')->alias(static fn (string $key, $default = null) => match ($key) {
            \OliTheme\MediaFolders\MediaFoldersGallerySettings::OPTION => ['test'],
            default => $default,
        });
        Functions\when('taxonomy_exists')->justReturn(true);
        Functions\when('get_terms')->justReturn([
            (object) ['slug' => 'test', 'name' => 'Test', 'parent' => 0, 'term_id' => 9, 'count' => 1],
        ]);
        Functions\when('get_posts')->justReturn([
            (object) ['ID' => 2, 'post_excerpt' => '', 'post_title' => ''],
        ]);
        Functions\when('wp_get_attachment_image_url')->justReturn('u2');
        Functions\when('wp_get_attachment_image_srcset')->justReturn('');
        Functions\when('get_post_meta')->justReturn('b');

        $gallery         = new \OliTheme\Gallery\GalleryRepository();
        $folderQuery     = new \OliTheme\MediaFolders\MediaFolderQuery();
        $gallerySettings = new \OliTheme\MediaFolders\MediaFoldersGallerySettings();

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/gallery-photos.html',
                self::callback(function (array $vm): bool {
                    if (!isset($vm['galleryDataJson']) || !\is_string($vm['galleryDataJson'])) {
                        return false;
                    }
                    $decoded = json_decode($vm['galleryDataJson'], true);

                    return \is_array($decoded)
                        && \array_key_exists('all', $decoded)
                        && ($decoded['folder-test'][0]['id'] ?? null) === 2;
                }),
            )
            ->willReturn('<html>photos</html>');

        Functions\when('get_queried_object_id')->justReturn(42);

        $controller = new PageController(
            $model,
            $resolver,
            $switcher,
            $menus,
            $this->buildSeoMock(),
            $this->buildBreadcrumbsMock(),
            $renderer,
            new \OliTheme\Posts\CoverExtractor(),
            $gallery,
            null,
            null,
            null,
            null,
            $folderQuery,
            $gallerySettings,
        );

        self::assertSame('<html>photos</html>', $controller->renderSingular());
    }

    /**
     * Quand `oli_gallery_folders` est configuré, seuls les dossiers présents
     * dans la liste sont rendus comme buckets (les autres sont ignorés même
     * s'ils contiennent des photos). Permet à l'éditeur de cocher quels
     * dossiers exposer publiquement.
     */
    public function testGalleryPhotosPageFiltersBuildFolderGalleriesByConfig(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $entity = new PostEntity(
            id: 42,
            type: 'page',
            title: 'Photos',
            content: '',
            excerpt: null,
            slug: 'photos',
            language: $french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/galerie/photos',
            publishedAt: new DateTimeImmutable('2026-01-01', new DateTimeZone('UTC')),
            updatedAt: null,
            author: null,
        );

        $model = $this->createMock(PostModelInterface::class);
        $model->method('find')->with(42)->willReturn($entity);

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($french);

        $switcher = $this->createMock(LanguageSwitcherControllerInterface::class);
        $switcher->method('build')->willReturn(new LanguageSwitcherViewModel(current: $french, items: []));

        $menus = $this->createMock(MenuControllerInterface::class);
        $menus->method('buildPrimary')->willReturn([]);
        $menus->method('buildFooter')->willReturn([]);

        // Deux dossiers existent ; seul `voyage` est coché.
        Functions\when('get_option')->alias(static fn (string $key, $default = null) => match ($key) {
            \OliTheme\MediaFolders\MediaFoldersGallerySettings::OPTION => ['voyage'],
            default => $default,
        });
        Functions\when('taxonomy_exists')->justReturn(true);
        Functions\when('get_terms')->justReturn([
            (object) ['slug' => 'voyage', 'name' => 'Voyage', 'parent' => 0, 'term_id' => 1, 'count' => 1],
            (object) ['slug' => 'archives', 'name' => 'Archives', 'parent' => 0, 'term_id' => 2, 'count' => 1],
        ]);
        Functions\when('get_posts')->justReturn([
            (object) ['ID' => 10, 'post_excerpt' => '', 'post_title' => ''],
        ]);
        Functions\when('wp_get_attachment_image_url')->justReturn('u');
        Functions\when('wp_get_attachment_image_srcset')->justReturn('');
        Functions\when('get_post_meta')->justReturn('');

        $gallery         = new \OliTheme\Gallery\GalleryRepository();
        $folderQuery     = new \OliTheme\MediaFolders\MediaFolderQuery();
        $gallerySettings = new \OliTheme\MediaFolders\MediaFoldersGallerySettings();

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/gallery-photos.html',
                self::callback(static function (array $vm): bool {
                    $slugs = array_map(static fn (array $f): string => (string) $f['slug'], $vm['folderGalleries'] ?? []);

                    return $slugs === ['voyage'];
                }),
            )
            ->willReturn('<html>photos</html>');

        Functions\when('get_queried_object_id')->justReturn(42);

        $controller = new PageController(
            $model,
            $resolver,
            $switcher,
            $menus,
            $this->buildSeoMock(),
            $this->buildBreadcrumbsMock(),
            $renderer,
            new \OliTheme\Posts\CoverExtractor(),
            $gallery,
            null,
            null,
            null,
            null,
            $folderQuery,
            $gallerySettings,
        );

        self::assertSame('<html>photos</html>', $controller->renderSingular());
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
