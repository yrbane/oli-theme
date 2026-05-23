<?php

declare(strict_types=1);

namespace OliTheme\Admin;

/**
 * Contrat d'un sous-onglet de la page de réglages unifiée du thème.
 *
 * Chaque module expose un ou plusieurs onglets ; la page hôte les collecte
 * via {@see AdminTabRegistry} et délègue le rendu du panneau actif.
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
interface AdminTabInterface
{
    /** Identifiant du sous-onglet (slug `sub`), ex. 'galerie'. */
    public function id(): string;

    /** Identifiant du groupe parent (slug `tab`), ex. 'contenu'. */
    public function group(): string;

    /** Libellé affiché dans la barre de sous-onglets. */
    public function label(): string;

    /** Capability WP requise pour voir/rendre l'onglet. */
    public function capability(): string;

    /** Imprime le contenu du panneau (sans le wrapper `.wrap` ni le `<h1>`). */
    public function renderPanel(): void;
}
