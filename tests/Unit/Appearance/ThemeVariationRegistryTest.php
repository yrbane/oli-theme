<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Appearance;

use OliTheme\Appearance\ThemeVariationRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ThemeVariationRegistry.
 *
 * @package OliTheme\Tests\Unit\Appearance
 *
 * @since 1.0.0
 */
final class ThemeVariationRegistryTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/oli-variations-' . uniqid();
        mkdir($this->dir, recursive: true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->dir)) {
            foreach ((array) glob($this->dir . '/*') as $file) {
                if (\is_string($file) && is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->dir);
        }
        parent::tearDown();
    }

    public function testListsCssFilesAsVariations(): void
    {
        file_put_contents($this->dir . '/summer.css', 'body{color:red}');
        file_put_contents($this->dir . '/winter.css', 'body{color:blue}');

        $variations = (new ThemeVariationRegistry($this->dir))->all();

        self::assertCount(2, $variations);
        $ids = array_column($variations, 'id');
        self::assertContains('summer', $ids);
        self::assertContains('winter', $ids);
    }

    public function testIgnoresNonCssFiles(): void
    {
        file_put_contents($this->dir . '/summer.css', 'body{color:red}');
        file_put_contents($this->dir . '/notes.txt', 'ignore me');
        file_put_contents($this->dir . '/.gitkeep', '');

        $variations = (new ThemeVariationRegistry($this->dir))->all();

        self::assertCount(1, $variations);
        self::assertSame('summer', $variations[0]['id']);
    }

    public function testParsesLabelFromHeaderComment(): void
    {
        file_put_contents(
            $this->dir . '/summer.css',
            "/* Theme Variation: Été ensoleillé */\nbody{color:gold}",
        );

        $variations = (new ThemeVariationRegistry($this->dir))->all();

        self::assertSame('summer', $variations[0]['id']);
        self::assertSame('Été ensoleillé', $variations[0]['label']);
    }

    public function testFallsBackToHumanizedFilenameWhenNoHeader(): void
    {
        file_put_contents($this->dir . '/dark-mode.css', 'body{color:white}');

        $variations = (new ThemeVariationRegistry($this->dir))->all();

        self::assertSame('Dark mode', $variations[0]['label']);
    }

    public function testReturnsEmptyArrayWhenDirectoryDoesNotExist(): void
    {
        $variations = (new ThemeVariationRegistry($this->dir . '/missing'))->all();

        self::assertSame([], $variations);
    }

    public function testHasReturnsTrueForExistingId(): void
    {
        file_put_contents($this->dir . '/summer.css', '');

        $registry = new ThemeVariationRegistry($this->dir);

        self::assertTrue($registry->has('summer'));
        self::assertFalse($registry->has('winter'));
    }

    public function testFileNameForReturnsRelativePathOrNull(): void
    {
        file_put_contents($this->dir . '/summer.css', '');

        $registry = new ThemeVariationRegistry($this->dir);

        self::assertSame('summer.css', $registry->fileNameFor('summer'));
        self::assertNull($registry->fileNameFor('missing'));
    }

    public function testSortsVariationsAlphabeticallyByLabel(): void
    {
        file_put_contents($this->dir . '/zulu.css', '');
        file_put_contents($this->dir . '/alpha.css', '');
        file_put_contents($this->dir . '/mike.css', '');

        $variations = (new ThemeVariationRegistry($this->dir))->all();

        self::assertSame(['alpha', 'mike', 'zulu'], array_column($variations, 'id'));
    }
}
