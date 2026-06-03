<?php

declare(strict_types=1);

namespace OliTheme\Gabarits;

/**
 * Contenu typé d'une zone pour un post donné.
 *
 * - Text    : `text` (string, HTML sanitized).
 * - Image   : `imageId` (int).
 * - Gallery : `imageIds` (list<int>).
 *
 * @package OliTheme\Gabarits
 *
 * @since 1.5.0
 */
final readonly class ZoneContent
{
    /**
     * @param list<int> $imageIds
     */
    public function __construct(
        public ZoneType $type,
        public string $text = '',
        public int $imageId = 0,
        public array $imageIds = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return match ($this->type) {
            ZoneType::Text    => trim($this->text) === '',
            ZoneType::Image   => $this->imageId <= 0,
            ZoneType::Gallery => empty($this->imageIds),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return match ($this->type) {
            ZoneType::Text    => ['type' => 'text',    'text'     => $this->text],
            ZoneType::Image   => ['type' => 'image',   'imageId'  => $this->imageId],
            ZoneType::Gallery => ['type' => 'gallery', 'imageIds' => array_values($this->imageIds)],
        };
    }

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): ?self
    {
        $type = ZoneType::tryFrom((string) ($raw['type'] ?? ''));
        if ($type === null) {
            return null;
        }
        return match ($type) {
            ZoneType::Text    => new self($type, text: (string) ($raw['text'] ?? '')),
            ZoneType::Image   => new self($type, imageId: (int) ($raw['imageId'] ?? 0)),
            ZoneType::Gallery => new self(
                $type,
                imageIds: \is_array($raw['imageIds'] ?? null)
                    ? array_values(array_filter(array_map('intval', $raw['imageIds']), static fn (int $i): bool => $i > 0))
                    : [],
            ),
        };
    }
}
