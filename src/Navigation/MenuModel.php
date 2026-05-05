<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

/**
 * Convertit la liste plate d'items WordPress en arbre d'entités de menu.
 *
 * Applique également la résolution des états `isCurrent` et `isAncestor`
 * en fonction de l'objet courant fourni par WordPress.
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
final class MenuModel implements MenuModelInterface
{
    /**
     * Construit l'arbre d'items à partir de la liste plate WP.
     *
     * @param array<int, object> $items Liste plate des items WP
     * @param int $currentObjectId Identifiant de l'objet courant
     *
     * @return MenuItemEntity[]
     */
    public function toTree(array $items, int $currentObjectId): array
    {
        if ($items === []) {
            return [];
        }

        /** @var array<int, list<object>> $byParent */
        $byParent = [];
        foreach ($items as $item) {
            $parentId = (int) ($item->menu_item_parent ?? 0);
            $byParent[$parentId] ??= [];
            $byParent[$parentId][] = $item;
        }

        return $this->buildBranch($byParent, 0, 0, $currentObjectId);
    }

    /**
     * Construit récursivement une branche de l'arbre.
     *
     * @param array<int, list<object>> $byParent Items indexés par ID parent
     * @param int $parentId Identifiant du parent courant
     * @param int $depth Niveau d'imbrication courant
     * @param int $currentObjectId Identifiant de l'objet courant
     *
     * @return MenuItemEntity[]
     */
    private function buildBranch(array $byParent, int $parentId, int $depth, int $currentObjectId): array
    {
        if (! isset($byParent[$parentId])) {
            return [];
        }

        $branch = [];
        foreach ($byParent[$parentId] as $item) {
            $children = $this->buildBranch($byParent, (int) ($item->ID ?? 0), $depth + 1, $currentObjectId);
            $branch[] = new MenuItemEntity(
                id: (int) ($item->ID ?? 0),
                label: (string) ($item->title ?? ''),
                url: (string) ($item->url ?? ''),
                target: (string) ($item->target ?? ''),
                isCurrent: $currentObjectId > 0 && (int) ($item->object_id ?? 0) === $currentObjectId,
                isAncestor: $this->branchContainsCurrent($children),
                depth: $depth,
                children: $children,
            );
        }

        return $branch;
    }

    /**
     * Vérifie si une branche contient l'item courant (direct ou hérité).
     *
     * @param MenuItemEntity[] $children Enfants à inspecter
     */
    private function branchContainsCurrent(array $children): bool
    {
        foreach ($children as $child) {
            if ($child->isCurrent || $child->isAncestor) {
                return true;
            }
        }

        return false;
    }
}
