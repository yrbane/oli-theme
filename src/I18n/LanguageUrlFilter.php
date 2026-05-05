<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Filtre `home_url` (et plus tard `get_permalink`) pour préfixer la langue courante.
 *
 * La langue par défaut n'est pas préfixée afin que `https://site/` reste l'URL canonique
 * de la home dans la langue par défaut.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final class LanguageUrlFilter
{
    public function __construct(
        private readonly LanguageRegistry $registry,
        private readonly LanguageResolver $resolver,
    ) {
    }

    /**
     * Filtre 'home_url'.
     */
    public function filterHomeUrl(string $url, string $path): string
    {
        $current = $this->resolver->current();

        if ($current->equals($this->registry->default())) {
            return $url;
        }

        $prefix = '/' . $current->code . '/';
        if (str_contains($url, $prefix)) {
            return $url;
        }

        $parsed = parse_url($url);
        if (!\is_array($parsed) || !isset($parsed['host'])) {
            return $url;
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'];
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $urlPath = $parsed['path'] ?? '/';

        if ($urlPath === '/' || $urlPath === '') {
            $newPath = $prefix;
        } else {
            $newPath = $prefix . ltrim($urlPath, '/');
        }

        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $scheme . '://' . $host . $port . $newPath . $query . $fragment;
    }
}
