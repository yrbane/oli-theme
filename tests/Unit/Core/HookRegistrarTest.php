<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\HookRegistrar;
use PHPUnit\Framework\TestCase;

/**
 * Tests du wrapper testable autour de add_action / add_filter.
 */
final class HookRegistrarTest extends TestCase
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

    public function test_it_should_register_an_action_with_default_priority(): void
    {
        $callback = static function (): void {
        };

        Functions\expect('add_action')
            ->once()
            ->with('init', $callback, 10, 1);

        $registrar = new HookRegistrar();
        $registrar->action('init', $callback);

        $this->addToAssertionCount(1);
    }

    public function test_it_should_register_a_filter_with_custom_priority_and_args(): void
    {
        $callback = static fn (string $value): string => $value;

        Functions\expect('add_filter')
            ->once()
            ->with('the_title', $callback, 5, 2);

        $registrar = new HookRegistrar();
        $registrar->filter('the_title', $callback, priority: 5, acceptedArgs: 2);

        $this->addToAssertionCount(1);
    }

    public function test_it_should_track_registered_hooks_for_introspection(): void
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);

        $registrar = new HookRegistrar();
        $registrar->action('init', static fn () => null);
        $registrar->filter('the_content', static fn ($v) => $v, 20);

        $registered = $registrar->registered();
        self::assertCount(2, $registered);
        self::assertSame('action', $registered[0]['type']);
        self::assertSame('init', $registered[0]['hook']);
        self::assertSame('filter', $registered[1]['type']);
        self::assertSame('the_content', $registered[1]['hook']);
        self::assertSame(20, $registered[1]['priority']);
    }
}
