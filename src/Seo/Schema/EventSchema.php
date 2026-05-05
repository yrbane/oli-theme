<?php

declare(strict_types=1);

namespace OliTheme\Seo\Schema;

use OliTheme\Events\EventEntity;

/**
 * Schéma JSON-LD pour le type Event.
 *
 * Construit à partir d'un EventEntity avec gestion des champs optionnels.
 *
 * @package OliTheme\Seo\Schema
 *
 * @since 1.0.0
 */
final readonly class EventSchema implements SchemaInterface
{
    /**
     * @param EventEntity $event Événement source.
     */
    public function __construct(private EventEntity $event)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $schema = [
            '@type' => 'Event',
            '@id' => $this->event->permalink . '#event',
            'name' => $this->event->title,
            'startDate' => $this->event->startDate->format('c'),
            'description' => strip_tags($this->event->description),
            'inLanguage' => $this->event->language->code,
            'url' => $this->event->permalink,
        ];

        if ($this->event->endDate !== null) {
            $schema['endDate'] = $this->event->endDate->format('c');
        }

        if ($this->event->location !== null) {
            $schema['location'] = [
                '@type' => 'Place',
                'name' => $this->event->location,
                'address' => $this->event->address,
            ];
        }

        if ($this->event->price !== null) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => $this->event->price,
                'url' => $this->event->registrationUrl ?? $this->event->permalink,
            ];
        }

        $schema['eventStatus'] = $this->event->isOngoing
            ? 'https://schema.org/EventScheduled'
            : ($this->event->isPast ? 'https://schema.org/EventCompleted' : 'https://schema.org/EventScheduled');

        return $schema;
    }
}
