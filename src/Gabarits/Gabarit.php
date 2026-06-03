<?php

declare(strict_types=1);

namespace OliTheme\Gabarits;

/**
 * Value object immuable représentant un gabarit (skin de présentation).
 *
 * Un gabarit est défini par un manifeste `assets/gabarits/{id}/manifest.json`
 * et fournit au minimum une feuille de style propre. Il peut optionnellement
 * embarquer un script JS (effets, parallaxe, scroll, etc.).
 *
 * @package OliTheme\Gabarits
 *
 * @since 1.4.0
 */
final readonly class Gabarit
{
    /**
     * @param list<string> $supports types de contenu supportés ('post', 'page', 'oli_event')
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public array $supports,
        public string $cssPath,
        public ?string $jsPath = null,
        public bool $parallax = false,
        public string $previewColor = '#1a1a1a',
    ) {
    }

    /**
     * Vrai si le gabarit accepte d'être appliqué à un post du type donné.
     */
    public function supportsType(string $postType): bool
    {
        return \in_array($postType, $this->supports, true);
    }
}
