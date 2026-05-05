<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * View-model immuable rassemblant tous les éléments du <head> SEO.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final readonly class SeoHeadViewModel
{
    /**
     * @param array<int, array{code: string, url: string}> $hreflangs
     * @param array<string, int|string> $og
     * @param array<string, string> $twitter
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $robots,
        public string $canonical,
        public array $hreflangs,
        public array $og,
        public array $twitter,
        public string $jsonLd,
    ) {
    }
}
