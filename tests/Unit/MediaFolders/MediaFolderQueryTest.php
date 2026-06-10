<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MediaFolders;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\MediaFolders\MediaFolderQuery;
use OliTheme\MediaFolders\MediaFoldersTaxonomy;
use PHPUnit\Framework\TestCase;

final class MediaFolderQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('taxonomy_exists')->justReturn(true);
        Functions\when('wp_get_attachment_image_url')->alias(fn (int $id, string $size = 'large') => "https://oli.test/uploads/{$id}-{$size}.jpg");
        Functions\when('wp_get_attachment_image_srcset')->alias(fn (int $id) => "https://oli.test/uploads/{$id}-300.jpg 300w, https://oli.test/uploads/{$id}-800.jpg 800w");
        Functions\when('get_post_meta')->alias(fn (int $id) => $id === 42 ? 'Alt fourni' : '');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_all_folders_returns_terms(): void
    {
        Functions\when('get_terms')->justReturn([
            $this->makeTerm('stages-2026', 'Stages 2026', 0, 1, 0),
            $this->makeTerm('ete', 'Été', 1, 2, 0),
        ]);
        // Recompte par slug : 12 pour stages-2026, 3 pour ete.
        Functions\when('get_posts')->alias(static function (array $args): array {
            if (($args['fields'] ?? null) !== 'ids') {
                return [];
            }
            $slug = (string) ($args['tax_query'][0]['terms'] ?? '');

            return match ($slug) {
                'stages-2026' => range(1, 12),
                'ete'         => [101, 102, 103],
                default       => [],
            };
        });

        $folders = (new MediaFolderQuery())->allFolders();
        self::assertCount(2, $folders);
        self::assertSame('stages-2026', $folders[0]['slug']);
        self::assertSame(0, $folders[0]['parent']);
        self::assertSame(1, $folders[1]['parent']);
        self::assertSame(12, $folders[0]['count']);
        self::assertSame(3, $folders[1]['count']);
    }

    /**
     * Le champ `count` natif de WP_Term n'est pas fiable pour les attachments :
     * `_update_post_term_count` ne compte que les posts `post_status = publish`,
     * or les attachments sont en `inherit`. Notre allFolders() doit recompter.
     */
    public function test_all_folders_recomputes_count_from_attachments(): void
    {
        Functions\when('get_terms')->justReturn([
            $this->makeTerm('voyage', 'Voyage', 0, 1, 0),
        ]);
        Functions\when('get_posts')->alias(static function (array $args): array {
            return ($args['fields'] ?? null) === 'ids' ? [101, 102, 103] : [];
        });

        $folders = (new MediaFolderQuery())->allFolders();

        self::assertCount(1, $folders);
        self::assertSame(3, $folders[0]['count']);
    }

    /**
     * Construit un \WP_Term minimal pour les stubs `get_terms` (les tests
     * d'origine utilisaient stdClass, désormais incompatible avec le filtre
     * `instanceof WP_Term` introduit pour satisfaire PHPStan).
     */
    private function makeTerm(string $slug, string $name, int $parent, int $termId, int $count): \WP_Term
    {
        $term = new \WP_Term((object) [
            'term_id'  => $termId,
            'slug'     => $slug,
            'name'     => $name,
            'parent'   => $parent,
            'count'    => $count,
            'taxonomy' => 'oli_media_folder',
        ]);

        return $term;
    }

    public function test_all_folders_empty_when_taxonomy_missing(): void
    {
        Functions\when('taxonomy_exists')->justReturn(false);
        self::assertSame([], (new MediaFolderQuery())->allFolders());
    }

    public function test_photos_in_folder_returns_hydrated_list(): void
    {
        $post42 = (object) ['ID' => 42, 'post_title' => 'Photo A', 'post_excerpt' => 'Caption A'];
        $post43 = (object) ['ID' => 43, 'post_title' => 'Photo B', 'post_excerpt' => ''];
        Functions\when('get_posts')->alias(function (array $args) use ($post42, $post43) {
            self::assertSame('attachment', $args['post_type']);
            self::assertSame('image', $args['post_mime_type']);
            self::assertSame('stages-2026', $args['tax_query'][0]['terms']);
            self::assertTrue($args['tax_query'][0]['include_children']);
            return [$post42, $post43];
        });

        $photos = (new MediaFolderQuery())->photosInFolder('stages-2026');
        self::assertCount(2, $photos);
        self::assertSame(42, $photos[0]['id']);
        self::assertSame('Photo A', $photos[0]['title']);
        self::assertSame('Caption A', $photos[0]['caption']);
        self::assertSame('Alt fourni', $photos[0]['alt']);
        self::assertStringContainsString('300w', $photos[0]['srcset']);
        self::assertSame('', $photos[1]['alt']);
    }

    public function test_photos_in_folder_can_disable_children(): void
    {
        Functions\when('get_posts')->alias(function (array $args) {
            self::assertFalse($args['tax_query'][0]['include_children']);
            return [];
        });
        (new MediaFolderQuery())->photosInFolder('parent', includeChildren: false);
    }

    public function test_photos_in_folder_empty_when_no_slug(): void
    {
        $count = 0;
        Functions\when('get_posts')->alias(function () use (&$count) {
            $count++;
            return [];
        });
        self::assertSame([], (new MediaFolderQuery())->photosInFolder(''));
        self::assertSame(0, $count, 'get_posts ne devrait pas être appelée pour un slug vide');
    }

    public function test_photos_in_folder_skips_invalid_attachments(): void
    {
        Functions\when('get_posts')->justReturn([
            (object) ['ID' => 0, 'post_title' => 'broken'],
            (object) ['ID' => 99, 'post_title' => 'ok', 'post_excerpt' => ''],
        ]);
        $photos = (new MediaFolderQuery())->photosInFolder('x');
        self::assertCount(1, $photos);
        self::assertSame(99, $photos[0]['id']);
    }
}
