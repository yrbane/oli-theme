<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Audite les images d'un contenu HTML pour détecter les problèmes d'accessibilité SEO.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class ImageAuditor
{
    /**
     * Retourne la liste des problèmes détectés sur les balises img.
     *
     * @return array<int, array{src: string, issue: string}>
     */
    public function audit(string $html): array
    {
        $issues = [];
        if (preg_match_all('/<img[^>]*>/si', $html, $m) === false) {
            return $issues;
        }
        foreach ($m[0] as $tag) {
            $src = '';
            if (preg_match('/src=["\']([^"\']+)["\']/i', $tag, $sm) === 1) {
                $src = $sm[1];
            }
            $hasAlt = preg_match('/\salt=["\']([^"\']*)["\']/i', $tag, $am) === 1;
            $altValue = $hasAlt ? trim($am[1]) : null;

            if (! $hasAlt) {
                $issues[] = ['src' => $src, 'issue' => 'missing_alt'];
                continue;
            }
            if ($altValue === '') {
                $issues[] = ['src' => $src, 'issue' => 'empty_alt'];
            }
        }
        return $issues;
    }
}
