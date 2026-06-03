<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gabarits;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Gabarits\ZoneContent;
use OliTheme\Gabarits\ZoneContentRepository;
use OliTheme\Gabarits\ZoneType;
use PHPUnit\Framework\TestCase;

final class ZoneContentRepositoryTest extends TestCase
{
    /** @var array<int, string> */
    private array $store = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->store = [];
        Functions\when('get_post_meta')->alias(fn (int $id, string $key, bool $s = true) =>
            $this->store[$id] ?? ''
        );
        Functions\when('update_post_meta')->alias(function (int $id, string $key, mixed $value) {
            $this->store[$id] = (string) $value;
            return true;
        });
        Functions\when('delete_post_meta')->alias(function (int $id, string $key) {
            unset($this->store[$id]);
            return true;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_load_returns_empty_when_no_meta(): void
    {
        self::assertSame([], (new ZoneContentRepository())->load(42));
    }

    public function test_save_then_load_roundtrip(): void
    {
        $repo = new ZoneContentRepository();
        $contents = [
            'hero'    => new ZoneContent(ZoneType::Image, imageId: 42),
            'intro'   => new ZoneContent(ZoneType::Text, text: '<p>Bienvenue</p>'),
            'gallery' => new ZoneContent(ZoneType::Gallery, imageIds: [1, 2, 3]),
        ];
        $repo->save(7, $contents);
        $loaded = $repo->load(7);
        self::assertCount(3, $loaded);
        self::assertSame(42, $loaded['hero']->imageId);
        self::assertSame('<p>Bienvenue</p>', $loaded['intro']->text);
        self::assertSame([1, 2, 3], $loaded['gallery']->imageIds);
    }

    public function test_save_strips_empty_zones(): void
    {
        $repo = new ZoneContentRepository();
        $repo->save(5, [
            'a' => new ZoneContent(ZoneType::Text, text: 'OK'),
            'b' => new ZoneContent(ZoneType::Text, text: '   '),
            'c' => new ZoneContent(ZoneType::Image, imageId: 0),
        ]);
        $loaded = $repo->load(5);
        self::assertCount(1, $loaded);
        self::assertArrayHasKey('a', $loaded);
    }

    public function test_save_all_empty_deletes_meta(): void
    {
        $repo = new ZoneContentRepository();
        $repo->save(5, ['a' => new ZoneContent(ZoneType::Text, text: 'X')]);
        self::assertNotEmpty($this->store[5] ?? '');

        $repo->save(5, ['a' => new ZoneContent(ZoneType::Text, text: '')]);
        self::assertSame([], $this->store);
    }

    public function test_load_ignores_corrupted_json(): void
    {
        $this->store[5] = 'not json';
        self::assertSame([], (new ZoneContentRepository())->load(5));
    }

    public function test_load_ignores_invalid_zones(): void
    {
        $this->store[5] = (string) json_encode([
            'good' => ['type' => 'text', 'text' => 'OK'],
            'bad'  => ['type' => 'foo'],
            'gone' => 'string-not-array',
        ]);
        $loaded = (new ZoneContentRepository())->load(5);
        self::assertCount(1, $loaded);
        self::assertArrayHasKey('good', $loaded);
    }
}
