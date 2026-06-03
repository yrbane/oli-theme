<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * Réglages de typographie globaux du thème.
 *
 * Toutes les tailles sont en `rem` (16 px = 1 rem par défaut). Le ratio
 * d'échelle pilote la progression `h6 → h1` selon une suite géométrique :
 * `h{n} = base * ratio^(7-n)` (h6=base*ratio, h5=base*ratio², …, h1=base*ratio⁶).
 *
 * @package OliTheme\Settings
 *
 * @since 1.2.0
 */
final readonly class TypographySettings
{
    /** Valeur min/max acceptée pour la taille de base (en rem). */
    public const BASE_MIN  = 0.75;
    public const BASE_MAX  = 1.5;

    /** Valeur min/max acceptée pour le ratio d'échelle. */
    public const RATIO_MIN = 1.05;
    public const RATIO_MAX = 1.6;

    /** Valeur min/max acceptée pour les tailles auxiliaires (menu, footer). */
    public const AUX_MIN = 0.6;
    public const AUX_MAX = 1.4;

    public function __construct(
        public float $baseSize = 1.0,
        public float $scaleRatio = 1.2,
        public float $menuSize = 0.875,
        public float $footerSize = 0.875,
    ) {
    }

    /**
     * Valeurs par défaut neutres pour un nouveau site.
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Sanitize une entrée brute (typiquement issue d'un formulaire Settings API)
     * en clampant chaque valeur dans les bornes acceptées.
     *
     * @param array<string, mixed> $input
     */
    public static function fromInput(array $input): self
    {
        return new self(
            baseSize:   self::clamp((float) ($input['baseSize']   ?? 1.0),   self::BASE_MIN,  self::BASE_MAX),
            scaleRatio: self::clamp((float) ($input['scaleRatio'] ?? 1.2),   self::RATIO_MIN, self::RATIO_MAX),
            menuSize:   self::clamp((float) ($input['menuSize']   ?? 0.875), self::AUX_MIN,   self::AUX_MAX),
            footerSize: self::clamp((float) ($input['footerSize'] ?? 0.875), self::AUX_MIN,   self::AUX_MAX),
        );
    }

    /**
     * Produit les déclarations CSS à injecter dans le head pour matérialiser
     * la palette typographique. Format : un sélecteur :root avec toutes les
     * custom-properties nécessaires.
     */
    public function toCss(): string
    {
        $base  = $this->baseSize;
        $ratio = $this->scaleRatio;
        $h6 = $base * $ratio;
        $h5 = $h6 * $ratio;
        $h4 = $h5 * $ratio;
        $h3 = $h4 * $ratio;
        $h2 = $h3 * $ratio;
        $h1 = $h2 * $ratio;

        return \sprintf(
            ':root{--font-size-base:%srem;--font-size-h1:%srem;--font-size-h2:%srem;--font-size-h3:%srem;--font-size-h4:%srem;--font-size-h5:%srem;--font-size-h6:%srem;--font-size-menu:%srem;--font-size-footer:%srem;}',
            self::fmt($base),
            self::fmt($h1),
            self::fmt($h2),
            self::fmt($h3),
            self::fmt($h4),
            self::fmt($h5),
            self::fmt($h6),
            self::fmt($this->menuSize),
            self::fmt($this->footerSize),
        );
    }

    private static function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    private static function fmt(float $value): string
    {
        // 3 chiffres significatifs suffisent et évitent les longues queues de décimales.
        return rtrim(rtrim(\sprintf('%.3f', $value), '0'), '.');
    }
}
