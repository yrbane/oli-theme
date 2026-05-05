<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Events\EventEntity;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;

/**
 * Construit les balises Open Graph pour la balise <head>.
 *
 * Retourne un tableau associatif `property => content` prêt à être rendu
 * dans les templates Twig.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class OpenGraphBuilder
{
    /**
     * Construit les balises Open Graph pour le contenu donné.
     *
     * @param SeoMeta $meta Métadonnées SEO du contenu.
     * @param PostEntity|EventEntity|null $entity Entité du contenu (null pour les archives).
     * @param Language $lang Langue du contenu.
     * @param string $url URL canonique du contenu.
     *
     * @return array<string, string|int>
     */
    public function build(SeoMeta $meta, PostEntity|EventEntity|null $entity, Language $lang, string $url): array
    {
        $isArticle = $entity instanceof PostEntity || $entity instanceof EventEntity;

        $title = $meta->title
            ?? ($entity !== null ? $entity->title : null)
            ?? (string) get_bloginfo('name');

        $description = $meta->description
            ?? ($entity !== null ? $entity->excerpt : null)
            ?? '';

        $tags = [
            'og:type' => $isArticle ? 'article' : 'website',
            'og:locale' => $lang->locale,
            'og:title' => $title,
            'og:description' => $description,
            'og:url' => $url,
            'og:site_name' => (string) get_bloginfo('name'),
        ];

        // Image OG : override meta en priorité
        if ($meta->ogImageId !== null) {
            /** @var array<int, mixed>|false $src */
            $src = wp_get_attachment_image_src($meta->ogImageId, 'full');
            if ($src !== false && \is_array($src)) {
                $tags['og:image'] = (string) $src[0];
                $tags['og:image:width'] = (int) $src[1];
                $tags['og:image:height'] = (int) $src[2];
            }
        } elseif ($entity instanceof PostEntity && $entity->featuredImageUrl !== null) {
            $tags['og:image'] = $entity->featuredImageUrl;
        }

        // Balises article:*
        if ($entity instanceof PostEntity) {
            $tags['article:published_time'] = $entity->publishedAt->format('c');
            if ($entity->updatedAt !== null) {
                $tags['article:modified_time'] = $entity->updatedAt->format('c');
            }
            if ($entity->author !== null) {
                $tags['article:author'] = $entity->author;
            }
        } elseif ($entity instanceof EventEntity) {
            $tags['article:published_time'] = $entity->startDate->format('c');
        }

        return $tags;
    }
}
