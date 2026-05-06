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
     * @param string|null $currentUrlPath Path normalisé de l'URL courante (sans query/trailing slash)
     *
     * @return MenuItemEntity[]
     */
    public function toTree(array $items, int $currentObjectId, ?string $currentUrlPath = null): array
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

        $normalizedCurrentPath = $currentUrlPath !== null ? $this->normalizePath($currentUrlPath) : null;

        return $this->buildBranch($byParent, 0, 0, $currentObjectId, $normalizedCurrentPath);
    }

    /**
     * Construit récursivement une branche de l'arbre.
     *
     * @param array<int, list<object>> $byParent Items indexés par ID parent
     * @param int $parentId Identifiant du parent courant
     * @param int $depth Niveau d'imbrication courant
     * @param int $currentObjectId Identifiant de l'objet courant
     * @param string|null $currentUrlPath Path normalisé de l'URL courante (déjà passé au format canonique)
     *
     * @return MenuItemEntity[]
     */
    private function buildBranch(array $byParent, int $parentId, int $depth, int $currentObjectId, ?string $currentUrlPath): array
    {
        if (! isset($byParent[$parentId])) {
            return [];
        }

        $branch = [];
        foreach ($byParent[$parentId] as $item) {
            $children = $this->buildBranch($byParent, (int) ($item->ID ?? 0), $depth + 1, $currentObjectId, $currentUrlPath);
            $branch[] = new MenuItemEntity(
                id: (int) ($item->ID ?? 0),
                label: (string) ($item->title ?? ''),
                url: (string) ($item->url ?? ''),
                target: (string) ($item->target ?? ''),
                isCurrent: $this->isItemCurrent($item, $currentObjectId, $currentUrlPath),
                isAncestor: $this->branchContainsCurrent($children),
                depth: $depth,
                children: $children,
            );
        }

        return $branch;
    }

    /**
     * Détermine si un item est l'item courant. Match d'abord par object_id ;
     * fallback sur l'URL pour les archives de CPT (où object_id vaut 0) et les
     * custom links.
     */
    private function isItemCurrent(object $item, int $currentObjectId, ?string $currentUrlPath): bool
    {
        if ($currentObjectId > 0 && (int) ($item->object_id ?? 0) === $currentObjectId) {
            return true;
        }

        if ($currentUrlPath !== null) {
            $itemPath = $this->normalizePath((string) ($item->url ?? ''));
            if ($itemPath !== '' && $itemPath === $currentUrlPath) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extrait le path d'une URL (absolue ou relative) et retire le trailing slash
     * pour permettre une comparaison stable.
     */
    private function normalizePath(string $url): string
    {
        $path = parse_url($url, \PHP_URL_PATH);
        if (!\is_string($path)) {
            $path = $url;
        }

        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
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
