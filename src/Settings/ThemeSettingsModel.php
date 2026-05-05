<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * Modèle de lecture/écriture des paramètres du thème via l'option WordPress.
 *
 * Lit et écrit dans l'option `oli_theme_settings` (tableau associatif
 * de premier niveau) et hydrate un {@see SettingsBag} typé à la demande.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final class ThemeSettingsModel implements ThemeSettingsModelInterface
{
    /** Clé de l'option WordPress qui stocke tous les settings du thème. */
    private const OPTION_KEY = 'oli_theme_settings';

    /**
     * Retourne la valeur d'une clé de premier niveau des settings.
     *
     * @param string $key Clé de premier niveau (ex. 'banner', 'social').
     * @param mixed $default Valeur par défaut si la clé est absente.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->raw();

        return \array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    /**
     * Persiste la valeur d'une clé de premier niveau des settings.
     *
     * @param string $key Clé de premier niveau.
     * @param mixed $value Valeur à enregistrer.
     *
     * @return bool Vrai si la mise à jour a réussi.
     */
    public function set(string $key, mixed $value): bool
    {
        $settings       = $this->raw();
        $settings[$key] = $value;

        return (bool) update_option(self::OPTION_KEY, $settings);
    }

    /**
     * Retourne l'intégralité des settings sous forme d'un {@see SettingsBag} hydraté.
     */
    public function all(): SettingsBag
    {
        $raw = $this->raw();

        if ($raw === []) {
            return SettingsBag::default();
        }

        $banner    = $raw['banner'] ?? [];
        $footer    = $raw['footer'] ?? [];
        $social    = $raw['social'] ?? [];
        $languages = $raw['languages'] ?? [];
        $contact   = $raw['contact'] ?? [];
        $seo       = $raw['seo'] ?? [];

        $defaults = SettingsBag::default();

        return new SettingsBag(
            banner: new BannerSettings(
                logoId: isset($banner['logoId']) ? (int) $banner['logoId'] : $defaults->banner->logoId,
                bannerDesktopId: isset($banner['bannerDesktopId']) ? (int) $banner['bannerDesktopId'] : $defaults->banner->bannerDesktopId,
                bannerMobileId: isset($banner['bannerMobileId']) ? (int) $banner['bannerMobileId'] : $defaults->banner->bannerMobileId,
                altByLanguage: \is_array($banner['altByLanguage'] ?? null) ? $banner['altByLanguage'] : [],
            ),
            footer: new FooterSettings(
                legalByLanguage: \is_array($footer['legalByLanguage'] ?? null) ? $footer['legalByLanguage'] : [],
                copyrightTemplate: (string) ($footer['copyrightTemplate'] ?? $defaults->footer->copyrightTemplate),
                showLegal: (bool) ($footer['showLegal'] ?? $defaults->footer->showLegal),
                showSocial: (bool) ($footer['showSocial'] ?? $defaults->footer->showSocial),
                showMenu: (bool) ($footer['showMenu'] ?? $defaults->footer->showMenu),
            ),
            social: new SocialSettings(
                facebook: $this->stringOrNull($social, 'facebook'),
                instagram: $this->stringOrNull($social, 'instagram'),
                youtube: $this->stringOrNull($social, 'youtube'),
                linkedin: $this->stringOrNull($social, 'linkedin'),
                twitter: $this->stringOrNull($social, 'twitter'),
            ),
            languages: new LanguagesSettings(
                enabled: \is_array($languages['enabled'] ?? null) ? $languages['enabled'] : ['fr'],
                default: (string) ($languages['default'] ?? 'fr'),
                fallbackBehavior: (string) ($languages['fallbackBehavior'] ?? LanguagesSettings::FALLBACK_HOME),
            ),
            contact: new ContactSettings(
                email: $this->stringOrNull($contact, 'email'),
                autoreplyBody: $this->stringOrNull($contact, 'autoreplyBody'),
                autoreplyEnabled: (bool) ($contact['autoreplyEnabled'] ?? false),
                loggingEnabled: (bool) ($contact['loggingEnabled'] ?? false),
            ),
            seo: new SeoSettings(
                ogImageId: isset($seo['ogImageId']) ? (int) $seo['ogImageId'] : null,
                twitterHandle: $this->stringOrNull($seo, 'twitterHandle'),
                organizationName: $this->stringOrNull($seo, 'organizationName'),
                organizationLogoUrl: $this->stringOrNull($seo, 'organizationLogoUrl'),
                sitemapEnabled: (bool) ($seo['sitemapEnabled'] ?? true),
                robotsTxtCustom: $this->stringOrNull($seo, 'robotsTxtCustom'),
            ),
        );
    }

    /**
     * Lit l'option brute depuis WordPress.
     *
     * @return array<string, mixed>
     */
    private function raw(): array
    {
        $value = get_option(self::OPTION_KEY, []);

        return \is_array($value) ? $value : [];
    }

    /**
     * Retourne la valeur de la clé sous forme de chaîne non vide, ou null.
     *
     * @param array<string, mixed> $data Tableau source.
     * @param string $key Clé à lire.
     */
    private function stringOrNull(array $data, string $key): ?string
    {
        if (! isset($data[$key])) {
            return null;
        }

        $value = (string) $data[$key];

        return $value === '' ? null : $value;
    }
}
