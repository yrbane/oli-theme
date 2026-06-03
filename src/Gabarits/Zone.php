<?php

declare(strict_types=1);

namespace OliTheme\Gabarits;

/**
 * Définition d'une zone d'un gabarit (déclarative).
 *
 * @package OliTheme\Gabarits
 *
 * @since 1.5.0
 */
final readonly class Zone
{
    public function __construct(
        public string $id,
        public ZoneType $type,
        public string $label,
        public string $help = '',
    ) {
    }

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): ?self
    {
        $id   = (string) ($raw['id']   ?? '');
        $type = ZoneType::tryFrom((string) ($raw['type'] ?? ''));
        if ($id === '' || $type === null) {
            return null;
        }
        return new self(
            id:    preg_replace('/[^a-z0-9_\-]/', '', strtolower($id)) ?? $id,
            type:  $type,
            label: (string) ($raw['label'] ?? ucfirst($id)),
            help:  (string) ($raw['help']  ?? ''),
        );
    }
}
