<?php

declare(strict_types=1);

namespace OliTheme\Appearance;

/**
 * Sous-page d'administration « Apparence > Variations CSS ».
 *
 * Permet à l'utilisateur de choisir une variation parmi celles déposées dans
 * `assets/css/variations/`. La variation sélectionnée est enqueuée par
 * `Core\AssetManager` après le CSS principal pour l'overrider.
 *
 * @package OliTheme\Appearance
 *
 * @since 1.0.0
 */
final class ThemeVariationPage
{
    public const OPTION = 'oli_theme_variation';

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

    public function render(): void
    {
        $current    = (string) get_option(self::OPTION, '');
        $variations = $this->registry->all();

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

        echo '<table class="form-table" role="presentation"><tbody><tr>';
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
        echo '</td></tr></tbody></table>';

        submit_button();
        echo '</form>';
        echo '</div>';
    }
}
