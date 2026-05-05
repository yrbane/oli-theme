<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Calcule un score de lisibilité Flesch adapté au français
 * (coefficients Kandel & Moles 1958).
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class ReadabilityAnalyzer
{
    public function score(string $text): int
    {
        $plain = trim(strip_tags($text));
        if ($plain === '') {
            return 0;
        }

        $sentences = $this->countSentences($plain);
        $words = $this->countWords($plain);
        $syllables = $this->countSyllables($plain);

        if ($sentences === 0 || $words === 0) {
            return 0;
        }

        $flesch = 207 - 1.015 * ($words / $sentences) - 73.6 * ($syllables / $words);
        return (int) max(0, min(100, round($flesch)));
    }

    private function countSentences(string $text): int
    {
        $matches = preg_split('/[.!?]+/u', $text, -1, \PREG_SPLIT_NO_EMPTY);
        return \is_array($matches) ? \count($matches) : 0;
    }

    private function countWords(string $text): int
    {
        $matches = preg_split('/\s+/u', trim($text), -1, \PREG_SPLIT_NO_EMPTY);
        return \is_array($matches) ? \count($matches) : 0;
    }

    private function countSyllables(string $text): int
    {
        // Approximation : nombre de groupes de voyelles dans le mot.
        // Suffisant pour un score Flesch indicatif (±5%).
        $words = preg_split('/\s+/u', mb_strtolower(trim($text)), -1, \PREG_SPLIT_NO_EMPTY);
        if (! \is_array($words)) {
            return 0;
        }

        $total = 0;
        foreach ($words as $word) {
            $word = preg_replace('/[^a-zàâäéèêëîïôöùûüÿæœ]/u', '', $word) ?? '';
            if ($word === '') {
                continue;
            }

            // Compte les groupes de voyelles consécutives.
            $count = (int) preg_match_all('/[aeiouyàâäéèêëîïôöùûüÿæœ]+/u', $word);

            // 'e' final muet : retirer 1 syllabe si le mot finit par 'e' (et n'est pas seul).
            if ($count > 1 && str_ends_with($word, 'e')) {
                $count--;
            }

            $total += max(1, $count);
        }

        return $total;
    }
}
