<?php

declare(strict_types=1);

namespace OliTheme\Settings;

use OliTheme\Admin\AdminTabInterface;

/**
 * Adaptateur exposant un onglet de {@see ThemeSettingsPage} (banner, languages,
 * footer, contact, seo) comme sous-onglet de la page de réglages unifiée.
 *
 * @package OliTheme\Settings
 *
 * @since 1.1.0
 */
final class SettingsTab implements AdminTabInterface
{
    public function __construct(
        private readonly ThemeSettingsPage $page,
        private readonly string $settingsTab,
        private readonly string $group,
        private readonly string $id,
        private readonly string $label,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function group(): string
    {
        return $this->group;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    public function renderPanel(): void
    {
        $this->page->renderPanelFor($this->settingsTab);
    }
}
