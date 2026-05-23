<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * Page d'administration "Identité du site" sous Apparence.
 *
 * Implémente la Settings API native de WordPress :
 * `register_setting` / `add_settings_section` / `add_settings_field` /
 * `do_settings_sections`. Les cinq onglets (banner, languages, footer,
 * contact, seo) sont chacun rendus sur une **page de settings
 * distincte** (`oli-theme-settings-banner`, `-languages`, etc.) afin que
 * `do_settings_sections($activePage)` ne rende que la section de l'onglet
 * actif. La sauvegarde utilise un seul groupe d'options
 * (`oli_theme_settings_group`) et préserve les sections non soumises grâce
 * à un `array_merge` de premier niveau dans le sanitize.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final class ThemeSettingsPage
{
    /** Groupe Settings API qui regroupe tous les onglets dans une seule option. */
    public const GROUP = 'oli_theme_settings_group';

    /** Clé de l'option WordPress qui stocke les settings du thème. */
    public const OPTION = 'oli_theme_settings';

    /** Slug racine de la page, sans suffixe d'onglet. */
    public const PAGE_SLUG = 'oli-theme-settings';

    /** Onglet actif par défaut quand `?tab=` est absent. */
    public const DEFAULT_TAB = 'banner';

    public function __construct(
        private readonly ThemeSettingsModelInterface $settings,
    ) {
    }

    /**
     * Enregistre l'option, les cinq sections (une page par onglet) et leurs champs.
     */
    public function registerSettings(): void
    {
        register_setting(
            self::GROUP,
            self::OPTION,
            [
                'sanitize_callback' => [$this, 'sanitize'],
                'default'           => [],
            ],
        );

        $current = $this->settings->all();

        foreach ($this->tabDefinitions() as $tab) {
            $sectionId = 'oli_section_' . $tab['id'];
            $page      = self::PAGE_SLUG . '-' . $tab['id'];

            add_settings_section(
                $sectionId,
                $tab['title'],
                static fn (): null => null,
                $page,
            );

            $registrar = 'register' . ucfirst($tab['id']) . 'Fields';
            \assert(method_exists($this, $registrar));
            $this->{$registrar}($page, $sectionId, $current);
        }
    }

    /**
     * Imprime le formulaire Settings API d'un onglet donné, sans wrapper de page.
     * Appelé par la page hôte unifiée via l'adaptateur {@see SettingsTab}.
     */
    public function renderPanelFor(string $tab): void
    {
        if (!\in_array($tab, $this->tabIds(), true)) {
            $tab = self::DEFAULT_TAB;
        }

        echo '<form method="post" action="options.php">';
        // settings_fields() émet le nonce et les champs cachés requis par la Settings API.
        settings_fields(self::GROUP);
        do_settings_sections(self::PAGE_SLUG . '-' . $tab);
        printf('<input type="hidden" name="_oli_active_tab" value="%s" />', esc_attr($tab));
        submit_button();
        echo '</form>';
    }

    /**
     * Sanitize l'option globale : applique les sanitizers WP appropriés à
     * chaque champ et fusionne avec les autres sections (non soumises).
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function sanitize(array $input): array
    {
        $existing = (array) get_option(self::OPTION, []);
        $clean    = [];

        if (isset($input['banner']) && \is_array($input['banner'])) {
            $clean['banner'] = $this->sanitizeBanner($input['banner']);
        }
        if (isset($input['languages']) && \is_array($input['languages'])) {
            $clean['languages'] = $this->sanitizeLanguages($input['languages']);
        }
        if (isset($input['footer']) && \is_array($input['footer'])) {
            $clean['footer'] = $this->sanitizeFooter($input['footer']);
        }
        if (isset($input['contact']) && \is_array($input['contact'])) {
            $clean['contact'] = $this->sanitizeContact($input['contact']);
        }
        if (isset($input['seo']) && \is_array($input['seo'])) {
            $clean['seo'] = $this->sanitizeSeo($input['seo']);
        }

        return array_replace($existing, $clean);
    }

    // ---------------------------------------------------------------------
    // Section 1 : Identité visuelle (banner)
    // ---------------------------------------------------------------------

    private function registerBannerFields(string $page, string $section, SettingsBag $current): void
    {
        add_settings_field(
            'oli_banner_logo_id',
            __('ID du média logo', 'oli-theme'),
            fn () => $this->renderMediaIdField('banner', 'logoId', $current->banner->logoId),
            $page,
            $section,
        );
        add_settings_field(
            'oli_banner_desktop_id',
            __('ID du média bannière desktop', 'oli-theme'),
            fn () => $this->renderMediaIdField('banner', 'bannerDesktopId', $current->banner->bannerDesktopId),
            $page,
            $section,
        );
        add_settings_field(
            'oli_banner_mobile_id',
            __('ID du média bannière mobile', 'oli-theme'),
            fn () => $this->renderMediaIdField('banner', 'bannerMobileId', $current->banner->bannerMobileId),
            $page,
            $section,
        );
        add_settings_field(
            'oli_banner_alt_fr',
            __('Texte alternatif (FR)', 'oli-theme'),
            fn () => $this->renderTextField(
                'banner',
                'altByLanguage[fr]',
                (string) ($current->banner->altByLanguage['fr'] ?? ''),
                __('Texte alternatif affiché si l\'image ne se charge pas.', 'oli-theme'),
            ),
            $page,
            $section,
        );
        add_settings_field(
            'oli_banner_alt_en',
            __('Texte alternatif (EN)', 'oli-theme'),
            fn () => $this->renderTextField(
                'banner',
                'altByLanguage[en]',
                (string) ($current->banner->altByLanguage['en'] ?? ''),
                '',
            ),
            $page,
            $section,
        );
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function sanitizeBanner(array $input): array
    {
        $alt = [];
        if (isset($input['altByLanguage']) && \is_array($input['altByLanguage'])) {
            foreach ($input['altByLanguage'] as $lang => $value) {
                $alt[sanitize_key((string) $lang)] = sanitize_text_field((string) $value);
            }
        }

        return [
            'logoId'          => $this->intOrNull($input['logoId'] ?? null),
            'bannerDesktopId' => $this->intOrNull($input['bannerDesktopId'] ?? null),
            'bannerMobileId'  => $this->intOrNull($input['bannerMobileId'] ?? null),
            'altByLanguage'   => $alt,
        ];
    }

    // ---------------------------------------------------------------------
    // Section 2 : Langues (languages)
    // ---------------------------------------------------------------------

    private function registerLanguagesFields(string $page, string $section, SettingsBag $current): void
    {
        add_settings_field(
            'oli_languages_enabled',
            __('Langues activées', 'oli-theme'),
            fn () => $this->renderLanguagesCheckboxes($current->languages->enabled),
            $page,
            $section,
        );
        add_settings_field(
            'oli_languages_default',
            __('Langue par défaut', 'oli-theme'),
            fn () => $this->renderLanguagesDefault($current->languages->default, $current->languages->enabled),
            $page,
            $section,
        );
        add_settings_field(
            'oli_languages_fallback',
            __('Comportement de repli', 'oli-theme'),
            fn () => $this->renderLanguagesFallback($current->languages->fallbackBehavior),
            $page,
            $section,
        );
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function sanitizeLanguages(array $input): array
    {
        $enabled = [];
        if (isset($input['enabled']) && \is_array($input['enabled'])) {
            foreach ($input['enabled'] as $code) {
                $code = sanitize_key((string) $code);
                if ($code !== '') {
                    $enabled[] = $code;
                }
            }
        }
        if ($enabled === []) {
            $enabled = ['fr'];
        }

        $default = sanitize_key((string) ($input['default'] ?? 'fr'));
        if (!\in_array($default, $enabled, true)) {
            $default = $enabled[0];
        }

        $fallback = (string) ($input['fallbackBehavior'] ?? LanguagesSettings::FALLBACK_HOME);
        $allowed  = [
            LanguagesSettings::FALLBACK_HOME,
            LanguagesSettings::FALLBACK_SHOW_SOURCE,
            LanguagesSettings::FALLBACK_MESSAGE,
        ];
        if (!\in_array($fallback, $allowed, true)) {
            $fallback = LanguagesSettings::FALLBACK_HOME;
        }

        return [
            'enabled'          => array_values(array_unique($enabled)),
            'default'          => $default,
            'fallbackBehavior' => $fallback,
        ];
    }

    // ---------------------------------------------------------------------
    // Section 3 : Pied de page (footer)
    // ---------------------------------------------------------------------

    private function registerFooterFields(string $page, string $section, SettingsBag $current): void
    {
        add_settings_field(
            'oli_footer_legal_fr',
            __('Mentions légales (FR)', 'oli-theme'),
            fn () => $this->renderTextarea(
                'footer',
                'legalByLanguage[fr]',
                (string) ($current->footer->legalByLanguage['fr'] ?? ''),
                __('HTML autorisé (liens, gras, paragraphes).', 'oli-theme'),
            ),
            $page,
            $section,
        );
        add_settings_field(
            'oli_footer_legal_en',
            __('Mentions légales (EN)', 'oli-theme'),
            fn () => $this->renderTextarea(
                'footer',
                'legalByLanguage[en]',
                (string) ($current->footer->legalByLanguage['en'] ?? ''),
                '',
            ),
            $page,
            $section,
        );
        add_settings_field(
            'oli_footer_copyright',
            __('Modèle de copyright', 'oli-theme'),
            fn () => $this->renderTextField(
                'footer',
                'copyrightTemplate',
                $current->footer->copyrightTemplate,
                __('Placeholders disponibles : {year}, {site}.', 'oli-theme'),
            ),
            $page,
            $section,
        );
        add_settings_field(
            'oli_footer_show_legal',
            __('Afficher les mentions légales', 'oli-theme'),
            fn () => $this->renderCheckbox('footer', 'showLegal', $current->footer->showLegal),
            $page,
            $section,
        );
        add_settings_field(
            'oli_footer_show_social',
            __('Afficher les réseaux sociaux', 'oli-theme'),
            fn () => $this->renderCheckbox('footer', 'showSocial', $current->footer->showSocial),
            $page,
            $section,
        );
        add_settings_field(
            'oli_footer_show_menu',
            __('Afficher le menu footer', 'oli-theme'),
            fn () => $this->renderCheckbox('footer', 'showMenu', $current->footer->showMenu),
            $page,
            $section,
        );
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function sanitizeFooter(array $input): array
    {
        $legal = [];
        if (isset($input['legalByLanguage']) && \is_array($input['legalByLanguage'])) {
            foreach ($input['legalByLanguage'] as $lang => $html) {
                $legal[sanitize_key((string) $lang)] = wp_kses_post((string) $html);
            }
        }

        return [
            'legalByLanguage'   => $legal,
            'copyrightTemplate' => sanitize_text_field((string) ($input['copyrightTemplate'] ?? '© {year} {site}')),
            'showLegal'         => !empty($input['showLegal']),
            'showSocial'        => !empty($input['showSocial']),
            'showMenu'          => !empty($input['showMenu']),
        ];
    }

    // ---------------------------------------------------------------------
    // Section 4 : Contact (contact)
    // ---------------------------------------------------------------------

    private function registerContactFields(string $page, string $section, SettingsBag $current): void
    {
        add_settings_field(
            'oli_contact_email',
            __('Adresse e-mail destinataire', 'oli-theme'),
            fn () => $this->renderEmailField(
                'contact',
                'email',
                (string) ($current->contact->email ?? ''),
                __('Adresse qui reçoit les soumissions du formulaire de contact.', 'oli-theme'),
            ),
            $page,
            $section,
        );
        add_settings_field(
            'oli_contact_autoreply_body',
            __('Corps de la réponse automatique', 'oli-theme'),
            fn () => $this->renderTextarea(
                'contact',
                'autoreplyBody',
                (string) ($current->contact->autoreplyBody ?? ''),
                '',
            ),
            $page,
            $section,
        );
        add_settings_field(
            'oli_contact_autoreply_enabled',
            __('Activer la réponse automatique', 'oli-theme'),
            fn () => $this->renderCheckbox('contact', 'autoreplyEnabled', $current->contact->autoreplyEnabled),
            $page,
            $section,
        );
        add_settings_field(
            'oli_contact_logging_enabled',
            __('Archiver les soumissions en base', 'oli-theme'),
            fn () => $this->renderCheckbox('contact', 'loggingEnabled', $current->contact->loggingEnabled),
            $page,
            $section,
        );
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function sanitizeContact(array $input): array
    {
        $email = (string) ($input['email'] ?? '');

        return [
            'email'            => $email === '' ? null : sanitize_email($email),
            'autoreplyBody'    => isset($input['autoreplyBody']) ? wp_kses_post((string) $input['autoreplyBody']) : null,
            'autoreplyEnabled' => !empty($input['autoreplyEnabled']),
            'loggingEnabled'   => !empty($input['loggingEnabled']),
        ];
    }

    // ---------------------------------------------------------------------
    // Section 5 : SEO global (seo)
    // ---------------------------------------------------------------------

    private function registerSeoFields(string $page, string $section, SettingsBag $current): void
    {
        add_settings_field(
            'oli_seo_og_image_id',
            __('ID image Open Graph par défaut', 'oli-theme'),
            fn () => $this->renderMediaIdField('seo', 'ogImageId', $current->seo->ogImageId),
            $page,
            $section,
        );
        add_settings_field(
            'oli_seo_twitter_handle',
            __('Handle Twitter / X', 'oli-theme'),
            fn () => $this->renderTextField(
                'seo',
                'twitterHandle',
                (string) ($current->seo->twitterHandle ?? ''),
                __('Sans le @.', 'oli-theme'),
            ),
            $page,
            $section,
        );
        add_settings_field(
            'oli_seo_org_name',
            __('Nom de l\'organisation', 'oli-theme'),
            fn () => $this->renderTextField(
                'seo',
                'organizationName',
                (string) ($current->seo->organizationName ?? ''),
                __('Utilisé dans les données structurées JSON-LD.', 'oli-theme'),
            ),
            $page,
            $section,
        );
        add_settings_field(
            'oli_seo_org_logo_url',
            __('URL du logo de l\'organisation', 'oli-theme'),
            fn () => $this->renderUrlField(
                'seo',
                'organizationLogoUrl',
                (string) ($current->seo->organizationLogoUrl ?? ''),
                __('URL absolue du logo (utilisée dans le JSON-LD Organization).', 'oli-theme'),
            ),
            $page,
            $section,
        );
        add_settings_field(
            'oli_seo_sitemap_enabled',
            __('Sitemap XML activé', 'oli-theme'),
            fn () => $this->renderCheckbox('seo', 'sitemapEnabled', $current->seo->sitemapEnabled),
            $page,
            $section,
        );
        add_settings_field(
            'oli_seo_robots_custom',
            __('Contenu personnalisé de robots.txt', 'oli-theme'),
            fn () => $this->renderTextarea(
                'seo',
                'robotsTxtCustom',
                (string) ($current->seo->robotsTxtCustom ?? ''),
                __('Laisser vide pour utiliser le robots.txt par défaut de WordPress.', 'oli-theme'),
            ),
            $page,
            $section,
        );
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function sanitizeSeo(array $input): array
    {
        $logoUrl = (string) ($input['organizationLogoUrl'] ?? '');
        $robots  = (string) ($input['robotsTxtCustom'] ?? '');

        return [
            'ogImageId'           => $this->intOrNull($input['ogImageId'] ?? null),
            'twitterHandle'       => isset($input['twitterHandle']) && $input['twitterHandle'] !== ''
                ? sanitize_text_field((string) $input['twitterHandle'])
                : null,
            'organizationName'    => isset($input['organizationName']) && $input['organizationName'] !== ''
                ? sanitize_text_field((string) $input['organizationName'])
                : null,
            'organizationLogoUrl' => $logoUrl === '' ? null : esc_url_raw($logoUrl),
            'sitemapEnabled'      => !empty($input['sitemapEnabled']),
            'robotsTxtCustom'     => $robots === '' ? null : wp_kses_post($robots),
        ];
    }

    // ---------------------------------------------------------------------
    // Helpers d'affichage des champs (HTML).
    // ---------------------------------------------------------------------

    private function renderTextField(string $section, string $key, string $value, string $description): void
    {
        $name = $this->fieldName($section, $key);
        $id   = $this->fieldId($section, $key);
        printf(
            '<input type="text" id="%s" name="%s" value="%s" class="regular-text" />',
            esc_attr($id),
            esc_attr($name),
            esc_attr($value),
        );
        if ($description !== '') {
            printf('<p class="description">%s</p>', esc_html($description));
        }
    }

    private function renderUrlField(string $section, string $key, string $value, string $description): void
    {
        $name = $this->fieldName($section, $key);
        $id   = $this->fieldId($section, $key);
        printf(
            '<input type="url" id="%s" name="%s" value="%s" class="regular-text" placeholder="https://" />',
            esc_attr($id),
            esc_attr($name),
            esc_attr($value),
        );
        if ($description !== '') {
            printf('<p class="description">%s</p>', esc_html($description));
        }
    }

    private function renderEmailField(string $section, string $key, string $value, string $description): void
    {
        $name = $this->fieldName($section, $key);
        $id   = $this->fieldId($section, $key);
        printf(
            '<input type="email" id="%s" name="%s" value="%s" class="regular-text" />',
            esc_attr($id),
            esc_attr($name),
            esc_attr($value),
        );
        if ($description !== '') {
            printf('<p class="description">%s</p>', esc_html($description));
        }
    }

    private function renderMediaIdField(string $section, string $key, ?int $value): void
    {
        $name = $this->fieldName($section, $key);
        $id   = $this->fieldId($section, $key);
        printf(
            '<input type="number" id="%s" name="%s" value="%s" class="small-text" min="0" />',
            esc_attr($id),
            esc_attr($name),
            esc_attr((string) ($value ?? '')),
        );
        printf(
            '<p class="description">%s</p>',
            esc_html__('ID numérique du média dans la médiathèque WP. (Uploader visuel — cycle 2.)', 'oli-theme'),
        );
    }

    private function renderTextarea(string $section, string $key, string $value, string $description): void
    {
        $name = $this->fieldName($section, $key);
        $id   = $this->fieldId($section, $key);
        printf(
            '<textarea id="%s" name="%s" rows="5" class="large-text">%s</textarea>',
            esc_attr($id),
            esc_attr($name),
            esc_textarea($value),
        );
        if ($description !== '') {
            printf('<p class="description">%s</p>', esc_html($description));
        }
    }

    private function renderCheckbox(string $section, string $key, bool $value): void
    {
        $name = $this->fieldName($section, $key);
        $id   = $this->fieldId($section, $key);
        printf(
            '<label for="%s"><input type="checkbox" id="%s" name="%s" value="1"%s /> %s</label>',
            esc_attr($id),
            esc_attr($id),
            esc_attr($name),
            $value ? ' checked="checked"' : '',
            esc_html__('Activé', 'oli-theme'),
        );
    }

    /**
     * @param string[] $enabled
     */
    private function renderLanguagesCheckboxes(array $enabled): void
    {
        $available = ['fr' => 'Français', 'en' => 'English', 'it' => 'Italiano', 'es' => 'Español', 'de' => 'Deutsch'];
        echo '<fieldset>';
        foreach ($available as $code => $label) {
            $checked = \in_array($code, $enabled, true);
            printf(
                '<label style="display:block;margin-bottom:.25rem;"><input type="checkbox" name="%s[]" value="%s"%s /> %s (<code>%s</code>)</label>',
                esc_attr($this->fieldName('languages', 'enabled')),
                esc_attr($code),
                $checked ? ' checked="checked"' : '',
                esc_html($label),
                esc_html($code),
            );
        }
        echo '</fieldset>';
    }

    /**
     * @param string[] $enabled
     */
    private function renderLanguagesDefault(string $current, array $enabled): void
    {
        if ($enabled === []) {
            $enabled = ['fr'];
        }
        printf('<select name="%s">', esc_attr($this->fieldName('languages', 'default')));
        foreach ($enabled as $code) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($code),
                $code === $current ? ' selected="selected"' : '',
                esc_html($code),
            );
        }
        echo '</select>';
        printf(
            '<p class="description">%s</p>',
            esc_html__('Langue affichée par défaut sur la racine du site (/).', 'oli-theme'),
        );
    }

    private function renderLanguagesFallback(string $current): void
    {
        $options = [
            LanguagesSettings::FALLBACK_HOME        => __('Rediriger vers l\'accueil', 'oli-theme'),
            LanguagesSettings::FALLBACK_SHOW_SOURCE => __('Afficher la version source', 'oli-theme'),
            LanguagesSettings::FALLBACK_MESSAGE     => __('Afficher un message d\'erreur', 'oli-theme'),
        ];
        echo '<fieldset>';
        foreach ($options as $value => $label) {
            printf(
                '<label style="display:block;margin-bottom:.25rem;"><input type="radio" name="%s" value="%s"%s /> %s</label>',
                esc_attr($this->fieldName('languages', 'fallbackBehavior')),
                esc_attr($value),
                $value === $current ? ' checked="checked"' : '',
                esc_html($label),
            );
        }
        echo '</fieldset>';
    }

    // ---------------------------------------------------------------------
    // Helpers internes.
    // ---------------------------------------------------------------------

    private function fieldName(string $section, string $key): string
    {
        // Permet `altByLanguage[fr]` → `oli_theme_settings[banner][altByLanguage][fr]`.
        if (str_contains($key, '[')) {
            [$head, $rest]  = explode('[', $key, 2);
            $bracketedTail  = '[' . $rest;

            return self::OPTION . '[' . $section . '][' . $head . ']' . $bracketedTail;
        }

        return self::OPTION . '[' . $section . '][' . $key . ']';
    }

    private function fieldId(string $section, string $key): string
    {
        return 'oli_' . $section . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '0') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @return array<int, array{id: string, title: string}>
     */
    private function tabDefinitions(): array
    {
        return [
            ['id' => 'banner',    'title' => __('Identité visuelle', 'oli-theme')],
            ['id' => 'languages', 'title' => __('Langues', 'oli-theme')],
            ['id' => 'footer',    'title' => __('Pied de page', 'oli-theme')],
            ['id' => 'contact',   'title' => __('Contact', 'oli-theme')],
            ['id' => 'seo',       'title' => __('SEO global', 'oli-theme')],
        ];
    }

    /**
     * @return string[]
     */
    private function tabIds(): array
    {
        return ['banner', 'languages', 'footer', 'contact', 'seo'];
    }
}
