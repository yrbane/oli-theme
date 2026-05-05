<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use DateTimeImmutable;
use OliTheme\Seo\RedirectEntity;
use PHPUnit\Framework\TestCase;

/**
 * Tests de RedirectEntity.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class RedirectEntityTest extends TestCase
{
    public function testExposesAllProperties(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-15 10:00:00');

        $entity = new RedirectEntity(
            id: 7,
            source: '/ancienne-page',
            target: 'https://example.com/nouvelle-page',
            code: 301,
            hits: 42,
            createdAt: $createdAt,
        );

        self::assertSame(7, $entity->id);
        self::assertSame('/ancienne-page', $entity->source);
        self::assertSame('https://example.com/nouvelle-page', $entity->target);
        self::assertSame(301, $entity->code);
        self::assertSame(42, $entity->hits);
        self::assertSame($createdAt, $entity->createdAt);
    }
}
