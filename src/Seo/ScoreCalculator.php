<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Posts\PostEntity;

/**
 * Calcule un score SEO global (0-100) pour un contenu WordPress.
 *
 * Le barème est configurable via le filtre `oli_seo_score_rules`.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class ScoreCalculator implements ScoreCalculatorInterface
{
    public function __construct(
        private readonly ReadabilityAnalyzer $readability,
        private readonly KeywordAnalyzer $keywords,
        private readonly ImageAuditor $images,
    ) {
    }

    /**
     * Calcule le score SEO d'un article selon ses métadonnées et son contenu.
     *
     * @param SeoMeta $meta Métadonnées SEO du contenu.
     * @param PostEntity $post Entité du contenu WordPress.
     *
     * @return int Score entre 0 et 100.
     */
    public function calculate(SeoMeta $meta, PostEntity $post): int
    {
        /** @var array<string, int> $rules */
        $rules = apply_filters('oli_seo_score_rules', [
            'title_length'            => 5,
            'title_has_keyword'       => 8,
            'description_length'      => 5,
            'description_has_keyword' => 6,
            'slug_has_keyword'        => 5,
            'has_h1_with_keyword'     => 5,
            'keyword_density'         => 6,
            'images_have_alt'         => 6,
            'flesch_above_60'         => 8,
            'word_count_above_300'    => 6,
            'canonical_set'           => 3,
            'og_image_set'            => 5,
        ]);

        $title = $meta->title ?? $post->title;
        $description = $meta->description ?? '';
        $keyword = $meta->focusKeyword ?? '';
        $sum = 0;

        // Longueur du titre : 30-65 caractères.
        $titleLen = mb_strlen($title);
        if ($titleLen >= 30 && $titleLen <= 65) {
            $sum += $rules['title_length'] ?? 0;
        }

        // Mot-clé dans le titre.
        if ($keyword !== '' && $this->keywords->inTitle($title, $keyword)) {
            $sum += $rules['title_has_keyword'] ?? 0;
        }

        // Longueur de la description : 120-158 caractères.
        $descLen = mb_strlen($description);
        if ($descLen >= 120 && $descLen <= 158) {
            $sum += $rules['description_length'] ?? 0;
        }

        // Mot-clé dans la description.
        if ($keyword !== '' && str_contains(mb_strtolower($description), mb_strtolower($keyword))) {
            $sum += $rules['description_has_keyword'] ?? 0;
        }

        // Mot-clé dans le slug.
        if ($keyword !== '' && $this->keywords->inSlug($post->slug, $keyword)) {
            $sum += $rules['slug_has_keyword'] ?? 0;
        }

        // Mot-clé dans le H1.
        if ($keyword !== '' && $this->keywords->inHeadings($post->content, $keyword)['h1']) {
            $sum += $rules['has_h1_with_keyword'] ?? 0;
        }

        // Densité du mot-clé : 0.5-2.5%.
        if ($keyword !== '') {
            $density = $this->keywords->densityIn($post->content, $keyword);
            if ($density >= 0.5 && $density <= 2.5) {
                $sum += $rules['keyword_density'] ?? 0;
            }
        }

        // Toutes les images ont un attribut alt valide.
        if ($this->images->audit($post->content) === []) {
            $sum += $rules['images_have_alt'] ?? 0;
        }

        // Score Flesch supérieur à 60.
        if ($this->readability->score($post->content) >= 60) {
            $sum += $rules['flesch_above_60'] ?? 0;
        }

        // Nombre de mots supérieur à 300.
        $wordCount = str_word_count(strip_tags($post->content));
        if ($wordCount >= 300) {
            $sum += $rules['word_count_above_300'] ?? 0;
        }

        // URL canonique renseignée.
        if ($meta->canonical !== null && $meta->canonical !== '') {
            $sum += $rules['canonical_set'] ?? 0;
        }

        // Image Open Graph renseignée.
        if ($meta->ogImageId !== null && $meta->ogImageId > 0) {
            $sum += $rules['og_image_set'] ?? 0;
        }

        $max = (int) array_sum($rules);
        if ($max === 0) {
            return 0;
        }
        return (int) round(($sum / $max) * 100);
    }
}
