<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Filtre les URL générées par WordPress pour préfixer la langue active.
 *
 * - `home_url` : préfixe la home par la langue active si non-défaut.
 * - `page_link` / `post_link` / `post_type_link` : préfixe les permaliens
 *   internes pour que la navigation conserve la langue choisie.
 *
 * La langue par défaut n'est jamais préfixée afin que `https://site/` reste
 * l'URL canonique de la home dans la langue par défaut. Les URL admin et
 * login ne sont pas préfixées (jamais routées par les rewrite rules custom).
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
     * Filtre `home_url` : ajoute le préfixe `/{lang}/` quand la langue active
     * n'est pas la langue par défaut.
     */
    public function filterHomeUrl(string $url, string $path): string
    {
        return $this->prefixUrl($url);
    }

    /**
     * Filtre `page_link` / `post_link` / `post_type_link` : préfixe les permaliens
     * pour conserver la langue active lors des navigations internes.
     */
    public function filterPermalink(string $url): string
    {
        return $this->prefixUrl($url);
    }

    /**
     * Logique commune : ajoute le préfixe `/{lang}/` à `$url` si pertinent,
     * sinon retourne l'URL telle quelle.
     */
    private function prefixUrl(string $url): string
    {
        $current = $this->resolver->current();

        if ($current->equals($this->registry->default())) {
            return $url;
        }

        if ($this->isAdminOrLoginUrl($url)) {
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

        $scheme  = $parsed['scheme'] ?? 'https';
        $host    = $parsed['host'];
        $port    = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $urlPath = $parsed['path'] ?? '/';

        $newPath = $urlPath === '' || $urlPath === '/'
            ? $prefix
            : $prefix . ltrim($urlPath, '/');

        $query    = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $scheme . '://' . $host . $port . $newPath . $query . $fragment;
    }

    /**
     * Détecte les URL d'administration ou de connexion (jamais préfixées).
     */
    private function isAdminOrLoginUrl(string $url): bool
    {
        return str_contains($url, '/wp-admin/')
            || str_contains($url, '/wp-login.php')
            || str_contains($url, '/wp-json/')
            || str_contains($url, '/feed/')
            || str_contains($url, 'xmlrpc.php');
    }
}
