<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use DateTimeImmutable;
use DateTimeZone;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\LanguageResolverInterface;
use WP_Post;

/**
 * Modèle générique des contenus pages/posts du thème.
 *
 * Convertit les `WP_Post` en `PostEntity` immuables et expose des méthodes
 * de récupération typées. Aucun appel WP ne fuit hors de cette classe.
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class PostModel
{
    public function __construct(
        private readonly LanguageResolverInterface $resolver,
        private readonly LanguageRegistryInterface $registry,
    ) {
    }

    /**
     * Récupère une entité par son identifiant WP.
     */
    public function find(int $id): ?PostEntity
    {
        /** @var WP_Post|array<mixed>|null $raw */
        $raw = get_post($id);
        // On rejette null, les tableaux (get_post avec ARRAY_A/ARRAY_N), et tout non-objet
        if ($raw === null || \is_array($raw)) {
            return null;
        }
        // $raw est un objet (WP_Post en prod, stdClass en test)
        /** @var object $post */
        $post = $raw;

        return $this->hydrate($post);
    }

    /**
     * Récupère une entité par son slug et sa langue.
     */
    public function findBySlug(string $slug, Language $language, string $type = 'post'): ?PostEntity
    {
        $posts = get_posts([
            'name' => $slug,
            'post_type' => $type,
            'post_status' => 'publish',
            'numberposts' => 1,
            'tax_query' => [[
                'taxonomy' => 'language',
                'field' => 'slug',
                'terms' => $language->code,
            ]],
        ]);

        if (empty($posts)) {
            return null;
        }

        return $this->hydrate($posts[0]);
    }

    /**
     * @return PostEntity[]
     */
    public function findByLanguage(Language $language, int $limit = 10, string $type = 'post'): array
    {
        $posts = get_posts([
            'post_type' => $type,
            'post_status' => 'publish',
            'numberposts' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => [[
                'taxonomy' => 'language',
                'field' => 'slug',
                'terms' => $language->code,
            ]],
        ]);

        return array_values(array_map(fn (object $p): PostEntity => $this->hydrate($p), $posts));
    }

    /**
     * Lit une meta sous-jacente avec valeur par défaut.
     */
    public function getMeta(int $id, string $key, mixed $default = null): mixed
    {
        $value = get_post_meta($id, $key, true);
        if ($value === '' || $value === false || $value === null) {
            return $default;
        }

        return $value;
    }

    /**
     * @param object $post WP_Post-like (testable via stdClass).
     */
    private function hydrate(object $post): PostEntity
    {
        $rawContent = (string) ($post->post_content ?? '');
        $rawExcerpt = (string) ($post->post_excerpt ?? '');

        $renderedContent = (string) apply_filters('the_content', $rawContent);
        $renderedExcerpt = $rawExcerpt !== ''
            ? (string) apply_filters('the_excerpt', $rawExcerpt)
            : null;

        $thumbnailUrl = get_the_post_thumbnail_url($post->ID ?? 0, 'large');
        $thumbnailUrl = \is_string($thumbnailUrl) && $thumbnailUrl !== '' ? $thumbnailUrl : null;

        $thumbnailId = (int) get_post_thumbnail_id($post->ID ?? 0);
        $thumbnailAlt = $thumbnailId > 0
            ? (string) get_post_meta($thumbnailId, '_wp_attachment_image_alt', true)
            : '';
        $thumbnailAlt = $thumbnailAlt !== '' ? $thumbnailAlt : null;

        $author = isset($post->post_author)
            ? (string) get_the_author_meta('display_name', (int) $post->post_author)
            : null;
        $author = \is_string($author) && $author !== '' ? $author : null;

        return new PostEntity(
            id: (int) ($post->ID ?? 0),
            type: (string) ($post->post_type ?? 'post'),
            title: (string) ($post->post_title ?? ''),
            content: $renderedContent,
            excerpt: $renderedExcerpt,
            slug: (string) ($post->post_name ?? ''),
            language: $this->resolveLanguage($post),
            featuredImageUrl: $thumbnailUrl,
            featuredImageAlt: $thumbnailAlt,
            permalink: (string) get_permalink((int) ($post->ID ?? 0)),
            publishedAt: $this->parseDate((string) ($post->post_date_gmt ?? '')),
            updatedAt: $this->parseOptionalDate((string) ($post->post_modified_gmt ?? '')),
            author: $author,
        );
    }

    private function resolveLanguage(object $post): Language
    {
        $id = isset($post->ID) ? (int) $post->ID : 0;
        $terms = wp_get_object_terms($id, 'language', ['fields' => 'all']);
        if (\is_array($terms) && ! empty($terms)) {
            $first = $terms[0];
            // WP_Term en production ; le slug est une chaîne non vide garantie par WP
            $code = $first->slug;
            if ($code !== '') {
                $language = $this->registry->get($code);
                if ($language instanceof Language) {
                    return $language;
                }
            }
        }

        return $this->resolver->current();
    }

    private function parseDate(string $value): DateTimeImmutable
    {
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return new DateTimeImmutable('@0', new DateTimeZone('UTC'));
        }

        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }

    private function parseOptionalDate(string $value): ?DateTimeImmutable
    {
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
