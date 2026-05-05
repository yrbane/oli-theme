<?php

declare(strict_types=1);

namespace OliTheme\Events;

use DateTimeImmutable;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use WP_Post;

/**
 * Modèle de récupération des événements depuis WordPress.
 *
 * Convertit les `WP_Post` en `EventEntity` immuables. Aucun appel WP
 * ne fuit hors de cette classe.
 *
 * @package OliTheme\Events
 *
 * @since 1.0.0
 */
final class EventModel implements EventModelInterface
{
    /**
     * @param LanguageRegistryInterface $registry Registre des langues actives.
     */
    public function __construct(
        private readonly LanguageRegistryInterface $registry,
    ) {
    }

    /**
     * Retourne les événements à venir pour une langue donnée.
     *
     * @return EventEntity[]
     */
    public function findUpcoming(Language $language, int $limit = 10): array
    {
        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $posts = get_posts([
            'post_type' => 'oli_event',
            'post_status' => 'publish',
            'numberposts' => $limit,
            'meta_key' => '_oli_event_start_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [['key' => '_oli_event_start_date', 'value' => $now, 'compare' => '>=']],
            'tax_query' => [[
                'taxonomy' => 'language',
                'field' => 'slug',
                'terms' => $language->code,
            ]],
        ]);

        return array_map(fn ($p) => $this->hydrate($p, $language), $posts);
    }

    /**
     * Retourne les événements passés pour une langue donnée.
     *
     * @return EventEntity[]
     */
    public function findPast(Language $language, int $limit = 10): array
    {
        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        $posts = get_posts([
            'post_type' => 'oli_event',
            'post_status' => 'publish',
            'numberposts' => $limit,
            'meta_key' => '_oli_event_end_date',
            'orderby' => 'meta_value',
            'order' => 'DESC',
            'meta_query' => [['key' => '_oli_event_end_date', 'value' => $now, 'compare' => '<']],
            'tax_query' => [[
                'taxonomy' => 'language',
                'field' => 'slug',
                'terms' => $language->code,
            ]],
        ]);

        return array_map(fn ($p) => $this->hydrate($p, $language), $posts);
    }

    /**
     * Retourne un événement par son identifiant WordPress, ou null si introuvable.
     */
    public function findById(int $id): ?EventEntity
    {
        /** @var WP_Post|array<mixed>|null $raw */
        $raw = get_post($id);

        // On rejette null, les tableaux et tout non-objet
        if ($raw === null || \is_array($raw)) {
            return null;
        }

        /** @var object $post */
        $post = $raw;

        if (($post->post_type ?? '') !== 'oli_event') {
            return null;
        }

        return $this->hydrate($post, $this->resolveLanguage($post));
    }

    /**
     * Retourne un événement par son slug et sa langue, ou null si introuvable.
     */
    public function findBySlug(string $slug, Language $language): ?EventEntity
    {
        $posts = get_posts([
            'post_type' => 'oli_event',
            'post_status' => 'publish',
            'numberposts' => 1,
            'name' => $slug,
            'tax_query' => [[
                'taxonomy' => 'language',
                'field' => 'slug',
                'terms' => $language->code,
            ]],
        ]);

        if (empty($posts)) {
            return null;
        }

        return $this->hydrate($posts[0], $language);
    }

    /**
     * Convertit un objet WP_Post-like en EventEntity.
     *
     * @param object $post WP_Post ou stdClass de test.
     * @param Language $language Langue de l'événement.
     */
    private function hydrate(object $post, Language $language): EventEntity
    {
        $id = (int) ($post->ID ?? 0);

        $startDateRaw = get_post_meta($id, '_oli_event_start_date', true);
        $startDate = $this->parseDate(\is_string($startDateRaw) ? $startDateRaw : null)
            ?? new DateTimeImmutable('now');

        $endDate = $this->parseDate(
            \is_string(get_post_meta($id, '_oli_event_end_date', true))
                ? (string) get_post_meta($id, '_oli_event_end_date', true)
                : null,
        );

        $locationRaw = get_post_meta($id, '_oli_event_location', true);
        $location = (\is_string($locationRaw) && $locationRaw !== '') ? $locationRaw : null;

        $addressRaw = get_post_meta($id, '_oli_event_address', true);
        $address = (\is_string($addressRaw) && $addressRaw !== '') ? $addressRaw : null;

        $flyerUrlRaw = get_post_meta($id, '_oli_event_flyer_url', true);
        $flyerUrl = (\is_string($flyerUrlRaw) && $flyerUrlRaw !== '') ? $flyerUrlRaw : null;

        $registrationUrlRaw = get_post_meta($id, '_oli_event_registration_url', true);
        $registrationUrl = (\is_string($registrationUrlRaw) && $registrationUrlRaw !== '') ? $registrationUrlRaw : null;

        $priceRaw = get_post_meta($id, '_oli_event_price', true);
        $price = (\is_string($priceRaw) && $priceRaw !== '') ? $priceRaw : null;

        $permalinkRaw = get_permalink($id);
        $permalink = $permalinkRaw !== false ? (string) $permalinkRaw : '';

        $now = new DateTimeImmutable('now');
        $end = $endDate ?? $startDate;
        $isPast = $end < $now;
        $isOngoing = $startDate <= $now && $now <= $end;

        return new EventEntity(
            id: $id,
            title: (string) ($post->post_title ?? ''),
            description: (string) ($post->post_content ?? ''),
            excerpt: ($post->post_excerpt ?? '') !== '' ? (string) $post->post_excerpt : null,
            slug: (string) ($post->post_name ?? ''),
            startDate: $startDate,
            endDate: $endDate,
            location: $location,
            address: $address,
            flyerUrl: $flyerUrl,
            registrationUrl: $registrationUrl,
            price: $price,
            language: $language,
            permalink: $permalink,
            isPast: $isPast,
            isOngoing: $isOngoing,
        );
    }

    /**
     * Tente de convertir une chaîne en DateTimeImmutable (format Y-m-d H:i:s).
     * Retourne null si la chaîne est vide ou invalide.
     */
    private function parseDate(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

        return $parsed !== false ? $parsed : null;
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
