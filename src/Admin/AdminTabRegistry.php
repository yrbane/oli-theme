<?php

declare(strict_types=1);

namespace OliTheme\Admin;

/**
 * Registre des onglets de la page de réglages unifiée.
 *
 * Les modules y publient leurs onglets ; la page hôte interroge le registre
 * pour construire la navigation et déléguer le rendu.
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
final class AdminTabRegistry
{
    /** @var list<AdminTabInterface> */
    private array $tabs = [];

    public function add(AdminTabInterface $tab): void
    {
        $this->tabs[] = $tab;
    }

    /**
     * Onglets d'un groupe, dans l'ordre d'insertion.
     *
     * @return list<AdminTabInterface>
     */
    public function forGroup(string $group): array
    {
        return array_values(array_filter(
            $this->tabs,
            static fn (AdminTabInterface $t): bool => $t->group() === $group,
        ));
    }

    public function find(string $group, string $id): ?AdminTabInterface
    {
        foreach ($this->tabs as $tab) {
            if ($tab->group() === $group && $tab->id() === $id) {
                return $tab;
            }
        }
        return null;
    }

    public function firstOfGroup(string $group): ?AdminTabInterface
    {
        return $this->forGroup($group)[0] ?? null;
    }

    /**
     * Groupes contenant au moins un onglet, dans l'ordre d'AdminGroups.
     *
     * @return array<string, string> id => libellé
     */
    public function groupsWithTabs(): array
    {
        $result = [];
        foreach (AdminGroups::all() as $id => $label) {
            if ($this->forGroup($id) !== []) {
                $result[$id] = $label;
            }
        }
        return $result;
    }
}
