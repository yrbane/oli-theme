<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Posts\PostEntity;

/**
 * Contrat du calcul de score SEO global pour un contenu WordPress.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
interface ScoreCalculatorInterface
{
    /**
     * Calcule le score SEO d'un contenu (0 à 100).
     */
    public function calculate(SeoMeta $meta, PostEntity $post): int;
}
