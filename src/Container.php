<?php

declare(strict_types=1);

namespace OliTheme;

use Closure;
use OutOfBoundsException;

/**
 * Conteneur de dépendances minimaliste, inspiré de PSR-11.
 *
 * Stocke des instances déjà construites (set) ou des fabriques paresseuses
 * (factory) qui produisent une instance unique partagée par défaut.
 * Volontairement spartiate : pas d'auto-wiring, pas de cycle de vie complexe,
 * pas de tags. Conçu pour être 100 % testable et lisible.
 *
 * @package OliTheme
 *
 * @since 1.0.0
 */
final class Container
{
    /**
     * Instances déjà résolues, indexées par identifiant logique.
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Fabriques paresseuses, indexées par identifiant logique.
     *
     * @var array<string, Closure>
     */
    private array $factories = [];

    /**
     * Enregistre une instance déjà construite sous l'identifiant donné.
     *
     * @param string $id Identifiant logique du service (souvent le FQCN).
     * @param mixed $instance Instance à mémoriser.
     */
    public function set(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
        unset($this->factories[$id]);
    }

    /**
     * Enregistre une fabrique paresseuse pour l'identifiant donné.
     *
     * La fabrique reçoit le conteneur en argument et n'est appelée qu'au premier
     * appel à get(). Le résultat est mémoïsé (singleton effectif).
     *
     * @param string $id Identifiant logique du service.
     * @param Closure(Container): mixed $factory Fabrique du service.
     */
    public function factory(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /**
     * Récupère le service enregistré sous cet identifiant.
     *
     * @throws OutOfBoundsException Si aucun service n'est enregistré sous cet id.
     */
    public function get(string $id): mixed
    {
        if (\array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            $instance = ($this->factories[$id])($this);
            $this->instances[$id] = $instance;

            return $instance;
        }

        throw new OutOfBoundsException(\sprintf("Service '%s' non enregistré dans le conteneur.", $id));
    }

    /**
     * Indique si un service est enregistré sous cet identifiant.
     */
    public function has(string $id): bool
    {
        return \array_key_exists($id, $this->instances) || isset($this->factories[$id]);
    }
}
