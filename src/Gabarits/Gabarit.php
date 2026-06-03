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
     * @param list<Zone>   $zones    architecture zonale du gabarit (vide = skin CSS pur)
     * @param ?string      $templateFsPath Chemin filesystem absolu vers `template.html.tpl` si présent.
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
        public array $zones = [],
        public ?string $templateFsPath = null,
    ) {
    }

    /**
     * Vrai si le gabarit accepte d'être appliqué à un post du type donné.
     */
    public function supportsType(string $postType): bool
    {
        return \in_array($postType, $this->supports, true);
    }

    /**
     * Vrai si le gabarit a une architecture zonale (ne se contente pas de CSS).
     */
    public function isZonal(): bool
    {
        return !empty($this->zones);
    }

    /**
     * Vrai si un template Lunar custom est associé (rendu serveur du gabarit).
     */
    public function hasCustomTemplate(): bool
    {
        return $this->templateFsPath !== null && is_file($this->templateFsPath);
    }

    public function zoneById(string $id): ?Zone
    {
        foreach ($this->zones as $z) {
            if ($z->id === $id) {
                return $z;
            }
        }
        return null;
    }
}
