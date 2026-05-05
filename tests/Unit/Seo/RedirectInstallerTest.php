<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Seo\RedirectInstaller;
use PHPUnit\Framework\TestCase;

final class RedirectInstallerTest extends TestCase
{
    private object $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        if (!\defined('ABSPATH')) {
            \define('ABSPATH', sys_get_temp_dir() . '/');
        }

        $this->wpdb = new class () {
            public string $prefix = 'wp_';
            public string $lastShowQuery = '';
            public ?string $tableInDb = null;

            public function get_charset_collate(): string
            {
                return 'DEFAULT CHARACTER SET utf8mb4';
            }

            public function prepare(string $query, mixed ...$args): string
            {
                $sql = $query;
                foreach ($args as $arg) {
                    $sql = preg_replace('/%s/', "'" . (string) $arg . "'", $sql, 1) ?? $sql;
                }
                return $sql;
            }

            public function get_var(string $query): ?string
            {
                $this->lastShowQuery = $query;
                return $this->tableInDb;
            }
        };
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testEnsureInstalledSkipsWhenVersionMatches(): void
    {
        Functions\when('get_option')->justReturn(RedirectInstaller::DB_VERSION);
        Functions\expect('dbDelta')->never();
        Functions\expect('update_option')->never();

        (new RedirectInstaller($this->wpdb()))->ensureInstalled();

        $this->addToAssertionCount(1);
    }

    public function testEnsureInstalledRunsMigrationWhenVersionDiffers(): void
    {
        Functions\when('get_option')->justReturn('');

        $captured = '';
        Functions\when('dbDelta')->alias(static function (string $sql) use (&$captured): array {
            $captured = $sql;
            return [];
        });
        Functions\expect('update_option')
            ->once()
            ->with(RedirectInstaller::OPTION_KEY, RedirectInstaller::DB_VERSION);

        (new RedirectInstaller($this->wpdb()))->ensureInstalled();

        self::assertStringContainsString('CREATE TABLE wp_oli_redirects', $captured);
        self::assertStringContainsString('source_idx', $captured);
    }

    public function testTableExistsReturnsTrueWhenWpdbSeesTable(): void
    {
        $this->wpdb->tableInDb = 'wp_oli_redirects'; // @phpstan-ignore-line property.notFound

        self::assertTrue((new RedirectInstaller($this->wpdb()))->tableExists());
    }

    public function testTableExistsReturnsFalseWhenWpdbReturnsNull(): void
    {
        $this->wpdb->tableInDb = null; // @phpstan-ignore-line property.notFound

        self::assertFalse((new RedirectInstaller($this->wpdb()))->tableExists());
    }

    public function testTableNameIncludesWpdbPrefix(): void
    {
        self::assertSame('wp_oli_redirects', (new RedirectInstaller($this->wpdb()))->tableName());
    }

    /**
     * Helper de typage : passe le stub en `\wpdb` pour PHPStan.
     *
     * @phpstan-return \wpdb
     */
    private function wpdb(): object
    {
        /** @phpstan-var \wpdb $wpdb */
        $wpdb = $this->wpdb;
        return $wpdb;
    }
}
