<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Seo\CanonicalBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests du CanonicalBuilder.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class CanonicalBuilderTest extends TestCase
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

    public function testReturnsOverrideWhenProvided(): void
    {
        $builder = new CanonicalBuilder();
        $result = $builder->build(1, 'https://example.com/override');

        self::assertSame('https://example.com/override', $result);
    }

    public function testFallsBackToPermalinkWhenNoOverride(): void
    {
        Functions\when('get_permalink')->justReturn('https://example.com/page');

        $builder = new CanonicalBuilder();
        $result = $builder->build(1);

        self::assertSame('https://example.com/page', $result);
    }
}
