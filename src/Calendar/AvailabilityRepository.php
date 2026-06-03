<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use OliTheme\Calendar\Cpt\AvailabilityCpt;

/**
 * Repository CRUD sur le CPT `oli_availability`.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final class AvailabilityRepository
{
    private const META_START  = '_oli_avail_start';
    private const META_END    = '_oli_avail_end';
    private const META_TYPE   = '_oli_avail_type';
    private const META_SOURCE = '_oli_avail_source';

    /**
     * Insère ou met à jour une indisponibilité. Retourne le post id.
     */
    public function save(Availability $availability): int
    {
        $args = [
            'post_type'   => AvailabilityCpt::POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => $availability->title !== ''
                ? $availability->title
                : $availability->start->format('Y-m-d H:i') . ' → ' . $availability->end->format('H:i'),
        ];
        if ($availability->id !== null) {
            $args['ID'] = $availability->id;
            $postId    = (int) wp_update_post($args, true);
        } else {
            $postId = (int) wp_insert_post($args, true);
        }
        if ($postId <= 0) {
            throw new \RuntimeException('Failed to persist availability.');
        }
        update_post_meta($postId, self::META_START,  $availability->start->getTimestamp());
        update_post_meta($postId, self::META_END,    $availability->end->getTimestamp());
        update_post_meta($postId, self::META_TYPE,   $availability->type);
        update_post_meta($postId, self::META_SOURCE, $availability->source);

        return $postId;
    }

    public function delete(int $id): bool
    {
        return (bool) wp_delete_post($id, true);
    }

    /**
     * Récupère les indisponibilités chevauchant la plage [$from, $to[.
     *
     * @return list<Availability>
     */
    public function findInRange(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $posts = get_posts([
            'post_type'   => AvailabilityCpt::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'meta_value_num',
            'meta_key'    => self::META_START,
            'order'       => 'ASC',
            'meta_query'  => [
                'relation' => 'AND',
                ['key' => self::META_START, 'value' => $to->getTimestamp(),   'compare' => '<',  'type' => 'NUMERIC'],
                ['key' => self::META_END,   'value' => $from->getTimestamp(), 'compare' => '>',  'type' => 'NUMERIC'],
            ],
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

    public function find(int $id): ?Availability
    {
        $post = get_post($id);
        return $post !== null ? $this->hydrate($post) : null;
    }

    private function hydrate(object $post): ?Availability
    {
        $id    = (int) ($post->ID ?? 0);
        $start = (int) get_post_meta($id, self::META_START, true);
        $end   = (int) get_post_meta($id, self::META_END, true);
        if ($id <= 0 || $start <= 0 || $end <= 0 || $end <= $start) {
            return null;
        }
        $tz = new DateTimeZone('UTC');

        return new Availability(
            id:     $id,
            start:  (new DateTimeImmutable('@' . $start))->setTimezone($tz),
            end:    (new DateTimeImmutable('@' . $end))->setTimezone($tz),
            title:  (string) ($post->post_title ?? ''),
            type:   (string) get_post_meta($id, self::META_TYPE, true) ?: Availability::TYPE_BLOCKED,
            source: (string) get_post_meta($id, self::META_SOURCE, true) ?: Availability::SOURCE_MANUAL,
        );
    }
}
