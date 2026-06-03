<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

/**
 * Repository simple sur l'option `oli_services` (tableau JSON de services).
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final class ServiceRepository
{
    public const OPTION_KEY = 'oli_services';

    /**
     * Récupère tous les services persistés, dans l'ordre d'insertion.
     *
     * @return list<Service>
     */
    public function all(): array
    {
        $raw = \function_exists('get_option') ? get_option(self::OPTION_KEY, []) : [];
        if (!\is_array($raw)) {
            return [];
        }
        $services = [];
        foreach ($raw as $entry) {
            if (\is_array($entry)) {
                $services[] = Service::fromArray($entry);
            }
        }

        return $services;
    }

    /**
     * Récupère un service par son id (ou null).
     */
    public function byId(string $id): ?Service
    {
        foreach ($this->all() as $service) {
            if ($service->id === $id) {
                return $service;
            }
        }
        return null;
    }

    /**
     * Insère ou met à jour un service (clé : id). Retourne le service stocké.
     *
     * @throws \InvalidArgumentException si l'id est vide après sanitisation.
     */
    public function save(Service $service): Service
    {
        $service = $this->ensureId($service);
        $list    = $this->all();
        $replaced = false;
        foreach ($list as $i => $existing) {
            if ($existing->id === $service->id) {
                $list[$i] = $service;
                $replaced = true;
                break;
            }
        }
        if (!$replaced) {
            $list[] = $service;
        }
        $this->persist($list);

        return $service;
    }

    /**
     * Supprime un service par id. Retourne true si quelque chose a été supprimé.
     */
    public function delete(string $id): bool
    {
        $list = $this->all();
        $kept = array_values(array_filter($list, static fn (Service $s): bool => $s->id !== $id));
        if (\count($kept) === \count($list)) {
            return false;
        }
        $this->persist($kept);
        return true;
    }

    /**
     * Génère un id stable et URL-safe si le service n'en a pas (ou nettoie un id existant).
     */
    private function ensureId(Service $service): Service
    {
        $id = $service->id !== ''
            ? preg_replace('/[^a-z0-9_\-]/', '', strtolower($service->id))
            : '';
        if ($id === '') {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $service->labelFr) ?? '');
            $id   = trim($slug, '-');
            if ($id === '') {
                $id = 'svc-' . substr(bin2hex(random_bytes(4)), 0, 8);
            }
        }
        if ($id === $service->id) {
            return $service;
        }

        return new Service(
            id:              $id,
            labelFr:         $service->labelFr,
            labelEn:         $service->labelEn,
            durationMinutes: $service->durationMinutes,
            descriptionFr:   $service->descriptionFr,
            descriptionEn:   $service->descriptionEn,
            priceCents:      $service->priceCents,
        );
    }

    /**
     * @param list<Service> $list
     */
    private function persist(array $list): void
    {
        $payload = array_map(static fn (Service $s): array => $s->toArray(), $list);
        if (\function_exists('update_option')) {
            update_option(self::OPTION_KEY, $payload, false);
        }
    }
}
