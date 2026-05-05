<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use DateTimeImmutable;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModelInterface;
use OliTheme\Seo\InternalLinkSuggester;
use PHPUnit\Framework\TestCase;

/**
 * Tests de InternalLinkSuggester.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class InternalLinkSuggesterTest extends TestCase
{
    private Language $lang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');
    }

    public function testSuggestForExcludesCurrentAndAlreadyLinked(): void
    {
        $current = $this->makePost(1, 'https://example.com/article-3-deja-lie', 'Contenu avec lien https://example.com/article-3-deja-lie ici.');
        $posts = [
            $current,                                                          // exclu : c'est le courant
            $this->makePost(2, 'https://example.com/article-2', 'contenu 2'), // suggéré
            $this->makePost(3, 'https://example.com/article-3-deja-lie', ''), // exclu : déjà lié
            $this->makePost(4, 'https://example.com/article-4', 'contenu 4'), // suggéré
        ];

        $model = $this->createMock(PostModelInterface::class);
        $model->method('findByLanguage')->willReturn($posts);

        $suggester = new InternalLinkSuggester($model);
        $result = $suggester->suggestFor($current);

        self::assertCount(2, $result);
        self::assertSame(2, $result[0]->id);
        self::assertSame(4, $result[1]->id);
    }

    public function testSuggestForRespectsLimit(): void
    {
        $current = $this->makePost(1, 'https://example.com/current', 'contenu');
        $posts = [];
        for ($i = 2; $i <= 11; $i++) {
            $posts[] = $this->makePost($i, "https://example.com/article-$i", "contenu $i");
        }

        $model = $this->createMock(PostModelInterface::class);
        $model->method('findByLanguage')->willReturn($posts);

        $suggester = new InternalLinkSuggester($model);
        $result = $suggester->suggestFor($current, 3);

        self::assertCount(3, $result);
    }

    private function makePost(int $id, string $permalink, string $content): PostEntity
    {
        return new PostEntity(
            id: $id,
            type: 'post',
            title: "Article $id",
            content: $content,
            excerpt: null,
            slug: "article-$id",
            language: $this->lang,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: $permalink,
            publishedAt: new DateTimeImmutable('2024-01-01'),
            updatedAt: null,
            author: null,
        );
    }
}
