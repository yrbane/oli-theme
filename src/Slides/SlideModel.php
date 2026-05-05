<?php

declare(strict_types=1);

namespace OliTheme\Slides;

use DateTimeImmutable;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use WP_Post;

/**
 * Modèle de récupération des slides depuis WordPress.
 *
 * Convertit les `WP_Post` en `SlideEntity` immuables. Aucun appel WP
 * ne fuit hors de cette classe.
 *
 * @package OliTheme\Slides
 *
 * @since 1.0.0
 */
final class SlideModel implements SlideModelInterface
{
    /**
     * @param LanguageRegistryInterface $registry Registre des langues actives.
     */
    public function __construct(
        private readonly LanguageRegistryInterface $registry,
    ) {
    }

    /**
     * Retourne les slides actifs (non expirés) pour une langue donnée.
     *
     * @return SlideEntity[]
     */
    public function findActive(Language $language, int $limit = 10): array
    {
        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $posts = get_posts([
            'post_type' => 'oli_slide',
            'post_status' => 'publish',
            'numberposts' => $limit,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'tax_query' => [[
                'taxonomy' => 'language',
                'field' => 'slug',
                'terms' => $language->code,
            ]],
            'meta_query' => [
                'relation' => 'OR',
                ['key' => '_oli_slide_expires_at', 'compare' => 'NOT EXISTS'],
                ['key' => '_oli_slide_expires_at', 'value' => $now, 'compare' => '>='],
            ],
        ]);

        return array_map(fn ($p) => $this->hydrate($p, $language), $posts);
    }

    /**
     * Retourne un slide par son identifiant WordPress, ou null si introuvable.
     */
    public function findById(int $id): ?SlideEntity
    {
        /** @var WP_Post|array<mixed>|null $raw */
        $raw = get_post($id);

        // On rejette null, les tableaux et tout non-objet
        if ($raw === null || \is_array($raw)) {
            return null;
        }

        /** @var object $post */
        $post = $raw;

        if (($post->post_type ?? '') !== 'oli_slide') {
            return null;
        }

        return $this->hydrate($post, $this->resolveLanguage($post));
    }

    /**
     * Convertit un objet WP_Post-like en SlideEntity.
     *
     * @param object $post WP_Post ou stdClass de test.
     * @param Language $language Langue du slide.
     */
    private function hydrate(object $post, Language $language): SlideEntity
    {
        $id = (int) ($post->ID ?? 0);
        $thumbnailId = (int) get_post_thumbnail_id($id);

        $imageUrlRaw = get_the_post_thumbnail_url($id, 'large');
        $imageUrl = $imageUrlRaw !== false ? (string) $imageUrlRaw : '';

        $imageAltRaw = get_post_meta($thumbnailId, '_wp_attachment_image_alt', true);
        $imageAlt = ($imageAltRaw !== '' && $imageAltRaw !== false) ? (string) $imageAltRaw : null;

        $linkUrlRaw = get_post_meta($id, '_oli_slide_link_url', true);
        $linkUrl = ($linkUrlRaw !== '' && $linkUrlRaw !== false) ? (string) $linkUrlRaw : null;

        $linkLabelRaw = get_post_meta($id, '_oli_slide_link_label', true);
        $linkLabel = ($linkLabelRaw !== '' && $linkLabelRaw !== false) ? (string) $linkLabelRaw : null;

        $expiresAtRaw = get_post_meta($id, '_oli_slide_expires_at', true);
        $expiresAt = null;
        if (\is_string($expiresAtRaw) && $expiresAtRaw !== '') {
            $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $expiresAtRaw);
            $expiresAt = $parsed !== false ? $parsed : null;
        }

        $captionRaw = (string) ($post->post_excerpt ?? '');

        return new SlideEntity(
            id: $id,
            title: (string) ($post->post_title ?? ''),
            caption: $captionRaw !== '' ? $captionRaw : null,
            imageUrl: $imageUrl,
            imageAlt: $imageAlt,
            linkUrl: $linkUrl,
            linkLabel: $linkLabel,
            order: (int) ($post->menu_order ?? 0),
            expiresAt: $expiresAt,
            language: $language,
        );
    }

    /**
     * Résout la langue d'un post via sa taxonomie, avec repli sur la langue par défaut.
     *
     * @param object $post WP_Post ou stdClass de test.
     */
    private function resolveLanguage(object $post): Language
    {
        $id = (int) ($post->ID ?? 0);
        $terms = wp_get_object_terms($id, 'language', ['fields' => 'all']);

        if (\is_array($terms) && ! empty($terms)) {
            $first = $terms[0];
            $code = $first->slug;
            if ($code !== '') {
                $language = $this->registry->get($code);
                if ($language instanceof Language) {
                    return $language;
                }
            }
        }

        return $this->registry->default();
    }
}
