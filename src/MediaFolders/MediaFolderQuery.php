<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders;

/**
 * Requêtes lecture sur la médiathèque organisée par dossiers.
 *
 * @package OliTheme\MediaFolders
 *
 * @since 1.5.0
 */
final class MediaFolderQuery
{
    /**
     * Liste tous les dossiers existants (terms de la taxonomie) avec compte
     * d'attachments. Retourne un tableau ordonné par nom.
     *
     * @return list<array{slug:string, name:string, parent:int, term_id:int, count:int}>
     */
    public function allFolders(): array
    {
        if (!\function_exists('get_terms') || !taxonomy_exists(MediaFoldersTaxonomy::TAXONOMY)) {
            return [];
        }
        $terms = get_terms([
            'taxonomy'   => MediaFoldersTaxonomy::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'name',
        ]);
        if (!\is_array($terms)) {
            return [];
        }
        $out = [];
        foreach ($terms as $t) {
            if (!\is_object($t) || !isset($t->slug)) {
                continue;
            }
            $out[] = [
                'slug'    => (string) $t->slug,
                'name'    => (string) ($t->name ?? $t->slug),
                'parent'  => (int) ($t->parent ?? 0),
                'term_id' => (int) ($t->term_id ?? 0),
                'count'   => (int) ($t->count ?? 0),
            ];
        }
        return $out;
    }

    /**
     * Photos contenues dans un dossier (et optionnellement ses sous-dossiers).
     *
     * @return list<array{
     *     id:int,
     *     url:string,
     *     srcset:string,
     *     thumb:string,
     *     alt:string,
     *     caption:string,
     *     title:string
     * }>
     */
    public function photosInFolder(string $slug, bool $includeChildren = true, int $limit = -1): array
    {
        if ($slug === '' || !\function_exists('get_posts')) {
            return [];
        }
        $posts = get_posts([
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'numberposts' => $limit,
            'orderby'     => 'menu_order date',
            'order'       => 'ASC',
            'tax_query'   => [[
                'taxonomy'         => MediaFoldersTaxonomy::TAXONOMY,
                'field'            => 'slug',
                'terms'            => $slug,
                'include_children' => $includeChildren,
            ]],
        ]);
        if (!\is_array($posts)) {
            return [];
        }
        $out = [];
        foreach ($posts as $post) {
            $hydrated = $this->hydrate($post);
            if ($hydrated !== null) {
                $out[] = $hydrated;
            }
        }
        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function hydrate(object $post): ?array
    {
        $id = (int) ($post->ID ?? 0);
        if ($id <= 0) {
            return null;
        }
        $url    = \function_exists('wp_get_attachment_image_url') ? (string) (wp_get_attachment_image_url($id, 'large') ?: '') : '';
        $thumb  = \function_exists('wp_get_attachment_image_url') ? (string) (wp_get_attachment_image_url($id, 'thumbnail') ?: '') : '';
        $srcset = \function_exists('wp_get_attachment_image_srcset') ? (string) (wp_get_attachment_image_srcset($id, 'large') ?: '') : '';
        $alt    = \function_exists('get_post_meta') ? (string) get_post_meta($id, '_wp_attachment_image_alt', true) : '';
        if ($url === '') {
            return null;
        }
        return [
            'id'      => $id,
            'url'     => $url,
            'srcset'  => $srcset,
            'thumb'   => $thumb !== '' ? $thumb : $url,
            'alt'     => $alt,
            'caption' => (string) ($post->post_excerpt ?? ''),
            'title'   => (string) ($post->post_title   ?? ''),
        ];
    }
}
