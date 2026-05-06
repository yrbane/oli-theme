<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

/**
 * Contrat du modèle de menu (narrow interface pour le mocking PHPUnit).
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
interface MenuModelInterface
{
    /**
     * Convertit la liste plate d'items WordPress en arbre d'entités.
     *
     * @param array<int, object> $items Liste plate des items WP (wp_get_nav_menu_items)
     * @param int $currentObjectId Identifiant de l'objet courant pour résoudre current/ancestor
     * @param string|null $currentUrlPath Path normalisé de l'URL courante (sans query, sans
     *                                    trailing slash) pour reconnaître les items qui pointent
     *                                    vers une archive ou un custom link (ex. archive de CPT
     *                                    où `currentObjectId` vaut 0).
     *
     * @return MenuItemEntity[]
     */
    public function toTree(array $items, int $currentObjectId, ?string $currentUrlPath = null): array;
}
