<?php

declare(strict_types=1);

namespace OliTheme\Appearance;

/**
 * Découvre les variations CSS du thème dans un dossier.
 *
 * Une variation est un fichier `*.css` à la racine du dossier `assets/css/variations/`.
 * Le label peut être déclaré via un commentaire d'en-tête :
 *
 *     /* Theme Variation: Mon nom lisible *\/
 *
 * Si l'en-tête est absent, le label est dérivé du nom de fichier
 * (`dark-mode.css` → « Dark mode »).
 *
 * @package OliTheme\Appearance
 *
 * @since 1.0.0
 */
final class ThemeVariationRegistry
{
    /** @var list<array{id: string, label: string, file: string}>|null */
    private ?array $cache = null;

    public function __construct(private readonly string $directory)
    {
    }

    /**
     * Liste les variations disponibles, triées alphabétiquement par label.
     *
     * @return list<array{id: string, label: string, file: string}>
     */
    public function all(): array
    {
        return $this->cache ??= $this->scan();
    }

    /**
     * Vérifie qu'une variation existe.
     */
    public function has(string $id): bool
    {
        foreach ($this->all() as $variation) {
            if ($variation['id'] === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne le nom de fichier (relatif au dossier) d'une variation, ou null.
     */
    public function fileNameFor(string $id): ?string
    {
        foreach ($this->all() as $variation) {
            if ($variation['id'] === $id) {
                return $variation['file'];
            }
        }

        return null;
    }

    /**
     * @return list<array{id: string, label: string, file: string}>
     */
    private function scan(): array
    {
        if (!is_dir($this->directory)) {
            return [];
        }

        $files = glob($this->directory . '/*.css');
        if (!\is_array($files)) {
            return [];
        }

        $variations = [];
        foreach ($files as $absolutePath) {
            $fileName = basename($absolutePath);
            if ($fileName === '' || $fileName[0] === '.') {
                continue;
            }

            $id = substr($fileName, 0, -4);
            if ($id === '') {
                continue;
            }

            $variations[] = [
                'id'    => $id,
                'label' => $this->readLabel($absolutePath, $id),
                'file'  => $fileName,
            ];
        }

        usort($variations, static fn ($a, $b) => strcmp($a['label'], $b['label']));

        return array_values($variations);
    }

    /**
     * Lit le label depuis l'en-tête du fichier ou le dérive du nom.
     */
    private function readLabel(string $absolutePath, string $fallbackId): string
    {
        $head = (string) file_get_contents($absolutePath, length: 512);
        if (preg_match('~/\*\s*Theme\s+Variation\s*:\s*(.+?)\s*\*/~i', $head, $matches) === 1) {
            return trim($matches[1]);
        }

        return ucfirst(str_replace(['-', '_'], ' ', $fallbackId));
    }
}
