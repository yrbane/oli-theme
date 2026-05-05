<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Schema;

use DateTimeImmutable;
use OliTheme\Events\EventEntity;
use OliTheme\I18n\Language;
use OliTheme\Seo\Schema\EventSchema;
use PHPUnit\Framework\TestCase;

/**
 * Tests du EventSchema.
 *
 * @package OliTheme\Tests\Unit\Seo\Schema
 *
 * @since 1.0.0
 */
final class EventSchemaTest extends TestCase
{
    private Language $language;

    protected function setUp(): void
    {
        parent::setUp();

        $this->language = new Language(
            code: 'fr',
            label: 'Français',
            nativeLabel: 'Français',
            flag: 'fr',
            locale: 'fr_FR',
        );
    }

    public function testToArrayFull(): void
    {
        $event = new EventEntity(
            id: 1,
            title: 'Concert de jazz',
            description: '<p>Un super concert</p>',
            excerpt: 'Concert jazz',
            slug: 'concert-jazz',
            startDate: new DateTimeImmutable('2024-06-15T20:00:00+02:00'),
            endDate: new DateTimeImmutable('2024-06-15T23:00:00+02:00'),
            location: 'Salle des fêtes',
            address: '1 rue de la Paix, 75001 Paris',
            flyerUrl: 'https://example.com/flyer.jpg',
            registrationUrl: 'https://example.com/inscription',
            price: '15',
            language: $this->language,
            permalink: 'https://example.com/fr/concerts/concert-jazz',
            isPast: false,
            isOngoing: true,
        );

        $schema = new EventSchema($event);
        $result = $schema->toArray();

        self::assertSame('Event', $result['@type']);
        self::assertSame('https://example.com/fr/concerts/concert-jazz#event', $result['@id']);
        self::assertSame('Concert de jazz', $result['name']);
        self::assertSame('Un super concert', $result['description']);
        self::assertSame('fr', $result['inLanguage']);
        self::assertArrayHasKey('endDate', $result);
        self::assertSame('Place', $result['location']['@type']);
        self::assertSame('Salle des fêtes', $result['location']['name']);
        self::assertSame('1 rue de la Paix, 75001 Paris', $result['location']['address']);
        self::assertSame('Offer', $result['offers']['@type']);
        self::assertSame('15', $result['offers']['price']);
        self::assertSame('https://example.com/inscription', $result['offers']['url']);
        self::assertSame('https://schema.org/EventScheduled', $result['eventStatus']);
    }

    public function testToArrayMinimal(): void
    {
        $event = new EventEntity(
            id: 2,
            title: 'Atelier photo',
            description: 'Apprenez la photo',
            excerpt: null,
            slug: 'atelier-photo',
            startDate: new DateTimeImmutable('2024-07-01T10:00:00+02:00'),
            endDate: null,
            location: null,
            address: null,
            flyerUrl: null,
            registrationUrl: null,
            price: null,
            language: $this->language,
            permalink: 'https://example.com/fr/ateliers/atelier-photo',
            isPast: true,
            isOngoing: false,
        );

        $schema = new EventSchema($event);
        $result = $schema->toArray();

        self::assertSame('Event', $result['@type']);
        self::assertSame('Atelier photo', $result['name']);
        self::assertArrayNotHasKey('endDate', $result);
        self::assertArrayNotHasKey('location', $result);
        self::assertArrayNotHasKey('offers', $result);
        self::assertSame('https://schema.org/EventCompleted', $result['eventStatus']);
    }
}
