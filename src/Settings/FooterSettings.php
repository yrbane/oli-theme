<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * DTO immuable pour les paramètres du pied de page.
 *
 * Contient le modèle de copyright, les indicateurs d'affichage des blocs,
 * et le logo + texte libre du footer.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final readonly class FooterSettings
{
    /**
     * @param string $copyrightTemplate Modèle de copyright (ex. "© {year} {site}").
     * @param bool $showSocial Afficher le bloc réseaux sociaux.
     * @param bool $showMenu Afficher le menu footer.
     * @param int|null $logoId Identifiant d'attachment du logo footer (null = pas de logo).
     * @param string $text Texte libre (HTML autorisé) affiché en bas du pied de page.
     */
    public function __construct(
        public string $copyrightTemplate,
        public bool $showSocial,
        public bool $showMenu,
        public ?int $logoId = null,
        public string $text = '',
    ) {
    }
}
