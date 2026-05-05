<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Events;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Events\EventCpt;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de EventCpt (CPT oli_event).
 *
 * @package OliTheme\Tests\Unit\Events
 *
 * @since 1.0.0
 */
final class EventCptTest extends TestCase
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

    public function testRegisterCallsRegisterPostTypeWithExpectedArgs(): void
    {
        $capturedSlug = null;
        $capturedArgs = null;

        Functions\when('__')->returnArg(1);
        Functions\when('register_post_type')->alias(
            static function (string $slug, array $args) use (&$capturedSlug, &$capturedArgs): void {
                $capturedSlug = $slug;
                $capturedArgs = $args;
            },
        );

        (new EventCpt())->register();

        self::assertSame('oli_event', $capturedSlug);
        self::assertIsArray($capturedArgs);
        self::assertTrue($capturedArgs['has_archive']);
        self::assertSame('evenements', $capturedArgs['rewrite']['slug']);
        self::assertSame(['language'], $capturedArgs['taxonomies']);
    }

    public function testSlugReturnsOliEvent(): void
    {
        self::assertSame('oli_event', (new EventCpt())->slug());
    }
}
