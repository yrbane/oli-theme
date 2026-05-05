<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Wrapper testable autour des fonctions WordPress add_action / add_filter.
 *
 * Permet (1) d'injecter le registrar dans les modules pour mocker les hooks
 * en test, et (2) de tenir un registre interne des hooks branchés à des
 * fins d'introspection / debug.
 *
 * @package OliTheme\Core
 *
 * @since 1.0.0
 */
final class HookRegistrar
{
    /**
     * Liste des hooks enregistrés depuis la création de l'instance.
     *
     * @var array<int, array{type: string, hook: string, priority: int, acceptedArgs: int}>
     */
    private array $registered = [];

    /**
     * Enregistre une action WordPress.
     *
     * @param string $hook Nom du hook ('init', 'wp_enqueue_scripts'...).
     * @param callable $callback Callback à exécuter.
     * @param int $priority Priorité d'exécution (10 par défaut).
     * @param int $acceptedArgs Nombre d'arguments acceptés (1 par défaut).
     */
    public function action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_action($hook, $callback, $priority, $acceptedArgs);
        $this->registered[] = [
            'type' => 'action',
            'hook' => $hook,
            'priority' => $priority,
            'acceptedArgs' => $acceptedArgs,
        ];
    }

    /**
     * Enregistre un filtre WordPress.
     *
     * @param string $hook Nom du filtre.
     * @param callable $callback Callback à exécuter.
     * @param int $priority Priorité d'exécution (10 par défaut).
     * @param int $acceptedArgs Nombre d'arguments acceptés (1 par défaut).
     */
    public function filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_filter($hook, $callback, $priority, $acceptedArgs);
        $this->registered[] = [
            'type' => 'filter',
            'hook' => $hook,
            'priority' => $priority,
            'acceptedArgs' => $acceptedArgs,
        ];
    }

    /**
     * Retourne la liste des hooks enregistrés via cette instance.
     *
     * @return array<int, array{type: string, hook: string, priority: int, acceptedArgs: int}>
     */
    public function registered(): array
    {
        return $this->registered;
    }
}
