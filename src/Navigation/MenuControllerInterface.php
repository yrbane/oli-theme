<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

use OliTheme\I18n\Language;

/**
 * Contrat du controller de menus (mockable depuis les autres modules).
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
interface MenuControllerInterface
{
    /**
     * Construit l'arbre du menu principal pour une langue donnée.
     *
     * @param Language $language Langue cible
     *
     * @return MenuItemEntity[]
     */
    public function buildPrimary(Language $language): array;

    /**
     * Construit l'arbre du menu pied de page pour une langue donnée.
     *
     * @param Language $language Langue cible
     *
     * @return MenuItemEntity[]
     */
    public function buildFooter(Language $language): array;
}
