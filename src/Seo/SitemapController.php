<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Events\EventModelInterface;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModelInterface;

/**
 * Génère les sitemaps XML multilingues du thème.
 *
 * Produit un index de sitemaps et des sous-sitemaps par type de contenu et par langue.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class SitemapController implements SitemapControllerInterface
{
    public function __construct(
        private readonly LanguageRegistryInterface $registry,
        private readonly PostModelInterface $posts,
        private readonly EventModelInterface $events,
        private readonly SitemapEntryBuilder $entryBuilder,
        private readonly SitemapIndexBuilder $indexBuilder,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getIndex(): string
    {
        $urls = [];

        foreach ($this->registry->all() as $language) {
            foreach (['post', 'page', 'oli_event'] as $type) {
                $urls[] = home_url(\sprintf('/sitemap-%s-%s.xml', $type, $language->code));
            }
        }

        return $this->indexBuilder->build($urls);
    }

    /**
     * {@inheritDoc}
     */
    public function getSubsitemap(string $type, Language $language): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        $entities = match ($type) {
            'oli_event' => $this->events->findUpcoming($language, 1000),
            default => $this->posts->findByLanguage($language, 1000, $type),
        };

        foreach ($entities as $entity) {
            $loc = $entity->permalink;
            $lastmod = $entity instanceof PostEntity
                ? ($entity->updatedAt?->format('c') ?? $entity->publishedAt->format('c'))
                : $entity->startDate->format('c');

            $xml .= $this->entryBuilder->build(
                loc: $loc,
                lastmod: $lastmod,
                changefreq: 'weekly',
                priority: 0.7,
                hreflangs: [],
            );
        }

        $xml .= '</urlset>' . "\n";

        return $xml;
    }
}
