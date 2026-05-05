<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Slides;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Slides\SlideCpt;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de SlideCpt (CPT oli_slide).
 *
 * @package OliTheme\Tests\Unit\Slides
 *
 * @since 1.0.0
 */
final class SlideCptTest extends TestCase
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

        (new SlideCpt())->register();

        self::assertSame('oli_slide', $capturedSlug);
        self::assertIsArray($capturedArgs);
        self::assertFalse($capturedArgs['public']);
        self::assertSame(['title', 'thumbnail', 'excerpt', 'page-attributes'], $capturedArgs['supports']);
        self::assertSame(['language'], $capturedArgs['taxonomies']);
        self::assertSame('Slides', $capturedArgs['labels']['name']);
    }

    public function testSlugReturnsOliSlide(): void
    {
        self::assertSame('oli_slide', (new SlideCpt())->slug());
    }
}
