<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\I18n\Language;

/**
 * Contrat du contrôleur de génération de sitemaps XML.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
interface SitemapControllerInterface
{
    /**
     * Retourne le XML de l'index de sitemaps.
     */
    public function getIndex(): string;

    /**
     * Retourne le XML d'un sous-sitemap pour le type et la langue donnés.
     *
     * @param string $type Type de contenu WordPress (post, page, oli_event, ...).
     */
    public function getSubsitemap(string $type, Language $language): string;
}
