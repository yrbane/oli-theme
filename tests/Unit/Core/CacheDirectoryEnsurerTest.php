<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use OliTheme\Core\CacheDirectoryEnsurer;
use PHPUnit\Framework\TestCase;

/**
 * Tests de l'utilitaire qui crée et vérifie l'écriture du cache compilé.
 *
 * Issue : github.com/yrbane/oli-theme/issues/1 — fatal mkdir() permission denied.
 * Le ensurer doit retourner un état exploitable (succès / message d'erreur)
 * sans jamais lever d'exception, pour que le bootstrap puisse afficher un
 * admin_notice plutôt qu'un fatal.
 */
final class CacheDirectoryEnsurerTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = sys_get_temp_dir() . '/oli-cache-ensurer-' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->base)) {
            $this->removeRecursive($this->base);
        }
        parent::tearDown();
    }

    public function test_ensure_creates_directory_when_missing(): void
    {
        $ensurer = new CacheDirectoryEnsurer();
        $path    = $this->base . '/templates';

        $result = $ensurer->ensure($path);

        $this->assertTrue($result);
        $this->assertDirectoryExists($path);
        $this->assertNull($ensurer->getError());
    }

    public function test_ensure_returns_true_when_directory_already_exists_and_writable(): void
    {
        mkdir($this->base . '/templates', 0o755, true);
        $ensurer = new CacheDirectoryEnsurer();

        $result = $ensurer->ensure($this->base . '/templates');

        $this->assertTrue($result);
        $this->assertNull($ensurer->getError());
    }

    public function test_ensure_returns_false_and_records_error_when_path_is_a_file(): void
    {
        mkdir($this->base, 0o755, true);
        $blocker = $this->base . '/templates';
        file_put_contents($blocker, 'oops');

        $ensurer = new CacheDirectoryEnsurer();
        $result  = $ensurer->ensure($blocker);

        $this->assertFalse($result);
        $this->assertNotNull($ensurer->getError());
        $error = $ensurer->getError();
        $this->assertNotNull($error);
        $this->assertStringContainsString($blocker, $error);
    }

    public function test_ensure_returns_false_when_directory_not_writable(): void
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $this->markTestSkipped('chmod readonly only meaningful on POSIX.');
        }
        if (\function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('Root contourne les permissions POSIX.');
        }

        mkdir($this->base, 0o755, true);
        $readonly = $this->base . '/readonly';
        mkdir($readonly, 0o500);

        $ensurer = new CacheDirectoryEnsurer();
        $result  = $ensurer->ensure($readonly . '/templates');

        $this->assertFalse($result);
        $this->assertNotNull($ensurer->getError());

        chmod($readonly, 0o755);
    }

    public function test_get_error_returns_null_initially(): void
    {
        $this->assertNull((new CacheDirectoryEnsurer())->getError());
    }

    private function removeRecursive(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);

            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $this->removeRecursive($path . '/' . $entry);
        }
        @rmdir($path);
    }
}
