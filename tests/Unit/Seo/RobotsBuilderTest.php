<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use OliTheme\Seo\RobotsBuilder;
use OliTheme\Seo\SeoMeta;
use PHPUnit\Framework\TestCase;

/**
 * Tests du RobotsBuilder.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class RobotsBuilderTest extends TestCase
{
    public function testIndexFollow(): void
    {
        $meta = new SeoMeta(noindex: false, nofollow: false);
        $result = (new RobotsBuilder())->build($meta);

        self::assertSame('index, follow', $result);
    }

    public function testNoindexFollow(): void
    {
        $meta = new SeoMeta(noindex: true, nofollow: false);
        $result = (new RobotsBuilder())->build($meta);

        self::assertSame('noindex, follow', $result);
    }

    public function testIndexNofollow(): void
    {
        $meta = new SeoMeta(noindex: false, nofollow: true);
        $result = (new RobotsBuilder())->build($meta);

        self::assertSame('index, nofollow', $result);
    }

    public function testNoindexNofollow(): void
    {
        $meta = new SeoMeta(noindex: true, nofollow: true);
        $result = (new RobotsBuilder())->build($meta);

        self::assertSame('noindex, nofollow', $result);
    }
}
