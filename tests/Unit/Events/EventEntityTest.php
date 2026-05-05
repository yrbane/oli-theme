<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Events;

use DateTimeImmutable;
use OliTheme\Events\EventEntity;
use OliTheme\I18n\Language;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de EventEntity (DTO immuable d'événement).
 *
 * @package OliTheme\Tests\Unit\Events
 *
 * @since 1.0.0
 */
final class EventEntityTest extends TestCase
{
    private Language $french;

    protected function setUp(): void
    {
        parent::setUp();
        $this->french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
    }

    public function testItExposesAllProperties(): void
    {
        $start = new DateTimeImmutable('2026-06-15 19:00:00');
        $end = new DateTimeImmutable('2026-06-15 22:00:00');

        $entity = new EventEntity(
            id: 1,
            title: 'Concert de jazz',
            description: '<p>Un beau concert.</p>',
            excerpt: 'Un beau concert.',
            slug: 'concert-de-jazz',
            startDate: $start,
            endDate: $end,
            location: 'Salle Pleyel',
            address: '252 Rue du Faubourg Saint-Honoré, Paris',
            flyerUrl: 'https://cdn/flyer.jpg',
            registrationUrl: 'https://ticketing.example.com',
            price: '25€',
            language: $this->french,
            permalink: 'https://olikalari.com/evenements/concert-de-jazz',
            isPast: false,
            isOngoing: true,
        );

        self::assertSame(1, $entity->id);
        self::assertSame('Concert de jazz', $entity->title);
        self::assertSame('<p>Un beau concert.</p>', $entity->description);
        self::assertSame('Un beau concert.', $entity->excerpt);
        self::assertSame('concert-de-jazz', $entity->slug);
        self::assertSame($start, $entity->startDate);
        self::assertSame($end, $entity->endDate);
        self::assertSame('Salle Pleyel', $entity->location);
        self::assertSame('252 Rue du Faubourg Saint-Honoré, Paris', $entity->address);
        self::assertSame('https://cdn/flyer.jpg', $entity->flyerUrl);
        self::assertSame('https://ticketing.example.com', $entity->registrationUrl);
        self::assertSame('25€', $entity->price);
        self::assertSame($this->french, $entity->language);
        self::assertSame('https://olikalari.com/evenements/concert-de-jazz', $entity->permalink);
        self::assertFalse($entity->isPast);
        self::assertTrue($entity->isOngoing);
    }

    public function testItAcceptsNullableOptionals(): void
    {
        $start = new DateTimeImmutable('2026-06-15 19:00:00');

        $entity = new EventEntity(
            id: 2,
            title: 'Événement minimal',
            description: '',
            excerpt: null,
            slug: 'evenement-minimal',
            startDate: $start,
            endDate: null,
            location: null,
            address: null,
            flyerUrl: null,
            registrationUrl: null,
            price: null,
            language: $this->french,
            permalink: 'https://olikalari.com/evenements/evenement-minimal',
            isPast: false,
            isOngoing: false,
        );

        self::assertNull($entity->excerpt);
        self::assertNull($entity->endDate);
        self::assertNull($entity->location);
        self::assertNull($entity->address);
        self::assertNull($entity->flyerUrl);
        self::assertNull($entity->registrationUrl);
        self::assertNull($entity->price);
    }
}
