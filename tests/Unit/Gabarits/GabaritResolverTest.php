<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gabarits;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Gabarits\Gabarit;
use OliTheme\Gabarits\GabaritRegistryInterface;
use OliTheme\Gabarits\GabaritResolver;
use PHPUnit\Framework\TestCase;

final class GabaritResolverTest extends TestCase
{
    /** @var array<int, string> */
    private array $metas = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('get_post_meta')->alias(fn (int $id, string $key, bool $single = true) =>
            $this->metas[$id] ?? ''
        );
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_returns_null_when_post_id_zero(): void
    {
        $reg = $this->createMock(GabaritRegistryInterface::class);
        self::assertNull((new GabaritResolver($reg))->forPost(0));
    }

    public function test_returns_null_when_no_meta(): void
    {
        $reg = $this->createMock(GabaritRegistryInterface::class);
        $this->metas[42] = '';
        self::assertNull((new GabaritResolver($reg))->forPost(42));
    }

    public function test_returns_gabarit_when_meta_matches(): void
    {
        $expected = new Gabarit('magazine', 'Magazine', '', ['post'], '/style.css');
        $reg = $this->createMock(GabaritRegistryInterface::class);
        $reg->method('byId')->with('magazine')->willReturn($expected);
        $this->metas[42] = 'magazine';
        self::assertSame($expected, (new GabaritResolver($reg))->forPost(42));
    }

    public function test_returns_null_when_meta_unknown_to_registry(): void
    {
        $reg = $this->createMock(GabaritRegistryInterface::class);
        $reg->method('byId')->willReturn(null);
        $this->metas[42] = 'inexistant';
        self::assertNull((new GabaritResolver($reg))->forPost(42));
    }
}
