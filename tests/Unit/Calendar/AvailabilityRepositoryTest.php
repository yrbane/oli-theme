<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Calendar\Availability;
use OliTheme\Calendar\AvailabilityRepository;
use OliTheme\Calendar\Cpt\AvailabilityCpt;
use PHPUnit\Framework\TestCase;

final class AvailabilityRepositoryTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $metaStore = [];
    private int $nextId = 1;
    /** @var array<int, object> */
    private array $posts = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->metaStore = [];
        $this->posts     = [];
        $this->nextId    = 1;

        Functions\when('wp_insert_post')->alias(function (array $args): int {
            $id = $this->nextId++;
            $post = new \stdClass();
            $post->ID         = $id;
            $post->post_title = (string) ($args['post_title'] ?? '');
            $post->post_type  = (string) ($args['post_type'] ?? '');
            $this->posts[$id] = $post;
            return $id;
        });
        Functions\when('wp_update_post')->alias(function (array $args): int {
            $id = (int) ($args['ID'] ?? 0);
            if (isset($this->posts[$id])) {
                $this->posts[$id]->post_title = (string) ($args['post_title'] ?? $this->posts[$id]->post_title);
            }
            return $id;
        });
        Functions\when('wp_delete_post')->alias(function (int $id): bool {
            if (isset($this->posts[$id])) {
                unset($this->posts[$id], $this->metaStore[$id]);
                return true;
            }
            return false;
        });
        Functions\when('update_post_meta')->alias(function (int $id, string $key, mixed $value): bool {
            $this->metaStore[$id][$key] = $value;
            return true;
        });
        Functions\when('get_post_meta')->alias(fn (int $id, string $key, bool $single = true) => $this->metaStore[$id][$key] ?? '');
        Functions\when('get_post')->alias(fn (int $id) => $this->posts[$id] ?? null);
        Functions\when('get_posts')->alias(function (array $args) {
            $type = $args['post_type'] ?? '';
            if ($type !== AvailabilityCpt::POST_TYPE) {
                return [];
            }
            $constraints = $args['meta_query'] ?? [];
            $matching = [];
            foreach ($this->posts as $id => $post) {
                if (($post->post_type ?? '') !== $type) {
                    continue;
                }
                if ($this->satisfiesMetaQuery($id, $constraints)) {
                    $matching[] = $post;
                }
            }
            return $matching;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * @param array<mixed> $constraints
     */
    private function satisfiesMetaQuery(int $id, array $constraints): bool
    {
        if (empty($constraints)) {
            return true;
        }
        foreach ($constraints as $key => $rule) {
            if ($key === 'relation') {
                continue;
            }
            $metaValue = (int) ($this->metaStore[$id][$rule['key']] ?? 0);
            $cmp       = (int) $rule['value'];
            switch ($rule['compare']) {
                case '<':  if (!($metaValue < $cmp))  return false; break;
                case '>':  if (!($metaValue > $cmp))  return false; break;
                case '<=': if (!($metaValue <= $cmp)) return false; break;
                case '>=': if (!($metaValue >= $cmp)) return false; break;
            }
        }
        return true;
    }

    public function test_save_returns_id_and_persists_meta(): void
    {
        $repo = new AvailabilityRepository();
        $availability = new Availability(
            null,
            new DateTimeImmutable('2026-06-03 10:00'),
            new DateTimeImmutable('2026-06-03 12:00'),
            type: Availability::TYPE_BLOCKED,
        );
        $id = $repo->save($availability);
        self::assertGreaterThan(0, $id);
        self::assertSame('blocked', $this->metaStore[$id]['_oli_avail_type']);
    }

    public function test_find_in_range_returns_overlapping_only(): void
    {
        $repo = new AvailabilityRepository();
        $repo->save(new Availability(null, new DateTimeImmutable('2026-06-03 09:00'), new DateTimeImmutable('2026-06-03 10:00')));
        $repo->save(new Availability(null, new DateTimeImmutable('2026-06-03 14:00'), new DateTimeImmutable('2026-06-03 16:00')));
        $repo->save(new Availability(null, new DateTimeImmutable('2026-06-04 09:00'), new DateTimeImmutable('2026-06-04 10:00')));

        $results = $repo->findInRange(
            new DateTimeImmutable('2026-06-03 12:00'),
            new DateTimeImmutable('2026-06-03 18:00'),
        );
        self::assertCount(1, $results);
        self::assertSame('2026-06-03 14:00:00', $results[0]->start->format('Y-m-d H:i:s'));
    }

    public function test_find_returns_null_when_unknown(): void
    {
        self::assertNull((new AvailabilityRepository())->find(999));
    }

    public function test_delete_removes_post(): void
    {
        $repo = new AvailabilityRepository();
        $id = $repo->save(new Availability(null, new DateTimeImmutable('2026-06-03 09:00'), new DateTimeImmutable('2026-06-03 10:00')));
        self::assertTrue($repo->delete($id));
        self::assertNull($repo->find($id));
    }
}
