<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Navigation;

use OliTheme\Navigation\MenuItemEntity;
use PHPUnit\Framework\TestCase;

final class MenuItemEntityTest extends TestCase
{
    public function testItExposesAllProperties(): void
    {
        $child = new MenuItemEntity(
            id: 11,
            label: 'Sous-page',
            url: 'https://example.com/sous-page',
            target: '',
            isCurrent: false,
            isAncestor: false,
            depth: 1,
            children: [],
        );

        $entity = new MenuItemEntity(
            id: 1,
            label: 'Accueil',
            url: 'https://example.com/',
            target: '_self',
            isCurrent: true,
            isAncestor: false,
            depth: 0,
            children: [$child],
        );

        self::assertSame(1, $entity->id);
        self::assertSame('Accueil', $entity->label);
        self::assertSame('https://example.com/', $entity->url);
        self::assertSame('_self', $entity->target);
        self::assertTrue($entity->isCurrent);
        self::assertFalse($entity->isAncestor);
        self::assertSame(0, $entity->depth);
        self::assertCount(1, $entity->children);
        self::assertSame($child, $entity->children[0]);
    }
}
