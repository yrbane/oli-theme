<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Slides;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\Slides\SlideEntity;
use OliTheme\Slides\SlideModel;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests unitaires de SlideModel (findActive + findById).
 *
 * @package OliTheme\Tests\Unit\Slides
 *
 * @since 1.0.0
 */
final class SlideModelTest extends TestCase
{
    private Language $french;
    private LanguageRegistryInterface $registry;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $this->registry = $this->createMock(LanguageRegistryInterface::class);
        $this->registry->method('default')->willReturn($this->french);
        $this->registry->method('get')->willReturn($this->french);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFindActiveReturnsEmptyArrayWhenNothing(): void
    {
        Functions\when('get_posts')->justReturn([]);

        $model = new SlideModel($this->registry);

        self::assertSame([], $model->findActive($this->french));
    }

    public function testFindActiveBuildsEntities(): void
    {
        $post1 = $this->buildPost(id: 10, title: 'Slide A', excerpt: 'Légende A', order: 1);
        $post2 = $this->buildPost(id: 11, title: 'Slide B', excerpt: '', order: 2);

        Functions\when('get_posts')->justReturn([$post1, $post2]);
        Functions\when('get_the_post_thumbnail_url')->justReturn('https://cdn/slide.jpg');
        Functions\when('get_post_thumbnail_id')->justReturn(99);
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('wp_get_object_terms')->justReturn([(object) ['slug' => 'fr']]);

        $model = new SlideModel($this->registry);
        $results = $model->findActive($this->french);

        self::assertCount(2, $results);
        self::assertContainsOnlyInstancesOf(SlideEntity::class, $results);
        self::assertSame(10, $results[0]->id);
        self::assertSame('Slide A', $results[0]->title);
        self::assertSame('Légende A', $results[0]->caption);
        self::assertNull($results[1]->caption);
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        Functions\when('get_post')->justReturn(null);

        $model = new SlideModel($this->registry);

        self::assertNull($model->findById(999));
    }

    public function testFindByIdHydratesEntity(): void
    {
        $post = $this->buildPost(id: 42, title: 'Hero slide', excerpt: 'Caption', order: 0);
        $post->post_type = 'oli_slide';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_the_post_thumbnail_url')->justReturn('https://cdn/hero.jpg');
        Functions\when('get_post_thumbnail_id')->justReturn(55);
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('wp_get_object_terms')->justReturn([(object) ['slug' => 'fr']]);

        $model = new SlideModel($this->registry);
        $entity = $model->findById(42);

        self::assertInstanceOf(SlideEntity::class, $entity);
        self::assertSame(42, $entity->id);
        self::assertSame('Hero slide', $entity->title);
        self::assertSame($this->french, $entity->language);
    }

    private function buildPost(int $id, string $title, string $excerpt, int $order): stdClass
    {
        $post = new stdClass();
        $post->ID = $id;
        $post->post_title = $title;
        $post->post_excerpt = $excerpt;
        $post->post_type = 'oli_slide';
        $post->menu_order = $order;

        return $post;
    }
}
