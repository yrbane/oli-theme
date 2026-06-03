<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use OliTheme\Calendar\Cpt\BookingCpt;

/**
 * Repository CRUD sur le CPT `oli_booking`.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final class BookingRepository implements BookingRepositoryInterface
{
    private const META_START   = '_oli_book_start';
    private const META_END     = '_oli_book_end';
    private const META_SERVICE = '_oli_book_service_id';
    private const META_STATUS  = '_oli_book_status';
    private const META_NAME    = '_oli_book_customer_name';
    private const META_EMAIL   = '_oli_book_customer_email';
    private const META_PHONE   = '_oli_book_customer_phone';
    private const META_MESSAGE = '_oli_book_message';
    private const META_LANG    = '_oli_book_lang';
    private const META_IP_HASH = '_oli_book_ip_hash';

    public function save(Booking $booking, string $ipHash = ''): int
    {
        $title = $booking->serviceId . ' — ' . $booking->customerName . ' — '
               . $booking->start->format('Y-m-d H:i');
        $args = [
            'post_type'   => BookingCpt::POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => $title,
        ];
        if ($booking->id !== null) {
            $args['ID'] = $booking->id;
            $postId    = (int) wp_update_post($args, true);
        } else {
            $postId = (int) wp_insert_post($args, true);
        }
        if ($postId <= 0) {
            throw new \RuntimeException('Failed to persist booking.');
        }
        update_post_meta($postId, self::META_START,   $booking->start->getTimestamp());
        update_post_meta($postId, self::META_END,     $booking->end->getTimestamp());
        update_post_meta($postId, self::META_SERVICE, $booking->serviceId);
        update_post_meta($postId, self::META_STATUS,  $booking->status->value);
        update_post_meta($postId, self::META_NAME,    $booking->customerName);
        update_post_meta($postId, self::META_EMAIL,   $booking->customerEmail);
        update_post_meta($postId, self::META_PHONE,   $booking->customerPhone);
        update_post_meta($postId, self::META_MESSAGE, $booking->message);
        update_post_meta($postId, self::META_LANG,    $booking->language);
        if ($ipHash !== '') {
            update_post_meta($postId, self::META_IP_HASH, $ipHash);
        }

        return $postId;
    }

    public function find(int $id): ?Booking
    {
        $post = get_post($id);
        return $post !== null ? $this->hydrate($post) : null;
    }

    /**
     * Récupère les réservations actives (pending/confirmed) chevauchant
     * la plage [$from, $to[.
     *
     * @return list<Booking>
     */
    public function findActiveInRange(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $posts = get_posts([
            'post_type'   => BookingCpt::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'meta_value_num',
            'meta_key'    => self::META_START,
            'order'       => 'ASC',
            'meta_query'  => [
                'relation' => 'AND',
                ['key' => self::META_START, 'value' => $to->getTimestamp(),   'compare' => '<',  'type' => 'NUMERIC'],
                ['key' => self::META_END,   'value' => $from->getTimestamp(), 'compare' => '>',  'type' => 'NUMERIC'],
                [
                    'key'     => self::META_STATUS,
                    'value'   => [BookingStatus::Pending->value, BookingStatus::Confirmed->value],
                    'compare' => 'IN',
                ],
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

    /**
     * @param array{status?: BookingStatus, limit?: int} $filters
     *
     * @return list<Booking>
     */
    public function recent(array $filters = []): array
    {
        $metaQuery = [];
        if (isset($filters['status']) && $filters['status'] instanceof BookingStatus) {
            $metaQuery[] = ['key' => self::META_STATUS, 'value' => $filters['status']->value];
        }
        $posts = get_posts([
            'post_type'   => BookingCpt::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => $filters['limit'] ?? 50,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'meta_query'  => $metaQuery,
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

    public function setStatus(int $id, BookingStatus $status): void
    {
        update_post_meta($id, self::META_STATUS, $status->value);
    }

    public function delete(int $id): bool
    {
        return (bool) wp_delete_post($id, true);
    }

    private function hydrate(object $post): ?Booking
    {
        $id     = (int) ($post->ID ?? 0);
        $start  = (int) get_post_meta($id, self::META_START, true);
        $end    = (int) get_post_meta($id, self::META_END, true);
        if ($id <= 0 || $start <= 0 || $end <= 0 || $end <= $start) {
            return null;
        }
        $statusRaw = (string) get_post_meta($id, self::META_STATUS, true);
        $status    = BookingStatus::tryFrom($statusRaw) ?? BookingStatus::Pending;
        $name      = (string) get_post_meta($id, self::META_NAME, true);
        $email     = (string) get_post_meta($id, self::META_EMAIL, true);
        if ($name === '' || $email === '') {
            return null;
        }
        $tz = new DateTimeZone('UTC');

        return new Booking(
            id:            $id,
            start:         (new DateTimeImmutable('@' . $start))->setTimezone($tz),
            end:           (new DateTimeImmutable('@' . $end))->setTimezone($tz),
            serviceId:     (string) get_post_meta($id, self::META_SERVICE, true),
            customerName:  $name,
            customerEmail: $email,
            status:        $status,
            customerPhone: (string) get_post_meta($id, self::META_PHONE, true),
            message:       (string) get_post_meta($id, self::META_MESSAGE, true),
            language:      (string) get_post_meta($id, self::META_LANG, true) ?: 'fr',
        );
    }
}
