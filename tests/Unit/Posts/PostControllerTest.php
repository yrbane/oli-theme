<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;
use OliTheme\I18n\LanguageSwitcherViewModel;
use OliTheme\Posts\PostController;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModelInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PostControllerTest extends TestCase
{
    private Language $french;

    /** @var MockObject&PostModelInterface */
    private MockObject $model;

    /** @var MockObject&LanguageResolverInterface */
    private MockObject $resolver;

    /** @var MockObject&LanguageSwitcherControllerInterface */
    private MockObject $switcher;

    /** @var MockObject&RendererInterface */
    private MockObject $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $this->model    = $this->createMock(PostModelInterface::class);
        $this->resolver = $this->createMock(LanguageResolverInterface::class);
        $this->switcher = $this->createMock(LanguageSwitcherControllerInterface::class);
        $this->renderer = $this->createMock(RendererInterface::class);

        $this->resolver->method('current')->willReturn($this->french);
        $this->switcher->method('build')->willReturn(
            new LanguageSwitcherViewModel(current: $this->french, items: []),
        );
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRenderSingleUsesEntity(): void
    {
        $entity = $this->buildEntity(11, 'post', 'Hello');

        Functions\when('get_queried_object_id')->justReturn(11);
        $this->model->method('find')->with(11)->willReturn($entity);

        $this->renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/single-post.html',
                self::callback(
                    static fn (array $vm): bool => $vm['post'] instanceof PostEntity && $vm['post']->id === 11,
                ),
            )
            ->willReturn('<html>single</html>');

        $controller = new PostController($this->model, $this->resolver, $this->switcher, $this->renderer);

        self::assertSame('<html>single</html>', $controller->renderSingle());
    }

    public function testRenderArchiveListsEntities(): void
    {
        $first  = $this->buildEntity(1, 'post', 'A');
        $second = $this->buildEntity(2, 'post', 'B');

        $this->model
            ->method('findByLanguage')
            ->with($this->french, 10)
            ->willReturn([$first, $second]);

        $this->renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/archive-post.html',
                self::callback(static function (array $vm): bool {
                    if (! \is_array($vm['posts'])) {
                        return false;
                    }

                    return \count($vm['posts']) === 2 && $vm['posts'][0]->id === 1;
                }),
            )
            ->willReturn('<html>archive</html>');

        $controller = new PostController($this->model, $this->resolver, $this->switcher, $this->renderer);

        self::assertSame('<html>archive</html>', $controller->renderArchive());
    }

    public function testRenderSearchExposesQuery(): void
    {
        Functions\when('get_search_query')->justReturn('yoga');
        $this->model
            ->method('findByLanguage')
            ->willReturn([]);

        $this->renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/search.html',
                self::callback(
                    static fn (array $vm): bool => $vm['query'] === 'yoga' && $vm['posts'] === [],
                ),
            )
            ->willReturn('<html>search</html>');

        $controller = new PostController($this->model, $this->resolver, $this->switcher, $this->renderer);

        self::assertSame('<html>search</html>', $controller->renderSearch());
    }

    private function buildEntity(int $id, string $type, string $title): PostEntity
    {
        return new PostEntity(
            id: $id,
            type: $type,
            title: $title,
            content: '<p>x</p>',
            excerpt: null,
            slug: strtolower($title),
            language: $this->french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/' . $id,
            publishedAt: new DateTimeImmutable('2026-01-01'),
            updatedAt: null,
            author: null,
        );
    }
}
