<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Events\EventEntity;
use OliTheme\Posts\PostEntity;

/**
 * Construit les balises Twitter Card pour la balise <head>.
 *
 * Retourne un tableau associatif `name => content` prêt à être rendu
 * dans les templates Twig.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class TwitterCardBuilder
{
    /**
     * Construit les balises Twitter Card pour le contenu donné.
     *
     * @param SeoMeta $meta Métadonnées SEO du contenu.
     * @param PostEntity|EventEntity|null $entity Entité du contenu (null pour les archives).
     *
     * @return array<string, string>
     */
    public function build(SeoMeta $meta, PostEntity|EventEntity|null $entity): array
    {
        $title = $meta->title
            ?? ($entity !== null ? $entity->title : null)
            ?? '';

        $description = $meta->description
            ?? ($entity !== null ? $entity->excerpt : null)
            ?? '';

        $tags = [
            'twitter:card' => $meta->twitterCardType,
            'twitter:title' => $title,
            'twitter:description' => $description,
        ];

        // Image : override meta OG en priorité
        if ($meta->ogImageId !== null) {
            /** @var array<int, mixed>|false $src */
            $src = wp_get_attachment_image_src($meta->ogImageId, 'full');
            if ($src !== false && \is_array($src)) {
                $tags['twitter:image'] = (string) $src[0];
            }
        } elseif ($entity instanceof PostEntity && $entity->featuredImageUrl !== null) {
            $tags['twitter:image'] = $entity->featuredImageUrl;
        } elseif ($entity instanceof EventEntity && $entity->flyerUrl !== null) {
            $tags['twitter:image'] = $entity->flyerUrl;
        }

        return $tags;
    }
}
