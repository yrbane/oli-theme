<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModel;
use PHPUnit\Framework\TestCase;
use stdClass;

final class PostModelTest extends TestCase
{
    private Language $french;
    private LanguageResolverInterface $resolver;
    private LanguageRegistryInterface $registry;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $this->resolver = $this->createMock(LanguageResolverInterface::class);
        $this->registry = $this->createMock(LanguageRegistryInterface::class);

        $this->resolver->method('current')->willReturn($this->french);
        $this->registry->method('default')->willReturn($this->french);
        $this->registry->method('get')->willReturn($this->french);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFindReturnsNullWhenPostMissing(): void
    {
        Functions\when('get_post')->justReturn(null);

        $model = new PostModel($this->resolver, $this->registry);

        self::assertNull($model->find(123));
    }

    public function testFindBuildsEntityFromWpPost(): void
    {
        $post = $this->buildWpPost(
            id: 42,
            type: 'post',
            title: 'Hello',
            content: '<p>Body</p>',
            excerpt: 'Short',
            slug: 'hello',
            date: '2026-05-05 10:00:00',
            modified: '2026-05-06 12:00:00',
            author: 7,
        );

        Functions\when('get_post')->justReturn($post);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_permalink')->justReturn('https://example.com/fr/hello');
        Functions\when('get_the_post_thumbnail_url')->justReturn('https://cdn/img.jpg');
        Functions\when('get_post_thumbnail_id')->justReturn(99);
        Functions\when('get_post_meta')->justReturn('Une image');
        Functions\when('get_the_author_meta')->justReturn('Olivier');
        Functions\when('mysql2date')->returnArg(2);
        Functions\when('wp_get_object_terms')->justReturn([(object) ['slug' => 'fr']]);

        $model = new PostModel($this->resolver, $this->registry);
        $entity = $model->find(42);

        self::assertInstanceOf(PostEntity::class, $entity);
        self::assertSame(42, $entity->id);
        self::assertSame('post', $entity->type);
        self::assertSame('Hello', $entity->title);
        self::assertSame('<p>Body</p>', $entity->content);
        self::assertSame('Short', $entity->excerpt);
        self::assertSame('hello', $entity->slug);
        self::assertSame('https://example.com/fr/hello', $entity->permalink);
        self::assertSame('https://cdn/img.jpg', $entity->featuredImageUrl);
        self::assertSame('Une image', $entity->featuredImageAlt);
        self::assertSame('Olivier', $entity->author);
    }

    public function testFindByLanguageReturnsArrayOfEntities(): void
    {
        $first = $this->buildWpPost(1, 'post', 'A', 'A', 'a', 'a', '2026-01-01', null, 1);
        $second = $this->buildWpPost(2, 'post', 'B', 'B', 'b', 'b', '2026-01-02', null, 1);

        Functions\when('get_posts')->justReturn([$first, $second]);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_permalink')->justReturn('https://example.com/');
        Functions\when('get_the_post_thumbnail_url')->justReturn(false);
        Functions\when('get_post_thumbnail_id')->justReturn(0);
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_the_author_meta')->justReturn('Author');
        Functions\when('mysql2date')->returnArg(2);
        Functions\when('wp_get_object_terms')->justReturn([(object) ['slug' => 'fr']]);

        $model = new PostModel($this->resolver, $this->registry);
        $items = $model->findByLanguage($this->french, 5);

        self::assertCount(2, $items);
        self::assertContainsOnlyInstancesOf(PostEntity::class, $items);
    }

    public function testGetMetaReturnsDefaultWhenAbsent(): void
    {
        Functions\when('get_post_meta')->justReturn('');

        $model = new PostModel($this->resolver, $this->registry);

        self::assertSame('default', $model->getMeta(1, '_oli_seo_title', 'default'));
    }

    public function testGetMetaReturnsExistingValue(): void
    {
        Functions\when('get_post_meta')->justReturn('Custom title');

        $model = new PostModel($this->resolver, $this->registry);

        self::assertSame('Custom title', $model->getMeta(1, '_oli_seo_title', 'fallback'));
    }

    private function buildWpPost(
        int $id,
        string $type,
        string $title,
        string $content,
        string $excerpt,
        string $slug,
        string $date,
        ?string $modified,
        int $author,
    ): stdClass {
        $post = new stdClass();
        $post->ID = $id;
        $post->post_type = $type;
        $post->post_title = $title;
        $post->post_content = $content;
        $post->post_excerpt = $excerpt;
        $post->post_name = $slug;
        $post->post_date_gmt = $date;
        $post->post_modified_gmt = $modified ?? '';
        $post->post_author = $author;
        $post->post_status = 'publish';

        return $post;
    }
}
