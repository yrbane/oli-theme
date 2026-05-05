<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Analyse l'usage d'un mot-clé focus dans un contenu donné.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class KeywordAnalyzer
{
    public function densityIn(string $text, string $keyword): float
    {
        $text = mb_strtolower(strip_tags($text));
        $keyword = mb_strtolower(trim($keyword));
        if ($text === '' || $keyword === '') {
            return 0.0;
        }
        $words = preg_split('/\s+/u', trim($text), -1, \PREG_SPLIT_NO_EMPTY);
        $totalWords = \is_array($words) ? \count($words) : 0;
        if ($totalWords === 0) {
            return 0.0;
        }
        $occurrences = substr_count($text, $keyword);
        return round(($occurrences / $totalWords) * 100, 2);
    }

    public function inTitle(string $title, string $keyword): bool
    {
        return $this->contains($title, $keyword);
    }

    public function inSlug(string $slug, string $keyword): bool
    {
        // Le slug utilise des tirets — on les remplace par des espaces avant le test.
        return $this->contains(str_replace('-', ' ', $slug), $keyword);
    }

    public function inFirstParagraph(string $html, string $keyword): bool
    {
        if (preg_match('/<p[^>]*>(.*?)<\/p>/si', $html, $m) !== 1) {
            return false;
        }
        return $this->contains($m[1], $keyword);
    }

    /**
     * @return array{h1: bool, h2: bool, h3: bool}
     */
    public function inHeadings(string $html, string $keyword): array
    {
        return [
            'h1' => $this->headingContains($html, 'h1', $keyword),
            'h2' => $this->headingContains($html, 'h2', $keyword),
            'h3' => $this->headingContains($html, 'h3', $keyword),
        ];
    }

    private function headingContains(string $html, string $tag, string $keyword): bool
    {
        if (preg_match_all(\sprintf('/<%s[^>]*>(.*?)<\/%s>/si', $tag, $tag), $html, $m) === false) {
            return false;
        }
        foreach ($m[1] ?? [] as $heading) {
            if ($this->contains($heading, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function contains(string $haystack, string $needle): bool
    {
        $haystack = mb_strtolower(strip_tags($haystack));
        $needle = mb_strtolower(trim($needle));
        if ($needle === '') {
            return false;
        }
        return str_contains($haystack, $needle);
    }
}
