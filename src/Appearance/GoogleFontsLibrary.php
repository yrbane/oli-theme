<?php

declare(strict_types=1);

namespace OliTheme\Appearance;

/**
 * Catalogue complet des Google Fonts (~1900 familles).
 *
 * Le catalogue est bundlé en local (`assets/data/google-fonts.json`) — pas de
 * dépendance réseau côté thème. Pour rafraîchir la liste, voir
 * `bin/refresh-google-fonts.php` (ou re-télécharger
 * https://fonts.google.com/metadata/fonts puis filtrer la propriété `family`).
 *
 * @package OliTheme\Appearance
 *
 * @since 1.0.0
 */
final class GoogleFontsLibrary
{
    private const DEFAULT_CATALOG_PATH = __DIR__ . '/../../assets/data/google-fonts.json';

    /** @var list<array{family: string, category: string}>|null */
    private ?array $cache = null;

    public function __construct(private readonly ?string $catalogPath = null)
    {
    }

    /**
     * Liste de toutes les polices Google Fonts connues, triées par nom.
     *
     * @return list<array{family: string, category: string}>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $path = $this->catalogPath ?? self::DEFAULT_CATALOG_PATH;
        if (!is_file($path)) {
            return $this->cache = [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return $this->cache = [];
        }

        $data = json_decode($raw, true);
        if (!\is_array($data)) {
            return $this->cache = [];
        }

        $out = [];
        foreach ($data as $entry) {
            if (!\is_array($entry) || empty($entry['family']) || !\is_string($entry['family'])) {
                continue;
            }
            $out[] = [
                'family'   => $entry['family'],
                'category' => \is_string($entry['category'] ?? null) ? $entry['category'] : 'Sans Serif',
            ];
        }

        return $this->cache = $out;
    }

    /**
     * Vérifie qu'une famille existe dans le catalogue (sécurité côté admin).
     */
    public function has(string $family): bool
    {
        foreach ($this->all() as $font) {
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
        // Google Fonts utilise `+` au lieu de `%20` pour les espaces.
        $encoded = str_replace('%20', '+', rawurlencode($family));

        return 'https://fonts.googleapis.com/css2?family=' . $encoded . ':wght@400;500;700&display=swap';
    }
}
