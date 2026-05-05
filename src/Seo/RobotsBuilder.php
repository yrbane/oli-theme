<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Construit la valeur de la balise robots à partir des métadonnées SEO.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class RobotsBuilder
{
    /**
     * Retourne la directive robots (ex. `index, follow` ou `noindex, nofollow`).
     *
     * @param SeoMeta $meta Métadonnées SEO du contenu.
     */
    public function build(SeoMeta $meta): string
    {
        $directives = [
            $meta->noindex ? 'noindex' : 'index',
            $meta->nofollow ? 'nofollow' : 'follow',
        ];

        return implode(', ', $directives);
    }
}
