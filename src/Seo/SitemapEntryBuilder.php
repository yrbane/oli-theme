<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Construit une entrée XML <url> pour un sitemap.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class SitemapEntryBuilder
{
    /**
     * Construit le fragment XML d'une URL de sitemap.
     *
     * @param string $loc URL de la ressource.
     * @param string|null $lastmod Date de dernière modification (format ISO 8601).
     * @param string|null $changefreq Fréquence de modification estimée.
     * @param float|null $priority Priorité relative (0.0 à 1.0).
     * @param array<string, string> $hreflangs Table de correspondance code langue → URL alternative.
     */
    public function build(
        string $loc,
        ?string $lastmod = null,
        ?string $changefreq = null,
        ?float $priority = null,
        array $hreflangs = [],
    ): string {
        $xml = '  <url>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($loc, \ENT_XML1) . '</loc>' . "\n";

        if ($lastmod !== null) {
            $xml .= '    <lastmod>' . htmlspecialchars($lastmod, \ENT_XML1) . '</lastmod>' . "\n";
        }

        if ($changefreq !== null) {
            $xml .= '    <changefreq>' . htmlspecialchars($changefreq, \ENT_XML1) . '</changefreq>' . "\n";
        }

        if ($priority !== null) {
            $xml .= '    <priority>' . number_format($priority, 1, '.', '') . '</priority>' . "\n";
        }

        foreach ($hreflangs as $code => $url) {
            $xml .= \sprintf(
                '    <xhtml:link rel="alternate" hreflang="%s" href="%s"/>' . "\n",
                htmlspecialchars((string) $code, \ENT_XML1),
                htmlspecialchars($url, \ENT_XML1),
            );
        }

        $xml .= '  </url>' . "\n";

        return $xml;
    }
}
