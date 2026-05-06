<?php

declare(strict_types=1);

namespace OliTheme\Appearance;

/**
 * Sous-page d'administration « Apparence > Variations CSS ».
 *
 * Permet à l'utilisateur de :
 *   - choisir une variation parmi celles déposées dans `assets/css/variations/` ;
 *   - choisir une image de bandeau pour les pages internes (utilisée par les
 *     variations qui exposent la custom-property `--oli-internal-banner-url`,
 *     comme Olikalari).
 *
 * @package OliTheme\Appearance
 *
 * @since 1.0.0
 */
final class ThemeVariationPage
{
    public const OPTION = 'oli_theme_variation';

    public const OPTION_BANNER = 'oli_internal_banner_image';

    public const OPTION_TITLES_FONT = 'oli_theme_titles_font';

    public const GROUP = 'oli_theme_variation_group';

    public const PAGE_SLUG = 'oli-theme-variations';

    public function __construct(
        private readonly ThemeVariationRegistry $registry,
        private readonly GoogleFontsLibrary $fonts = new GoogleFontsLibrary(),
    ) {
    }

    /**
     * À brancher sur `admin_menu`.
     */
    public function register(): void
    {
        add_theme_page(
            __('Variations CSS', 'oli-theme'),
            __('Variations CSS', 'oli-theme'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
        );
    }

    /**
     * À brancher sur `admin_init`.
     */
    public function registerSettings(): void
    {
        register_setting(self::GROUP, self::OPTION, [
            'type'              => 'string',
            'sanitize_callback' => [$this, 'sanitize'],
            'default'           => '',
        ]);

        register_setting(self::GROUP, self::OPTION_BANNER, [
            'type'              => 'string',
            'sanitize_callback' => [$this, 'sanitizeBanner'],
            'default'           => '',
        ]);

        register_setting(self::GROUP, self::OPTION_TITLES_FONT, [
            'type'              => 'string',
            'sanitize_callback' => [$this, 'sanitizeFont'],
            'default'           => '',
        ]);
    }

    /**
     * Sanitize : ne garde la valeur que si elle correspond à une variation existante.
     */
    public function sanitize(mixed $value): string
    {
        if (!\is_string($value) || $value === '') {
            return '';
        }

        $value = sanitize_key($value);

        return $this->registry->has($value) ? $value : '';
    }

    /**
     * Sanitize de l'URL du bandeau : esc_url_raw, vide accepté.
     */
    public function sanitizeBanner(mixed $value): string
    {
        if (!\is_string($value) || $value === '') {
            return '';
        }

        return (string) esc_url_raw($value);
    }

    /**
     * Sanitize de la police : doit être présente dans la liste blanche
     * (sécurité contre injection CSS via le nom de famille).
     */
    public function sanitizeFont(mixed $value): string
    {
        if (!\is_string($value) || $value === '') {
            return '';
        }

        $value = sanitize_text_field($value);

        return $this->fonts->has($value) ? $value : '';
    }

    public function render(): void
    {
        // Charge le media uploader WP nécessaire au picker d'image (bouton
        // « Choisir une image »). Appelé ici plutôt que sur admin_enqueue_scripts
        // pour ne pas dépendre de la comparaison du hook_suffix : à ce stade
        // les scripts sont imprimés en footer, donc l'enqueue tardif fonctionne.
        if (\function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        $current     = (string) get_option(self::OPTION, '');
        $bannerUrl   = (string) get_option(self::OPTION_BANNER, '');
        $titlesFont  = (string) get_option(self::OPTION_TITLES_FONT, '');
        $variations  = $this->registry->all();
        $allFonts    = $this->fonts->all();

        // URL de l'image par défaut du thème (utilisée comme preview tant que
        // l'admin n'a pas configuré une image personnalisée). Permet à l'utilisateur
        // de voir ce qui sera affiché côté front quand l'option est vide.
        $defaultBannerUrl = \function_exists('get_template_directory_uri')
            ? rtrim((string) get_template_directory_uri(), '/') . '/assets/img/banner.jpg'
            : '';
        $isCustom    = $bannerUrl !== '';
        $previewUrl  = $isCustom ? $bannerUrl : $defaultBannerUrl;
        $previewLabel = $isCustom
            ? __('Image personnalisée', 'oli-theme')
            : __('Image par défaut du thème', 'oli-theme');

        echo '<div class="wrap oli-variations">';
        echo '<h1>' . esc_html__('Variations CSS du thème', 'oli-theme') . '</h1>';
        echo '<p class="description">';
        echo esc_html__(
            'Déposez vos variations dans le dossier assets/css/variations/ du thème (un fichier .css par variation). Le CSS sélectionné est chargé après main.css pour l\'overrider.',
            'oli-theme',
        );
        echo '</p>';

        if ($variations === []) {
            echo '<div class="notice notice-info inline"><p>';
            echo esc_html__('Aucune variation détectée. Ajoutez un fichier .css dans assets/css/variations/.', 'oli-theme');
            echo '</p></div>';
        }

        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);

        echo '<table class="form-table" role="presentation"><tbody>';

        // --- Variation active ---
        echo '<tr>';
        echo '<th scope="row"><label for="oli-theme-variation">' . esc_html__('Variation active', 'oli-theme') . '</label></th>';
        echo '<td>';
        echo '<select id="oli-theme-variation" name="' . esc_attr(self::OPTION) . '">';
        echo '<option value=""' . selected($current, '', false) . '>' . esc_html__('— Aucune (CSS de base) —', 'oli-theme') . '</option>';
        foreach ($variations as $variation) {
            echo '<option value="' . esc_attr($variation['id']) . '"' . selected($current, $variation['id'], false) . '>';
            echo esc_html($variation['label']);
            echo '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Le fichier sera enqueué après main.css avec une dépendance sur celui-ci.', 'oli-theme') . '</p>';
        echo '</td></tr>';

        // --- Bandeau pages internes ---
        echo '<tr>';
        echo '<th scope="row"><label for="oli-internal-banner-url">' . esc_html__('Bandeau pages internes', 'oli-theme') . '</label></th>';
        echo '<td>';
        echo '<div class="oli-banner-picker" data-default-url="' . esc_attr($defaultBannerUrl) . '">';

        // Preview : toujours visible. Affiche l'image personnalisée si définie,
        // sinon l'image par défaut du thème pour donner un aperçu à l'utilisateur.
        echo '<figure id="oli-internal-banner-figure" style="margin:0 0 0.75rem;max-width:560px;">';
        echo '<img id="oli-internal-banner-preview" src="' . esc_attr($previewUrl) . '" alt="" style="display:block;width:100%;height:auto;background:#f0f0f1;border:1px solid #dcdcde;border-radius:3px;">';
        echo '<figcaption id="oli-internal-banner-caption" style="font-size:0.8125em;color:#646970;margin-top:0.4rem;">';
        echo '<span id="oli-internal-banner-badge" class="oli-banner-badge" data-state="' . ($isCustom ? 'custom' : 'default') . '" style="display:inline-block;padding:1px 8px;border-radius:10px;font-weight:600;background:' . ($isCustom ? '#dcfce7' : '#f0f0f1') . ';color:' . ($isCustom ? '#166534' : '#646970') . ';margin-right:0.5rem;">' . esc_html($previewLabel) . '</span>';
        echo '<span id="oli-internal-banner-filename">' . esc_html(basename($previewUrl)) . '</span>';
        echo '</figcaption>';
        echo '</figure>';

        echo '<input type="url" id="oli-internal-banner-url" name="' . esc_attr(self::OPTION_BANNER) . '" value="' . esc_attr($bannerUrl) . '" class="regular-text code" placeholder="https://exemple.fr/wp-content/uploads/...jpg" />';
        echo ' <button type="button" class="button" id="oli-internal-banner-pick">' . esc_html__('Choisir une image', 'oli-theme') . '</button>';
        echo ' <button type="button" class="button-link" id="oli-internal-banner-clear" style="margin-left:0.5rem;color:#b32d2e;">' . esc_html__('Retirer (revenir au défaut)', 'oli-theme') . '</button>';
        echo '</div>';
        echo '<p class="description">' . esc_html__('Affiché en haut des pages autres que la home (dans les variations qui le supportent, ex. Olikalari). Si vide, le thème utilise son image par défaut affichée ci-dessus.', 'oli-theme') . '</p>';
        echo '</td></tr>';

        // --- Police des titres ---
        echo '<tr>';
        echo '<th scope="row"><label for="oli-titles-font">' . esc_html__('Police des titres', 'oli-theme') . '</label></th>';
        echo '<td>';
        echo '<div class="oli-font-picker">';

        // Combobox HTML5 : <input> + <datalist> couvre les ~1900 familles
        // sans ralentir le navigateur. Autocomplete natif au cours de la saisie.
        echo '<input type="text" id="oli-titles-font" name="' . esc_attr(self::OPTION_TITLES_FONT) . '"'
            . ' value="' . esc_attr($titlesFont) . '"'
            . ' list="oli-titles-font-list"'
            . ' class="regular-text"'
            . ' placeholder="' . esc_attr__('Tapez pour chercher (ex. Bricolage Grotesque)…', 'oli-theme') . '"'
            . ' autocomplete="off"'
            . ' style="display:block;width:100%;max-width:32rem;">';
        echo '<datalist id="oli-titles-font-list">';
        foreach ($allFonts as $font) {
            echo '<option value="' . esc_attr($font['family']) . '">'
                . esc_html($font['family'] . ' — ' . $font['category'])
                . '</option>';
        }
        echo '</datalist>';

        // Boutons rapides : effacer + suggérer.
        echo '<div style="margin-top:0.5rem;">';
        echo '<button type="button" class="button-link" id="oli-titles-font-clear" style="color:#b32d2e;">'
            . esc_html__('Effacer (revenir à la police du thème)', 'oli-theme') . '</button>';
        echo '</div>';

        // Preview live (la police est chargée dynamiquement par le JS).
        echo '<div id="oli-titles-font-preview" style="margin-top:1rem;padding:1.25rem;background:#fff;border:1px solid #dcdcde;border-radius:3px;max-width:32rem;">';
        echo '<div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:#646970;margin-bottom:0.5rem;">'
            . esc_html__('Aperçu', 'oli-theme') . ' — <span id="oli-titles-font-name">'
            . esc_html($titlesFont !== '' ? $titlesFont : __('Police par défaut', 'oli-theme')) . '</span>';
        echo '<span id="oli-titles-font-status" style="margin-left:0.5rem;font-weight:400;color:#a7aaad;"></span>';
        echo '</div>';
        echo '<div id="oli-titles-font-sample" style="font-size:1.75rem;line-height:1.2;font-weight:700;color:#111;">The quick brown fox</div>';
        echo '<div style="font-size:1rem;color:#444;margin-top:0.25rem;" id="oli-titles-font-sample-2">jumps over the lazy dog 0123456789</div>';
        echo '</div>';
        echo '</div>';
        echo '<p class="description">' . esc_html__('Catalogue complet Google Fonts (~1900 polices). S\'applique à h1–h6, au nom du site dans la barre de menu (.banner__title) et au titre du carousel d\'accueil. Police chargée dynamiquement.', 'oli-theme') . '</p>';
        echo '</td></tr>';

        echo '</tbody></table>';

        submit_button();
        echo '</form>';

        // JS : ouvre le media uploader WP au clic.
        $this->renderMediaUploaderScript();

        echo '</div>';
    }

    /**
     * Inline JS : intègre le media uploader WP pour le picker d'image.
     */
    private function renderMediaUploaderScript(): void
    {
        ?>
        <script>
        (function () {
            // ⚠️ Ne PAS sortir si wp.media n'est pas encore défini : ses scripts
            // peuvent être enqueués en footer, donc indisponibles au moment où
            // ce <script> inline s'exécute. On vérifie wp.media dans le handler
            // de clic qui, lui, tournera après le DOMContentLoaded.
            const wrap     = document.querySelector('.oli-banner-picker');
            const pick     = document.getElementById('oli-internal-banner-pick');
            const clear    = document.getElementById('oli-internal-banner-clear');
            const input    = document.getElementById('oli-internal-banner-url');
            const preview  = document.getElementById('oli-internal-banner-preview');
            const badge    = document.getElementById('oli-internal-banner-badge');
            const filename = document.getElementById('oli-internal-banner-filename');
            if (!wrap || !pick || !input) { return; }

            const defaultUrl    = wrap.dataset.defaultUrl || '';
            const labelCustom   = '<?php echo esc_js(__('Image personnalisée', 'oli-theme')); ?>';
            const labelDefault  = '<?php echo esc_js(__('Image par défaut du thème', 'oli-theme')); ?>';

            const setBadgeState = function (state) {
                if (!badge) return;
                badge.dataset.state = state;
                badge.textContent = state === 'custom' ? labelCustom : labelDefault;
                if (state === 'custom') {
                    badge.style.background = '#dcfce7';
                    badge.style.color = '#166534';
                } else {
                    badge.style.background = '#f0f0f1';
                    badge.style.color = '#646970';
                }
            };

            const updatePreview = function (url, isCustom) {
                if (preview) preview.src = url;
                if (filename) filename.textContent = url ? url.split('/').pop() : '';
                setBadgeState(isCustom ? 'custom' : 'default');
            };

            let frame;
            pick.addEventListener('click', function (e) {
                e.preventDefault();
                if (typeof wp === 'undefined' || !wp.media) {
                    console.error('[oli-theme] wp.media indisponible — wp_enqueue_media() n\'a pas été appelé.');
                    alert('<?php echo esc_js(__('Impossible d\'ouvrir la médiathèque (wp.media indisponible). Rechargez la page.', 'oli-theme')); ?>');
                    return;
                }
                if (!frame) {
                    frame = wp.media({
                        title: '<?php echo esc_js(__('Choisir une image de bandeau', 'oli-theme')); ?>',
                        button: { text: '<?php echo esc_js(__('Utiliser cette image', 'oli-theme')); ?>' },
                        library: { type: 'image' },
                        multiple: false,
                    });
                    frame.on('select', function () {
                        const att = frame.state().get('selection').first().toJSON();
                        const url = att.url || '';
                        input.value = url;
                        updatePreview(url, true);
                    });
                }
                frame.open();
            });

            if (clear) {
                clear.addEventListener('click', function () {
                    input.value = '';
                    updatePreview(defaultUrl, false);
                });
            }
        })();
        </script>

        <script>
        // Picker de police Google Fonts (combobox HTML5 input + datalist).
        // Charge dynamiquement la police pour la preview au cours de la saisie.
        (function () {
            const input   = document.getElementById('oli-titles-font');
            const clear   = document.getElementById('oli-titles-font-clear');
            const list    = document.getElementById('oli-titles-font-list');
            const sample  = document.getElementById('oli-titles-font-sample');
            const sample2 = document.getElementById('oli-titles-font-sample-2');
            const name    = document.getElementById('oli-titles-font-name');
            const status  = document.getElementById('oli-titles-font-status');
            if (!input || !sample) { return; }

            const known = new Set();
            if (list) {
                Array.from(list.options).forEach(function (o) { known.add(o.value); });
            }

            const loaded = new Set();
            const loadFont = function (family) {
                if (!family || loaded.has(family)) return;
                loaded.add(family);
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://fonts.googleapis.com/css2?family='
                    + family.replace(/\s+/g, '+') + ':wght@400;500;700&display=swap';
                document.head.appendChild(link);
            };

            const applyPreview = function (family) {
                if (family) {
                    loadFont(family);
                    sample.style.fontFamily = '"' + family + '", system-ui, sans-serif';
                    if (sample2) sample2.style.fontFamily = '"' + family + '", system-ui, sans-serif';
                    if (name) name.textContent = family;
                    if (status) {
                        status.textContent = known.has(family) ? '' : '⚠️ Police inconnue dans le catalogue';
                        status.style.color = known.has(family) ? '' : '#b32d2e';
                    }
                } else {
                    sample.style.fontFamily = '';
                    if (sample2) sample2.style.fontFamily = '';
                    if (name) name.textContent = 'Police par défaut';
                    if (status) status.textContent = '';
                }
            };

            // Preview initial
            applyPreview(input.value.trim());

            // Debounce pour ne pas re-charger la font à chaque frappe
            let debounce;
            input.addEventListener('input', function () {
                clearTimeout(debounce);
                debounce = setTimeout(function () {
                    applyPreview(input.value.trim());
                }, 250);
            });
            // Sélection dans la datalist → change immédiat
            input.addEventListener('change', function () {
                applyPreview(input.value.trim());
            });

            if (clear) {
                clear.addEventListener('click', function () {
                    input.value = '';
                    applyPreview('');
                });
            }
        })();
        </script>
        <?php
    }
}
