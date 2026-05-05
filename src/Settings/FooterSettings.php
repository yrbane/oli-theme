<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * DTO immuable pour les paramètres du pied de page.
 *
 * Contient les mentions légales par langue, le modèle de copyright
 * et les indicateurs d'affichage des blocs footer.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final readonly class FooterSettings
{
    /**
     * @param array<string, string> $legalByLanguage   HTML des mentions légales par langue.
     * @param string                $copyrightTemplate Modèle de copyright (ex. "© {year} {site}").
     * @param bool                  $showLegal         Afficher le bloc mentions légales.
     * @param bool                  $showSocial        Afficher le bloc réseaux sociaux.
     * @param bool                  $showMenu          Afficher le menu footer.
     */
    public function __construct(
        public array $legalByLanguage,
        public string $copyrightTemplate,
        public bool $showLegal,
        public bool $showSocial,
        public bool $showMenu,
    ) {}
}
