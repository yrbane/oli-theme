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

    /** Garde : le script du sélecteur de médiathèque n'est imprimé qu'une fois. */
    private bool $mediaScriptPrinted = false;

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

        // Les onglets contenant des champs média ont besoin de wp.media (sélecteur
        // de médiathèque). Enqueue ici car la page hôte n'a pas de hook dédié.
        if (\in_array($tab, ['banner', 'seo'], true) && \function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
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
        if (isset($input['typography']) && \is_array($input['typography'])) {
            $clean['typography'] = $this->sanitizeTypography($input['typography']);
        }

        return array_replace($existing, $clean);
    }

    // ---------------------------------------------------------------------
    // Section 1 : Identité visuelle (banner)
    // ---------------------------------------------------------------------

    private function registerBannerFields(string $page, string $section, SettingsBag $current): void
    {
        $helpBanner = self::helpBubble('banniere');
        $helpIdent  = self::helpBubble('identite');
        add_settings_field(
            'oli_banner_logo_id',
            __('ID du média logo', 'oli-theme') . $helpIdent,
            fn () => $this->renderMediaIdField('banner', 'logoId', $current->banner->logoId),
            $page,
            $section,
        );
        add_settings_field(
            'oli_banner_desktop_id',
            __('ID du média bannière desktop', 'oli-theme') . $helpBanner,
            fn () => $this->renderMediaIdField('banner', 'bannerDesktopId', $current->banner->bannerDesktopId),
            $page,
            $section,
        );
        add_settings_field(
            'oli_banner_mobile_id',
            __('ID du média bannière mobile', 'oli-theme') . $helpBanner,
            fn () => $this->renderMediaIdField('banner', 'bannerMobileId', $current->banner->bannerMobileId),
            $page,
            $section,
        );
        add_settings_field(
            'oli_banner_alt_fr',
            __('Texte alternatif (FR)', 'oli-theme') . $helpBanner,
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
            __('Texte alternatif (EN)', 'oli-theme') . $helpBanner,
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
     * Génère le HTML d'une bulle d'aide « ? » liant vers un guide de l'onglet Aide.
     *
     * Utilise les helpers WP s'ils sont chargés, sinon construit une URL relative
     * (utile pour les tests unitaires hors WordPress).
     */
    private static function helpBubble(string $guideId): string
    {
        $guideId = preg_replace('/[^a-z0-9_\-]/', '', strtolower($guideId)) ?? '';
        if (\function_exists('admin_url') && \function_exists('add_query_arg')) {
            $url = add_query_arg(
                ['page' => 'oli-theme-settings', 'tab' => 'aide', 'guide' => $guideId],
                admin_url('themes.php'),
            );
        } else {
            $url = '/wp-admin/themes.php?page=oli-theme-settings&tab=aide&guide=' . $guideId;
        }
        $urlAttr = \function_exists('esc_url') ? esc_url($url) : htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $label   = \function_exists('esc_attr__')
            ? esc_attr__('Voir le guide d\'aide', 'oli-theme')
            : 'Voir le guide d\'aide';

        return ' <a class="oli-help-bubble" href="' . $urlAttr
            . '" title="' . $label . '" aria-label="' . $label . '">?</a>';
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
        // Affiche l'état RÉEL des langues activées : le registre runtime lit
        // l'option `oli_languages`, qui est la source de vérité (cf. sanitize
        // qui la synchronise). La lecture est paresseuse (dans les callbacks de
        // rendu) pour ne pas toucher get_option hors contexte d'affichage.
        $helpLang = self::helpBubble('traductions');
        add_settings_field(
            'oli_languages_enabled',
            __('Langues activées', 'oli-theme') . $helpLang,
            function () use ($current): void {
                [$enabled] = $this->liveLanguages($current);
                $this->renderLanguagesCheckboxes($enabled);
            },
            $page,
            $section,
        );
        add_settings_field(
            'oli_languages_default',
            __('Langue par défaut', 'oli-theme') . $helpLang,
            function () use ($current): void {
                [$enabled, $default] = $this->liveLanguages($current);
                $this->renderLanguagesDefault($default, $enabled);
            },
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

        $enabled = array_values(array_unique($enabled));

        // Synchronise l'option `oli_languages` lue par LanguageRegistry (source
        // de vérité runtime pour le routing et le sélecteur de langue). Sans ça,
        // cocher/décocher une langue ici resterait sans effet sur le front.
        if (\function_exists('update_option')) {
            update_option('oli_languages', ['enabled' => $enabled, 'default' => $default]);
        }

        return [
            'enabled'          => $enabled,
            'default'          => $default,
            'fallbackBehavior' => $fallback,
        ];
    }

    /**
     * État réel des langues : lit l'option `oli_languages` (source runtime),
     * avec repli sur les valeurs du SettingsBag si l'option est absente.
     *
     * @return array{0: list<string>, 1: string}
     */
    private function liveLanguages(SettingsBag $current): array
    {
        $enabled = $current->languages->enabled;
        $default = $current->languages->default;

        $stored = \function_exists('get_option') ? get_option('oli_languages', null) : null;
        if (\is_array($stored)) {
            if (\is_array($stored['enabled'] ?? null) && $stored['enabled'] !== []) {
                $enabled = array_values(array_filter(
                    $stored['enabled'],
                    static fn ($c): bool => \is_string($c),
                ));
            }
            if (\is_string($stored['default'] ?? null) && $stored['default'] !== '') {
                $default = $stored['default'];
            }
        }

        return [$enabled, $default];
    }

    // ---------------------------------------------------------------------
    // Section 3 : Pied de page (footer)
    // ---------------------------------------------------------------------

    private function registerFooterFields(string $page, string $section, SettingsBag $current): void
    {
        $helpFooter = self::helpBubble('footer');
        add_settings_field(
            'oli_footer_legal_fr',
            __('Mentions légales (FR)', 'oli-theme') . $helpFooter,
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
            __('Mentions légales (EN)', 'oli-theme') . $helpFooter,
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
        add_settings_field(
            'oli_footer_logo_id',
            __('Logo footer', 'oli-theme') . $helpFooter,
            fn () => $this->renderMediaIdField('footer', 'logoId', $current->footer->logoId),
            $page,
            $section,
        );
        add_settings_field(
            'oli_footer_text',
            __('Texte libre du footer', 'oli-theme') . $helpFooter,
            fn () => $this->renderTextarea(
                'footer',
                'text',
                $current->footer->text,
                __('HTML autorisé (liens, gras, paragraphes). Affiché tout en bas du pied de page.', 'oli-theme'),
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
    private function sanitizeFooter(array $input): array
    {
        $legal = [];
        if (isset($input['legalByLanguage']) && \is_array($input['legalByLanguage'])) {
            foreach ($input['legalByLanguage'] as $lang => $html) {
                $legal[sanitize_key((string) $lang)] = wp_kses_post((string) $html);
            }
        }

        $logoIdRaw = $input['logoId'] ?? null;
        $logoId    = ($logoIdRaw !== null && $logoIdRaw !== '') ? absint((int) $logoIdRaw) : null;

        return [
            'legalByLanguage'   => $legal,
            'copyrightTemplate' => sanitize_text_field((string) ($input['copyrightTemplate'] ?? '© {year} {site}')),
            'showLegal'         => !empty($input['showLegal']),
            'showSocial'        => !empty($input['showSocial']),
            'showMenu'          => !empty($input['showMenu']),
            'logoId'            => $logoId,
            'text'              => wp_kses_post((string) ($input['text'] ?? '')),
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
        $name       = $this->fieldName($section, $key);
        $id         = $this->fieldId($section, $key);
        $hasValue   = $value !== null && $value > 0;
        $previewUrl = $hasValue && \function_exists('wp_get_attachment_image_url')
            ? (string) wp_get_attachment_image_url($value, 'thumbnail')
            : '';

        echo '<div class="oli-media-field">';
        printf(
            '<input type="hidden" id="%s" name="%s" value="%s" class="oli-media-id" />',
            esc_attr($id),
            esc_attr($name),
            esc_attr((string) ($value ?? '')),
        );
        echo '<div class="oli-media-preview">';
        if ($previewUrl !== '') {
            printf(
                '<img src="%s" alt="" style="max-width:140px;height:auto;display:block;'
                . 'border:1px solid #dcdcde;border-radius:3px;margin-bottom:0.4rem;" />',
                esc_url($previewUrl),
            );
        }
        echo '</div>';
        printf(
            '<button type="button" class="button oli-media-pick" data-title="%s">%s</button> '
            . '<button type="button" class="button-link oli-media-remove" '
            . 'style="color:#b32d2e;margin-left:0.5rem;%s">%s</button>',
            esc_attr__('Choisir une image', 'oli-theme'),
            esc_html__('Choisir une image', 'oli-theme'),
            $hasValue ? '' : 'display:none;',
            esc_html__('Retirer', 'oli-theme'),
        );
        echo '</div>';

        $this->printMediaPickerScriptOnce();
    }

    /**
     * Imprime une seule fois le script générique du sélecteur de médiathèque.
     * Délégation d'événements sur `.oli-media-pick` / `.oli-media-remove` dans
     * un conteneur `.oli-media-field`, donc valable pour tous les champs média.
     */
    private function printMediaPickerScriptOnce(): void
    {
        if ($this->mediaScriptPrinted) {
            return;
        }
        $this->mediaScriptPrinted = true;

        $unavailable = esc_js(__('Médiathèque indisponible. Rechargez la page.', 'oli-theme'));
        $useLabel    = esc_js(__('Utiliser cette image', 'oli-theme'));
        ?>
        <script>
        (function () {
            if (window.__oliMediaPicker) { return; }
            window.__oliMediaPicker = true;
            document.addEventListener('click', function (e) {
                var pick = e.target.closest('.oli-media-pick');
                var remove = e.target.closest('.oli-media-remove');
                if (pick) {
                    e.preventDefault();
                    var field = pick.closest('.oli-media-field');
                    if (typeof wp === 'undefined' || !wp.media) {
                        window.alert('<?php echo $unavailable; ?>');
                        return;
                    }
                    var frame = wp.media({
                        title: pick.getAttribute('data-title') || '',
                        button: { text: '<?php echo $useLabel; ?>' },
                        library: { type: 'image' },
                        multiple: false
                    });
                    frame.on('select', function () {
                        var att = frame.state().get('selection').first().toJSON();
                        var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
                        field.querySelector('.oli-media-id').value = att.id;
                        field.querySelector('.oli-media-preview').innerHTML =
                            '<img src="' + url + '" alt="" style="max-width:140px;height:auto;display:block;'
                            + 'border:1px solid #dcdcde;border-radius:3px;margin-bottom:0.4rem;" />';
                        field.querySelector('.oli-media-remove').style.display = '';
                    });
                    frame.open();
                } else if (remove) {
                    e.preventDefault();
                    var f = remove.closest('.oli-media-field');
                    f.querySelector('.oli-media-id').value = '';
                    f.querySelector('.oli-media-preview').innerHTML = '';
                    remove.style.display = 'none';
                }
            });
        })();
        </script>
        <?php
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
            ['id' => 'banner',     'title' => __('Identité visuelle', 'oli-theme')],
            ['id' => 'languages',  'title' => __('Langues', 'oli-theme')],
            ['id' => 'footer',     'title' => __('Pied de page', 'oli-theme')],
            ['id' => 'contact',    'title' => __('Contact', 'oli-theme')],
            ['id' => 'seo',        'title' => __('SEO global', 'oli-theme')],
            ['id' => 'typography', 'title' => __('Typographie', 'oli-theme')],
        ];
    }

    /**
     * @return string[]
     */
    private function tabIds(): array
    {
        return ['banner', 'languages', 'footer', 'contact', 'seo', 'typography'];
    }

    private function registerTypographyFields(string $page, string $section, SettingsBag $current): void
    {
        $helpTypo = self::helpBubble('typo');
        $t        = $current->typography;

        add_settings_field(
            'oli_typography_base',
            __('Taille de base (rem)', 'oli-theme') . $helpTypo,
            fn () => $this->renderNumberField('typography', 'baseSize', $t->baseSize, TypographySettings::BASE_MIN, TypographySettings::BASE_MAX, 0.05, __('1 rem = 16 px par défaut. Entre 0.75 et 1.5.', 'oli-theme')),
            $page,
            $section,
        );
        add_settings_field(
            'oli_typography_scale',
            __('Ratio d\'échelle des titres', 'oli-theme') . $helpTypo,
            fn () => $this->renderNumberField('typography', 'scaleRatio', $t->scaleRatio, TypographySettings::RATIO_MIN, TypographySettings::RATIO_MAX, 0.01, __('Suite géométrique entre h6 et h1. Entre 1.05 (subtil) et 1.6 (contrasté).', 'oli-theme')),
            $page,
            $section,
        );
        add_settings_field(
            'oli_typography_menu',
            __('Taille du menu (rem)', 'oli-theme') . $helpTypo,
            fn () => $this->renderNumberField('typography', 'menuSize', $t->menuSize, TypographySettings::AUX_MIN, TypographySettings::AUX_MAX, 0.025, __('Entre 0.6 et 1.4.', 'oli-theme')),
            $page,
            $section,
        );
        add_settings_field(
            'oli_typography_footer',
            __('Taille du pied de page (rem)', 'oli-theme') . $helpTypo,
            fn () => $this->renderNumberField('typography', 'footerSize', $t->footerSize, TypographySettings::AUX_MIN, TypographySettings::AUX_MAX, 0.025, __('Entre 0.6 et 1.4.', 'oli-theme')),
            $page,
            $section,
        );
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function sanitizeTypography(array $input): array
    {
        $t = TypographySettings::fromInput($input);

        return [
            'baseSize'   => $t->baseSize,
            'scaleRatio' => $t->scaleRatio,
            'menuSize'   => $t->menuSize,
            'footerSize' => $t->footerSize,
        ];
    }

    private function renderNumberField(string $section, string $key, float $value, float $min, float $max, float $step, string $description): void
    {
        $name = self::OPTION . '[' . $section . '][' . $key . ']';
        $id   = 'oli-' . $section . '-' . $key;
        printf(
            '<input type="number" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s" class="small-text" />',
            esc_attr($id),
            esc_attr($name),
            esc_attr((string) $value),
            esc_attr((string) $min),
            esc_attr((string) $max),
            esc_attr((string) $step),
        );
        if ($description !== '') {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }
}
