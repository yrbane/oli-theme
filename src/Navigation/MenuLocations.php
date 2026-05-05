<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;

/**
 * Enregistre les locations de menus WordPress par langue activée.
 *
 * Chaque langue contribue deux locations : `primary_<code>` et `footer_<code>`.
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
final class MenuLocations
{
    /**
     * @param LanguageRegistryInterface $registry Registre des langues activées
     */
    public function __construct(private readonly LanguageRegistryInterface $registry)
    {
    }

    /**
     * Enregistre toutes les locations de menus via WordPress.
     *
     * Chaque langue activée produit deux entrées dans `register_nav_menus` :
     * une pour le menu principal et une pour le pied de page.
     */
    public function register(): void
    {
        $locations = [];
        foreach ($this->registry->all() as $language) {
            $locations[$this->primaryFor($language)] = \sprintf(
                __('Menu principal (%s)', 'oli-theme'),
                $language->nativeLabel,
            );
            $locations[$this->footerFor($language)] = \sprintf(
                __('Pied de page (%s)', 'oli-theme'),
                $language->nativeLabel,
            );
        }

        register_nav_menus($locations);
    }

    /**
     * Retourne la clé de location du menu principal pour une langue donnée.
     *
     * @param Language $language Langue cible
     */
    public function primaryFor(Language $language): string
    {
        return 'primary_' . $language->code;
    }

    /**
     * Retourne la clé de location du menu pied de page pour une langue donnée.
     *
     * @param Language $language Langue cible
     */
    public function footerFor(Language $language): string
    {
        return 'footer_' . $language->code;
    }
}
