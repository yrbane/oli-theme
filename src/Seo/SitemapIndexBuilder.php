<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Construit le fichier XML d'index de sitemap (sitemapindex).
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class SitemapIndexBuilder
{
    /**
     * Construit l'index XML listant les sous-sitemaps.
     *
     * @param string[] $sitemapUrls Liste des URLs des sous-sitemaps.
     */
    public function build(array $sitemapUrls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemapUrls as $url) {
            $xml .= '  <sitemap><loc>' . htmlspecialchars($url, \ENT_XML1) . '</loc></sitemap>' . "\n";
        }

        $xml .= '</sitemapindex>' . "\n";

        return $xml;
    }
}
