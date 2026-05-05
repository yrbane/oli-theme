<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use OliTheme\Seo\BreadcrumbItemEntity;
use PHPUnit\Framework\TestCase;

/**
 * Tests de BreadcrumbItemEntity.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class BreadcrumbItemEntityTest extends TestCase
{
    public function testExposesAllProperties(): void
    {
        $item = new BreadcrumbItemEntity(
            label: 'Accueil',
            url: 'https://example.com/',
            isCurrent: true,
        );

        self::assertSame('Accueil', $item->label);
        self::assertSame('https://example.com/', $item->url);
        self::assertTrue($item->isCurrent);
    }
}
