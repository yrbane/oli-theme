<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * Contrat de lecture/écriture des paramètres du thème.
 *
 * Expose un accès clé/valeur à l'option WordPress `oli_theme_settings`
 * ainsi qu'une hydratation complète sous forme de {@see SettingsBag}.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
interface ThemeSettingsModelInterface
{
    /**
     * Retourne la valeur d'une clé de premier niveau des settings.
     *
     * @param string $key     Clé de premier niveau (ex. 'banner', 'social').
     * @param mixed  $default Valeur par défaut si la clé est absente.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Persiste la valeur d'une clé de premier niveau des settings.
     *
     * @param string $key   Clé de premier niveau.
     * @param mixed  $value Valeur à enregistrer.
     *
     * @return bool Vrai si la mise à jour a réussi.
     */
    public function set(string $key, mixed $value): bool;

    /**
     * Retourne l'intégralité des settings sous forme d'un {@see SettingsBag} hydraté.
     */
    public function all(): SettingsBag;
}
