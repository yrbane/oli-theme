<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Contrat des modules fonctionnels du thème.
 *
 * Un module regroupe une cohérence métier (I18n, SEO, Contact, Events...) et
 * enregistre ses propres hooks WordPress lors de son initialisation au
 * chargement du thème par {@see \OliTheme\Theme::boot()}.
 *
 * @package OliTheme\Core
 *
 * @since 1.0.0
 */
interface ModuleInterface
{
    /**
     * Enregistre les hooks WordPress nécessaires au module.
     *
     * Cette méthode est appelée une seule fois, au démarrage du thème.
     * Elle ne doit déclencher aucun appel à add_action / add_filter avant
     * que WordPress soit prêt — limiter à des bindings sur des hooks
     * postérieurs ('init', 'wp_loaded', 'template_redirect'...).
     */
    public function register(): void;
}
