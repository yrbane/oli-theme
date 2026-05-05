<?php

declare(strict_types=1);

namespace OliTheme\Settings;

use OliTheme\Core\RendererInterface;

/**
 * Page d'administration "Identité du site" sous Apparence.
 *
 * Enregistre la page de menu, les sections de réglages WordPress et
 * délègue le rendu au moteur de templates via {@see RendererInterface}.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final class ThemeSettingsPage
{
    /**
     * @param RendererInterface $renderer Moteur de rendu des templates.
     * @param ThemeSettingsModelInterface $settings Modèle de lecture des settings (utilisé dans les futures méthodes de rendu partiel).
     */
    public function __construct(
        private readonly RendererInterface $renderer,
        private readonly ThemeSettingsModelInterface $settings,
    ) {
    }

    /**
     * Enregistre la page sous Apparence > Identité du site.
     */
    public function register(): void
    {
        add_theme_page(
            __('Identité du site', 'oli-theme'),
            __('Identité du site', 'oli-theme'),
            'manage_options',
            'oli-theme-settings',
            [$this, 'render'],
        );
    }

    /**
     * Enregistre le groupe de réglages et les sections WP Settings API.
     */
    public function registerSettings(): void
    {
        register_setting(
            'oli_theme_settings_group',
            'oli_theme_settings',
            [
                'sanitize_callback' => [$this, 'sanitize'],
                'default'           => [],
            ],
        );

        foreach ($this->sectionDefinitions() as $section) {
            add_settings_section(
                $section['id'],
                $section['title'],
                static fn (): null => null,
                'oli-theme-settings',
            );
        }
    }

    /**
     * Affiche la page de réglages via le moteur de templates.
     */
    public function render(): void
    {
        $activeTab = isset($_GET['tab']) && \is_string($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'banner';

        // Capture la sortie HTML générée par les fonctions WP Settings API.
        ob_start();
        settings_fields('oli_theme_settings_group');
        do_settings_sections('oli-theme-settings');
        submit_button();
        $form = (string) ob_get_clean();

        echo $this->renderer->render('admin/settings-page.html', [
            'title' => __('Identité du site', 'oli-theme'),
            'tabs'  => $this->tabsFor($activeTab),
            'form'  => $form,
        ]);
    }

    /**
     * Fusionne les données soumises avec les réglages existants.
     *
     * @param array<string, mixed> $input Données soumises via le formulaire.
     *
     * @return array<string, mixed>
     */
    public function sanitize(array $input): array
    {
        $existing = (array) get_option('oli_theme_settings', []);

        return array_merge($existing, $input);
    }

    /**
     * Retourne les définitions des sections de réglages.
     *
     * @return array<int, array{id: string, title: string}>
     */
    private function sectionDefinitions(): array
    {
        return [
            ['id' => 'oli_section_banner',    'title' => __('Identité visuelle', 'oli-theme')],
            ['id' => 'oli_section_languages', 'title' => __('Langues', 'oli-theme')],
            ['id' => 'oli_section_social',    'title' => __('Réseaux sociaux', 'oli-theme')],
            ['id' => 'oli_section_footer',    'title' => __('Pied de page', 'oli-theme')],
            ['id' => 'oli_section_contact',   'title' => __('Contact', 'oli-theme')],
            ['id' => 'oli_section_seo',       'title' => __('SEO global', 'oli-theme')],
        ];
    }

    /**
     * Construit la liste des onglets de navigation avec leur état actif et leur URL.
     *
     * @param string $activeId Identifiant de l'onglet actif.
     *
     * @return array<int, array{id: string, label: string, isActive: bool, url: string}>
     */
    private function tabsFor(string $activeId): array
    {
        $tabs = [
            ['id' => 'banner',    'label' => __('Identité', 'oli-theme')],
            ['id' => 'languages', 'label' => __('Langues', 'oli-theme')],
            ['id' => 'social',    'label' => __('Réseaux', 'oli-theme')],
            ['id' => 'footer',    'label' => __('Footer', 'oli-theme')],
            ['id' => 'contact',   'label' => __('Contact', 'oli-theme')],
            ['id' => 'seo',       'label' => __('SEO', 'oli-theme')],
        ];

        return array_map(
            static fn (array $tab): array => [
                'id'       => $tab['id'],
                'label'    => $tab['label'],
                'isActive' => $tab['id'] === $activeId,
                'url'      => add_query_arg(['page' => 'oli-theme-settings', 'tab' => $tab['id']], admin_url('themes.php')),
            ],
            $tabs,
        );
    }
}
