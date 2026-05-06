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

    public const GROUP = 'oli_theme_variation_group';

    public const PAGE_SLUG = 'oli-theme-variations';

    public function __construct(private readonly ThemeVariationRegistry $registry)
    {
    }

    /**
     * À brancher sur `admin_menu`.
     */
    public function register(): void
    {
        $hook = add_theme_page(
            __('Variations CSS', 'oli-theme'),
            __('Variations CSS', 'oli-theme'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
        );

        if (\is_string($hook) && $hook !== '') {
            add_action('admin_enqueue_scripts', function (string $current) use ($hook): void {
                if ($current === $hook) {
                    wp_enqueue_media();
                }
            });
        }
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

    public function render(): void
    {
        $current    = (string) get_option(self::OPTION, '');
        $bannerUrl  = (string) get_option(self::OPTION_BANNER, '');
        $variations = $this->registry->all();

        // URL de l'image par défaut du thème (utilisée comme preview tant que
        // l'admin n'a pas configuré une image personnalisée). Permet à l'utilisateur
        // de voir ce qui sera affiché côté front quand l'option est vide.
        $defaultBannerUrl = \function_exists('get_template_directory_uri')
            ? rtrim((string) get_template_directory_uri(), '/') . '/assets/img/channels4_banner.jpg'
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
            if (typeof wp === 'undefined' || !wp.media) { return; }
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
            pick.addEventListener('click', function () {
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
        <?php
    }
}
