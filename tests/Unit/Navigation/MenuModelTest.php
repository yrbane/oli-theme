<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Navigation;

use OliTheme\Navigation\MenuItemEntity;
use OliTheme\Navigation\MenuModel;
use PHPUnit\Framework\TestCase;
use stdClass;

final class MenuModelTest extends TestCase
{
    public function testToTreeBuildsSingleLevelArray(): void
    {
        $items = [
            $this->buildWpItem(1, 0, 'Accueil', 'https://example.com/', 100),
            $this->buildWpItem(2, 0, 'Contact', 'https://example.com/contact', 200),
        ];

        $tree = (new MenuModel())->toTree($items, currentObjectId: 0);

        self::assertCount(2, $tree);
        self::assertContainsOnlyInstancesOf(MenuItemEntity::class, $tree);
        self::assertSame('Accueil', $tree[0]->label);
        self::assertSame(0, $tree[0]->depth);
        self::assertCount(0, $tree[0]->children);
    }

    public function testToTreeNestsChildrenUnderParents(): void
    {
        $items = [
            $this->buildWpItem(1, 0, 'Cours', 'https://example.com/cours', 10),
            $this->buildWpItem(2, 1, 'Hebdo', 'https://example.com/cours/hebdo', 11),
            $this->buildWpItem(3, 1, 'Stage', 'https://example.com/cours/stage', 12),
            $this->buildWpItem(4, 0, 'Contact', 'https://example.com/contact', 20),
        ];

        $tree = (new MenuModel())->toTree($items, currentObjectId: 0);

        self::assertCount(2, $tree);
        self::assertSame('Cours', $tree[0]->label);
        self::assertCount(2, $tree[0]->children);
        self::assertSame('Hebdo', $tree[0]->children[0]->label);
        self::assertSame(1, $tree[0]->children[0]->depth);
    }

    public function testCurrentAndAncestorAreResolved(): void
    {
        $items = [
            $this->buildWpItem(1, 0, 'Cours', 'https://example.com/cours', 10),
            $this->buildWpItem(2, 1, 'Hebdo', 'https://example.com/cours/hebdo', 11),
        ];

        $tree = (new MenuModel())->toTree($items, currentObjectId: 11);

        self::assertFalse($tree[0]->isCurrent);
        self::assertTrue($tree[0]->isAncestor);
        self::assertTrue($tree[0]->children[0]->isCurrent);
        self::assertFalse($tree[0]->children[0]->isAncestor);
    }

    private function buildWpItem(int $id, int $parent, string $title, string $url, int $objectId): stdClass
    {
        $item = new stdClass();
        $item->ID = $id;
        $item->menu_item_parent = (string) $parent;
        $item->title = $title;
        $item->url = $url;
        $item->target = '';
        $item->object_id = (string) $objectId;

        return $item;
    }
}
