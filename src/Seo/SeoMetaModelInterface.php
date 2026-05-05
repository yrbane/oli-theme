<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Contrat du modèle de lecture/écriture des métadonnées SEO.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
interface SeoMetaModelInterface
{
    /**
     * Charge les métadonnées SEO d'un post depuis les post metas WordPress.
     */
    public function find(int $postId): SeoMeta;

    /**
     * Persiste les métadonnées SEO d'un post dans les post metas WordPress.
     */
    public function save(int $postId, SeoMeta $meta): void;

    /**
     * Lit une meta brute avec valeur par défaut.
     */
    public function getMeta(int $postId, string $key, mixed $default = null): mixed;
}
