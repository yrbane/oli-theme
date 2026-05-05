<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Events;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Events\EventEntity;
use OliTheme\Events\EventModel;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests unitaires de EventModel (findUpcoming/findPast/findById/findBySlug).
 *
 * @package OliTheme\Tests\Unit\Events
 *
 * @since 1.0.0
 */
final class EventModelTest extends TestCase
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
        $this->registry->method('get')->with('fr')->willReturn($this->french);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFindUpcomingReturnsEmptyWhenNothing(): void
    {
        Functions\when('get_posts')->justReturn([]);

        $model = new EventModel($this->registry);
        $results = $model->findUpcoming($this->french);

        self::assertSame([], $results);
    }

    public function testFindUpcomingHydratesEntities(): void
    {
        $post1 = $this->buildPost(id: 10, title: 'Événement 1', slug: 'evenement-1');
        $post2 = $this->buildPost(id: 11, title: 'Événement 2', slug: 'evenement-2');

        Functions\when('get_posts')->justReturn([$post1, $post2]);
        Functions\when('get_post_meta')->alias(
            static function (int $id, string $key, bool $single) {
                if ($key === '_oli_event_start_date') {
                    return '2026-07-01 19:00:00';
                }
                if ($key === '_oli_event_end_date') {
                    return '2026-07-01 22:00:00';
                }

                return '';
            },
        );
        Functions\when('get_permalink')->justReturn('https://example.com/evenements/evenement');

        $model = new EventModel($this->registry);
        $results = $model->findUpcoming($this->french);

        self::assertCount(2, $results);
        self::assertInstanceOf(EventEntity::class, $results[0]);
        self::assertInstanceOf(EventEntity::class, $results[1]);
        self::assertSame('Événement 1', $results[0]->title);
        self::assertSame('Événement 2', $results[1]->title);
    }

    public function testFindPastFiltersByEndDate(): void
    {
        $post1 = $this->buildPost(id: 20, title: 'Vieux concert', slug: 'vieux-concert');

        Functions\when('get_posts')->justReturn([$post1]);
        Functions\when('get_post_meta')->alias(
            static function (int $id, string $key, bool $single) {
                if ($key === '_oli_event_start_date') {
                    return '2025-01-01 19:00:00';
                }
                if ($key === '_oli_event_end_date') {
                    return '2025-01-01 22:00:00';
                }

                return '';
            },
        );
        Functions\when('get_permalink')->justReturn('https://example.com/evenements/vieux-concert');

        $model = new EventModel($this->registry);
        $results = $model->findPast($this->french);

        self::assertCount(1, $results);
        self::assertInstanceOf(EventEntity::class, $results[0]);
        self::assertSame('Vieux concert', $results[0]->title);
        self::assertTrue($results[0]->isPast);
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        Functions\when('get_post')->justReturn(null);

        $model = new EventModel($this->registry);
        self::assertNull($model->findById(999));
    }

    public function testFindBySlugHydratesEntity(): void
    {
        $post = $this->buildPost(id: 30, title: 'Festival Été', slug: 'festival-ete');
        $post->post_type = 'oli_event';

        Functions\when('get_posts')->justReturn([$post]);
        Functions\when('get_post_meta')->alias(
            static function (int $id, string $key, bool $single) {
                if ($key === '_oli_event_start_date') {
                    return '2026-08-15 18:00:00';
                }

                return '';
            },
        );
        Functions\when('get_permalink')->justReturn('https://example.com/evenements/festival-ete');

        $model = new EventModel($this->registry);
        $entity = $model->findBySlug('festival-ete', $this->french);

        self::assertInstanceOf(EventEntity::class, $entity);
        self::assertSame(30, $entity->id);
        self::assertSame('Festival Été', $entity->title);
    }

    /**
     * Construit un objet stdClass simulant un WP_Post pour les événements.
     */
    private function buildPost(int $id, string $title, string $slug): stdClass
    {
        $post = new stdClass();
        $post->ID = $id;
        $post->post_title = $title;
        $post->post_name = $slug;
        $post->post_content = '<p>Contenu de test.</p>';
        $post->post_excerpt = '';
        $post->post_type = 'oli_event';
        $post->post_status = 'publish';

        return $post;
    }
}
