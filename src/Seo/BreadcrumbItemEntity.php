<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * DTO immuable représentant un élément de fil d'Ariane.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final readonly class BreadcrumbItemEntity
{
    /**
     * @param string $label Libellé affiché de l'élément.
     * @param string $url URL de l'élément.
     * @param bool $isCurrent Indique si cet élément est la page courante.
     */
    public function __construct(
        public string $label,
        public string $url,
        public bool $isCurrent,
    ) {
    }
}
