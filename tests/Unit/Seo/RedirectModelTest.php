<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use OliTheme\Seo\RedirectEntity;
use OliTheme\Seo\RedirectModel;
use PHPUnit\Framework\TestCase;

/**
 * Tests de RedirectModel.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class RedirectModelTest extends TestCase
{
    public function testFindBySourceReturnsNullWhenAbsent(): void
    {
        $wpdb = $this->makeWpdb(rowToReturn: null);

        // @phpstan-ignore argument.type
        $model = new RedirectModel($wpdb);
        $result = $model->findBySource('/inexistant');

        self::assertNull($result);
    }

    public function testFindBySourceReturnsHydratedEntity(): void
    {
        $row = $this->makeRow(id: 3, source: '/old', target: 'https://example.com/new', code: 302, hits: 5);
        $wpdb = $this->makeWpdb(rowToReturn: $row);

        // @phpstan-ignore argument.type
        $model = new RedirectModel($wpdb);
        $entity = $model->findBySource('/old');

        self::assertInstanceOf(RedirectEntity::class, $entity);
        self::assertSame(3, $entity->id);
        self::assertSame('/old', $entity->source);
        self::assertSame('https://example.com/new', $entity->target);
        self::assertSame(302, $entity->code);
        self::assertSame(5, $entity->hits);
    }

    public function testFindAllReturnsArrayOfEntities(): void
    {
        $rows = [
            $this->makeRow(id: 1, source: '/page-a'),
            $this->makeRow(id: 2, source: '/page-b'),
        ];
        $wpdb = $this->makeWpdb(rowsToReturn: $rows);

        // @phpstan-ignore argument.type
        $model = new RedirectModel($wpdb);
        $entities = $model->findAll();

        self::assertCount(2, $entities);
        self::assertInstanceOf(RedirectEntity::class, $entities[0]);
        self::assertInstanceOf(RedirectEntity::class, $entities[1]);
        self::assertSame('/page-a', $entities[0]->source);
        self::assertSame('/page-b', $entities[1]->source);
    }

    public function testSaveInsertsNewRecord(): void
    {
        /** @var array<array<string,mixed>> $insertCalls */
        $insertCalls = [];
        $row = $this->makeRow(id: 10, source: '/new-source', target: 'https://example.com/target', code: 301);

        // get_row retourne null au premier appel (pas d'existant), puis la ligne insérée
        $callCount = 0;
        $wpdb = new class ($row, $insertCalls, $callCount) {
            public string $prefix = 'wp_';

            /** @var array<array<string,mixed>> */
            private array $insertCalls;

            public function __construct(
                private readonly \stdClass $row,
                array &$insertCalls,
                private int &$callCount,
            ) {
                $this->insertCalls = &$insertCalls;
            }

            public function prepare(string $query, mixed ...$args): string
            {
                $replaced = str_replace(['%s', '%d'], ["'%s'", '%d'], $query);

                return vsprintf($replaced, $args);
            }

            public function get_row(string $query): ?\stdClass
            {
                // Premier appel : pas d'existant ; deuxième appel : retourne la ligne créée
                ++$this->callCount;

                return $this->callCount === 1 ? null : $this->row;
            }

            /** @return \stdClass[] */
            public function get_results(string $query): array
            {
                return [];
            }

            /** @param array<string,mixed> $data */
            public function insert(string $table, array $data, mixed $formats = []): int
            {
                $this->insertCalls[] = $data;

                return 1;
            }

            /**
             * @param array<string,mixed> $data
             * @param array<string,mixed> $where
             */
            public function update(string $table, array $data, array $where, mixed $dataFormats = [], mixed $whereFormats = []): int
            {
                return 1;
            }

            /** @param array<string,mixed> $where */
            public function delete(string $table, array $where, mixed $whereFormats = []): int
            {
                return 1;
            }

            public function query(string $query): int
            {
                return 1;
            }
        };

        // @phpstan-ignore argument.type
        $model = new RedirectModel($wpdb);
        $entity = $model->save('/new-source', 'https://example.com/target', 301);

        self::assertCount(1, $insertCalls);
        self::assertSame('/new-source', $insertCalls[0]['source']);
        self::assertSame('https://example.com/target', $insertCalls[0]['target']);
        self::assertSame(301, $insertCalls[0]['code']);
        self::assertInstanceOf(RedirectEntity::class, $entity);
    }

    public function testIncrementHitsRunsUpdateQuery(): void
    {
        /** @var string[] $queryCalls */
        $queryCalls = [];
        $wpdb = $this->makeWpdb(queryCalls: $queryCalls);

        // @phpstan-ignore argument.type
        $model = new RedirectModel($wpdb);
        $model->incrementHits(42);

        self::assertCount(1, $queryCalls);
        self::assertStringContainsString('hits = hits + 1', $queryCalls[0]);
        self::assertStringContainsString('42', $queryCalls[0]);
    }
    /**
     * Crée une ligne stdClass représentant une redirection en base.
     */
    private function makeRow(
        int $id = 1,
        string $source = '/old',
        string $target = 'https://example.com/new',
        int $code = 301,
        int $hits = 0,
        string $createdAt = '2026-01-01 00:00:00',
    ): \stdClass {
        $row = new \stdClass();
        $row->id = $id;
        $row->source = $source;
        $row->target = $target;
        $row->code = $code;
        $row->hits = $hits;
        $row->created_at = $createdAt;

        return $row;
    }

    /**
     * Construit un stub wpdb minimal pour les tests.
     *
     * @param \stdClass|null $rowToReturn Ligne retournée par get_row().
     * @param \stdClass[] $rowsToReturn Lignes retournées par get_results().
     * @param array<array<string,mixed>> &$insertCalls Tableau capturant les appels insert().
     * @param string[] &$queryCalls Tableau capturant les appels query().
     */
    private function makeWpdb(
        ?\stdClass $rowToReturn = null,
        array $rowsToReturn = [],
        array &$insertCalls = [],
        array &$queryCalls = [],
    ): object {
        return new class ($rowToReturn, $rowsToReturn, $insertCalls, $queryCalls) {
            public string $prefix = 'wp_';

            /** @var \stdClass[] */
            private array $rowsToReturn;

            /** @var array<array<string,mixed>> */
            private array $insertCalls;

            /** @var string[] */
            private array $queryCalls;

            /**
             * @param \stdClass[] $rowsToReturn
             * @param array<array<string,mixed>> &$insertCalls
             * @param string[] &$queryCalls
             */
            public function __construct(
                private ?\stdClass $rowToReturn,
                array $rowsToReturn,
                array &$insertCalls,
                array &$queryCalls,
            ) {
                $this->rowsToReturn = $rowsToReturn;
                $this->insertCalls = &$insertCalls;
                $this->queryCalls = &$queryCalls;
            }

            public function prepare(string $query, mixed ...$args): string
            {
                $replaced = str_replace(['%s', '%d'], ["'%s'", '%d'], $query);

                return vsprintf($replaced, $args);
            }

            public function get_row(string $query): ?\stdClass
            {
                return $this->rowToReturn;
            }

            /** @return \stdClass[] */
            public function get_results(string $query): array
            {
                return $this->rowsToReturn;
            }

            /** @param array<string,mixed> $data */
            public function insert(string $table, array $data, mixed $formats = []): int
            {
                $this->insertCalls[] = $data;

                return 1;
            }

            /**
             * @param array<string,mixed> $data
             * @param array<string,mixed> $where
             */
            public function update(string $table, array $data, array $where, mixed $dataFormats = [], mixed $whereFormats = []): int
            {
                return 1;
            }

            /** @param array<string,mixed> $where */
            public function delete(string $table, array $where, mixed $whereFormats = []): int
            {
                return 1;
            }

            public function query(string $query): int
            {
                $this->queryCalls[] = $query;

                return 1;
            }
        };
    }
}
