<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Modèle de gestion des redirections HTTP via wpdb.
 *
 * Opère sur la table `{prefix}oli_redirects` créée à l'activation du thème.
 * Aucun appel WordPress ne fuit hors de cette classe.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class RedirectModel implements RedirectModelInterface
{
    /**
     * @param \wpdb $wpdb Instance de la base de données WordPress.
     *
     * @phpstan-param \wpdb $wpdb
     */
    public function __construct(
        private readonly object $wpdb,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function findBySource(string $source): ?RedirectEntity
    {
        /** @var \stdClass|null $row */
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(\sprintf('SELECT * FROM `%s` WHERE source = %%s LIMIT 1', $this->table()), $source),
        );

        return $row !== null ? $this->hydrate($row) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(\sprintf('SELECT * FROM `%s` ORDER BY id DESC LIMIT %%d OFFSET %%d', $this->table()), $limit, $offset),
        );

        if (!\is_array($rows)) {
            return [];
        }

        /** @var \stdClass[] $rows */
        return array_map(fn ($r) => $this->hydrate($r), $rows);
    }

    /**
     * {@inheritDoc}
     */
    public function save(string $source, string $target, int $code = 301): RedirectEntity
    {
        $existing = $this->findBySource($source);

        if ($existing !== null) {
            $this->wpdb->update(
                $this->table(),
                ['target' => $target, 'code' => $code],
                ['id' => $existing->id],
                ['%s', '%d'],
                ['%d'],
            );

            /** @var RedirectEntity */
            return $this->findBySource($source);
        }

        $this->wpdb->insert(
            $this->table(),
            ['source' => $source, 'target' => $target, 'code' => $code, 'hits' => 0, 'created_at' => gmdate('Y-m-d H:i:s')],
            ['%s', '%s', '%d', '%d', '%s'],
        );

        /** @var RedirectEntity */
        return $this->findBySource($source);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): void
    {
        $this->wpdb->delete($this->table(), ['id' => $id], ['%d']);
    }

    /**
     * {@inheritDoc}
     */
    public function incrementHits(int $id): void
    {
        $query = $this->wpdb->prepare(\sprintf('UPDATE `%s` SET hits = hits + 1 WHERE id = %%d', $this->table()), $id);

        if ($query !== null) {
            $this->wpdb->query($query);
        }
    }

    /**
     * Retourne le nom complet de la table des redirections.
     */
    private function table(): string
    {
        return $this->wpdb->prefix . 'oli_redirects';
    }

    /**
     * Hydrate un objet ligne de base en RedirectEntity.
     */
    private function hydrate(\stdClass $row): RedirectEntity
    {
        return new RedirectEntity(
            id: (int) $row->id,
            source: (string) $row->source,
            target: (string) $row->target,
            code: (int) $row->code,
            hits: (int) $row->hits,
            createdAt: new \DateTimeImmutable((string) ($row->created_at ?? 'now')),
        );
    }
}
