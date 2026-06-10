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
            if (!$t instanceof \WP_Term) {
                continue;
            }
            $slug = (string) $t->slug;
            $out[] = [
                'slug'    => $slug,
                'name'    => (string) $t->name,
                'parent'  => (int) $t->parent,
                'term_id' => (int) $t->term_id,
                // Le `count` natif n'est pas fiable pour les attachments
                // (`_update_post_term_count` ne compte que les posts publish,
                // or les attachments sont en `inherit`). On recompte.
                'count'   => $this->countAttachmentsInFolder($slug),
            ];
        }
        return $out;
    }

    /**
     * Recompte dynamiquement le nombre de photos rangées dans un dossier
     * (et ses sous-dossiers). Utilisé en remplacement du `count` natif des
     * terms WP, faussé sur la taxonomie attachment.
     */
    private function countAttachmentsInFolder(string $slug): int
    {
        if ($slug === '' || !\function_exists('get_posts')) {
            return 0;
        }
        $ids = get_posts([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'numberposts'    => -1,
            'fields'         => 'ids',
            'tax_query'      => [[
                'taxonomy'         => MediaFoldersTaxonomy::TAXONOMY,
                'field'            => 'slug',
                'terms'            => $slug,
                'include_children' => true,
            ]],
        ]);

        return \count($ids);
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
     * @return array{id:int, url:string, srcset:string, thumb:string, alt:string, caption:string, title:string}|null
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
