<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

/**
 * Service réservable (cours particulier, séance de massage, etc.).
 *
 * Persistance : tableau JSON dans l'option `oli_services` (un tableau par
 * service). Pas de CPT pour rester léger et faciliter le drag & drop d'ordre.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final readonly class Service
{
    public function __construct(
        public string $id,
        public string $labelFr,
        public string $labelEn,
        public int $durationMinutes,
        public string $descriptionFr = '',
        public string $descriptionEn = '',
        public ?int $priceCents = null,
    ) {
    }

    /**
     * Libellé localisé : EN si demandé et présent, sinon FR.
     */
    public function label(string $language): string
    {
        if ($language === 'en' && $this->labelEn !== '') {
            return $this->labelEn;
        }
        return $this->labelFr;
    }

    public function description(string $language): string
    {
        if ($language === 'en' && $this->descriptionEn !== '') {
            return $this->descriptionEn;
        }
        return $this->descriptionFr;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'labelFr'         => $this->labelFr,
            'labelEn'         => $this->labelEn,
            'durationMinutes' => $this->durationMinutes,
            'descriptionFr'   => $this->descriptionFr,
            'descriptionEn'   => $this->descriptionEn,
            'priceCents'      => $this->priceCents,
        ];
    }

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $price = $raw['priceCents'] ?? null;
        return new self(
            id:              (string) ($raw['id'] ?? ''),
            labelFr:         (string) ($raw['labelFr'] ?? ''),
            labelEn:         (string) ($raw['labelEn'] ?? ''),
            durationMinutes: max(15, min(480, (int) ($raw['durationMinutes'] ?? 60))),
            descriptionFr:   (string) ($raw['descriptionFr'] ?? ''),
            descriptionEn:   (string) ($raw['descriptionEn'] ?? ''),
            priceCents:      $price === null || $price === '' ? null : max(0, (int) $price),
        );
    }
}
