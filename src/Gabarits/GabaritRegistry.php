<?php

declare(strict_types=1);

namespace OliTheme\Gabarits;

/**
 * Découvre les gabarits disponibles en scannant un dossier racine.
 *
 * Chaque sous-dossier de `$rootDir` qui contient un fichier `manifest.json`
 * est lu comme un gabarit. Le manifeste minimal :
 *
 * ```json
 * {
 *   "id": "magazine",                       // optionnel, sinon nom du dossier
 *   "name": "Magazine",
 *   "description": "Mise en page deux colonnes...",
 *   "supports": ["post", "page"],
 *   "parallax": false,
 *   "previewColor": "#1e3a8a"
 * }
 * ```
 *
 * Le fichier `style.css` à côté du manifeste est obligatoire (sinon le gabarit
 * est ignoré). `script.js` est optionnel et chargé en module ES si présent.
 *
 * @package OliTheme\Gabarits
 *
 * @since 1.4.0
 */
final class GabaritRegistry implements GabaritRegistryInterface
{
    /** Cache mémoire pour éviter de relire le disque à chaque appel. */
    private ?array $cache = null;

    public function __construct(
        private readonly string $rootDir,
        private readonly string $rootUri,
    ) {
    }

    /**
     * @return list<Gabarit>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $list = [];
        if (!is_dir($this->rootDir)) {
            return $this->cache = [];
        }
        $entries = scandir($this->rootDir);
        if ($entries === false) {
            return $this->cache = [];
        }
        sort($entries);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $dir = $this->rootDir . '/' . $entry;
            if (!is_dir($dir)) {
                continue;
            }
            $gabarit = $this->readManifest($entry, $dir);
            if ($gabarit !== null) {
                $list[] = $gabarit;
            }
        }
        return $this->cache = $list;
    }

    public function byId(string $id): ?Gabarit
    {
        foreach ($this->all() as $g) {
            if ($g->id === $id) {
                return $g;
            }
        }
        return null;
    }

    /**
     * @return list<Gabarit>
     */
    public function forType(string $postType): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (Gabarit $g): bool => $g->supportsType($postType),
        ));
    }

    private function readManifest(string $slug, string $dir): ?Gabarit
    {
        $manifestPath = $dir . '/manifest.json';
        $cssPath      = $dir . '/style.css';
        if (!is_file($manifestPath) || !is_file($cssPath)) {
            return null;
        }
        $rawJson = file_get_contents($manifestPath);
        if ($rawJson === false) {
            return null;
        }
        $manifest = json_decode($rawJson, true);
        if (!\is_array($manifest)) {
            return null;
        }
        $id          = (string) ($manifest['id'] ?? $slug);
        $jsRelative  = $dir . '/script.js';
        $jsUri       = is_file($jsRelative) ? $this->rootUri . '/' . $slug . '/script.js' : null;
        $supportsRaw = $manifest['supports'] ?? ['post', 'page', 'oli_event'];

        return new Gabarit(
            id:           $id,
            name:         (string) ($manifest['name']        ?? ucfirst($slug)),
            description:  (string) ($manifest['description'] ?? ''),
            supports:     \is_array($supportsRaw) ? array_values(array_map('strval', $supportsRaw)) : ['post', 'page'],
            cssPath:      $this->rootUri . '/' . $slug . '/style.css',
            jsPath:       $jsUri,
            parallax:     (bool) ($manifest['parallax']     ?? false),
            previewColor: (string) ($manifest['previewColor'] ?? '#1a1a1a'),
        );
    }
}
