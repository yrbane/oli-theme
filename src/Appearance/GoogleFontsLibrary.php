<?php

declare(strict_types=1);

namespace OliTheme\Appearance;

/**
 * Bibliothèque curated de Google Fonts populaires (sans dépendance réseau).
 *
 * Suffit largement pour 95 % des cas. Si l'utilisateur veut une police absente
 * de cette liste, il peut éditer cette classe ou bien rajouter manuellement
 * dans l'option WP `oli_theme_titles_font` — le rendu côté front la chargera
 * tant que le nom est valide côté Google Fonts.
 *
 * @package OliTheme\Appearance
 *
 * @since 1.0.0
 */
final class GoogleFontsLibrary
{
    /**
     * Liste de polices populaires (top Google Fonts), triées par catégorie.
     *
     * @var list<array{family: string, category: string}>
     */
    private const FONTS = [
        // --- Sans-serif ---
        ['family' => 'Inter',              'category' => 'sans-serif'],
        ['family' => 'Manrope',            'category' => 'sans-serif'],
        ['family' => 'Roboto',             'category' => 'sans-serif'],
        ['family' => 'Open Sans',          'category' => 'sans-serif'],
        ['family' => 'Lato',               'category' => 'sans-serif'],
        ['family' => 'Montserrat',         'category' => 'sans-serif'],
        ['family' => 'Poppins',            'category' => 'sans-serif'],
        ['family' => 'Raleway',            'category' => 'sans-serif'],
        ['family' => 'Nunito',             'category' => 'sans-serif'],
        ['family' => 'Nunito Sans',        'category' => 'sans-serif'],
        ['family' => 'Ubuntu',             'category' => 'sans-serif'],
        ['family' => 'Work Sans',          'category' => 'sans-serif'],
        ['family' => 'Fira Sans',          'category' => 'sans-serif'],
        ['family' => 'IBM Plex Sans',      'category' => 'sans-serif'],
        ['family' => 'DM Sans',            'category' => 'sans-serif'],
        ['family' => 'Karla',              'category' => 'sans-serif'],
        ['family' => 'Mulish',             'category' => 'sans-serif'],
        ['family' => 'Rubik',              'category' => 'sans-serif'],
        ['family' => 'Quicksand',          'category' => 'sans-serif'],
        ['family' => 'Comfortaa',          'category' => 'sans-serif'],
        ['family' => 'Cabin',              'category' => 'sans-serif'],
        ['family' => 'PT Sans',            'category' => 'sans-serif'],
        ['family' => 'Source Sans 3',      'category' => 'sans-serif'],
        ['family' => 'Noto Sans',          'category' => 'sans-serif'],
        ['family' => 'Archivo',            'category' => 'sans-serif'],
        ['family' => 'Archivo Narrow',     'category' => 'sans-serif'],
        ['family' => 'Hind',               'category' => 'sans-serif'],
        ['family' => 'Space Grotesk',      'category' => 'sans-serif'],
        ['family' => 'Barlow',             'category' => 'sans-serif'],
        ['family' => 'Outfit',             'category' => 'sans-serif'],
        ['family' => 'Plus Jakarta Sans',  'category' => 'sans-serif'],

        // --- Serif ---
        ['family' => 'Playfair Display',   'category' => 'serif'],
        ['family' => 'Merriweather',       'category' => 'serif'],
        ['family' => 'Lora',               'category' => 'serif'],
        ['family' => 'PT Serif',           'category' => 'serif'],
        ['family' => 'Roboto Slab',        'category' => 'serif'],
        ['family' => 'Crimson Text',       'category' => 'serif'],
        ['family' => 'EB Garamond',        'category' => 'serif'],
        ['family' => 'Cormorant Garamond', 'category' => 'serif'],
        ['family' => 'Libre Baskerville',  'category' => 'serif'],
        ['family' => 'Bitter',             'category' => 'serif'],
        ['family' => 'IBM Plex Serif',     'category' => 'serif'],
        ['family' => 'DM Serif Display',   'category' => 'serif'],
        ['family' => 'Source Serif 4',     'category' => 'serif'],
        ['family' => 'Noto Serif',         'category' => 'serif'],
        ['family' => 'Spectral',           'category' => 'serif'],
        ['family' => 'Vollkorn',           'category' => 'serif'],

        // --- Display ---
        ['family' => 'Anton',              'category' => 'display'],
        ['family' => 'Bebas Neue',         'category' => 'display'],
        ['family' => 'Oswald',             'category' => 'display'],
        ['family' => 'Abril Fatface',      'category' => 'display'],
        ['family' => 'Righteous',          'category' => 'display'],

        // --- Handwriting ---
        ['family' => 'Pacifico',           'category' => 'handwriting'],
        ['family' => 'Dancing Script',     'category' => 'handwriting'],
        ['family' => 'Caveat',             'category' => 'handwriting'],
        ['family' => 'Sacramento',         'category' => 'handwriting'],
        ['family' => 'Permanent Marker',   'category' => 'handwriting'],
        ['family' => 'Indie Flower',       'category' => 'handwriting'],

        // --- Monospace ---
        ['family' => 'Roboto Mono',        'category' => 'monospace'],
        ['family' => 'Fira Code',          'category' => 'monospace'],
        ['family' => 'JetBrains Mono',     'category' => 'monospace'],
        ['family' => 'IBM Plex Mono',      'category' => 'monospace'],
        ['family' => 'Source Code Pro',    'category' => 'monospace'],
        ['family' => 'Inconsolata',        'category' => 'monospace'],
        ['family' => 'Space Mono',         'category' => 'monospace'],
    ];

    /**
     * @return list<array{family: string, category: string}>
     */
    public function all(): array
    {
        return self::FONTS;
    }

    /**
     * Vérifie qu'une famille est dans la liste blanche (sécurité côté admin).
     */
    public function has(string $family): bool
    {
        foreach (self::FONTS as $font) {
            if ($font['family'] === $family) {
                return true;
            }
        }

        return false;
    }

    /**
     * URL `https://fonts.googleapis.com/css2?...` pour charger une famille.
     * Inclut les graisses 400/500/700 (les plus utilisées pour les titres).
     */
    public function cssUrlFor(string $family): string
    {
        $encoded = str_replace('+', '+', rawurlencode($family));

        // L'URL Google Fonts utilise `+` au lieu de `%20`.
        $encoded = str_replace('%20', '+', $encoded);

        return 'https://fonts.googleapis.com/css2?family=' . $encoded . ':wght@400;500;700&display=swap';
    }
}
