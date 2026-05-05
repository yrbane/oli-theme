<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

/**
 * DTO immuable représentant un item de menu WordPress.
 *
 * Contient toutes les propriétés nécessaires au rendu d'un item de navigation,
 * y compris l'état courant/ancêtre et les enfants imbriqués.
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
final readonly class MenuItemEntity
{
    /**
     * @param int $id Identifiant WordPress de l'item de menu
     * @param string $label Libellé affiché dans le menu
     * @param string $url URL de destination
     * @param string $target Attribut target du lien (ex. '_blank', '')
     * @param bool $isCurrent Vrai si l'item correspond à la page courante
     * @param bool $isAncestor Vrai si l'item est un ancêtre de la page courante
     * @param int $depth Niveau d'imbrication (0 = racine)
     * @param MenuItemEntity[] $children Sous-items imbriqués
     */
    public function __construct(
        public int $id,
        public string $label,
        public string $url,
        public string $target,
        public bool $isCurrent,
        public bool $isAncestor,
        public int $depth,
        public array $children,
    ) {
    }
}
