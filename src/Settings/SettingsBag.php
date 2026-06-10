<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * Agrégateur immuable de tous les sous-settings du thème.
 *
 * Constitue le point d'entrée unique pour accéder aux paramètres
 * regroupés par domaine fonctionnel (bannière, footer, réseaux, etc.).
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final readonly class SettingsBag
{
    /**
     * @param BannerSettings $banner Paramètres de la bannière et du logo.
     * @param FooterSettings $footer Paramètres du pied de page.
     * @param SocialSettings $social Paramètres des réseaux sociaux.
     * @param LanguagesSettings $languages Paramètres multilingues.
     * @param ContactSettings $contact Paramètres du formulaire de contact.
     * @param SeoSettings $seo Paramètres SEO globaux.
     * @param TypographySettings $typography Paramètres typographiques globaux.
     */
    public function __construct(
        public BannerSettings $banner,
        public FooterSettings $footer,
        public SocialSettings $social,
        public LanguagesSettings $languages,
        public ContactSettings $contact,
        public SeoSettings $seo,
        public TypographySettings $typography = new TypographySettings(),
    ) {
    }

    /**
     * Construit un SettingsBag avec des valeurs par défaut neutres
     * (utilisé quand `oli_theme_settings` n'est pas encore initialisé).
     */
    public static function default(): self
    {
        return new self(
            banner: new BannerSettings(null, null, null, []),
            footer: new FooterSettings('© {year} {site}', true, true),
            social: new SocialSettings(null, null, null, null, null),
            languages: new LanguagesSettings(['fr'], 'fr', LanguagesSettings::FALLBACK_HOME),
            contact: new ContactSettings(null, null, false, false),
            seo: new SeoSettings(null, null, null, null, true, null),
            typography: TypographySettings::default(),
        );
    }
}
