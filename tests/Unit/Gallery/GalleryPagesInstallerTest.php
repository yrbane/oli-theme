<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gallery;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Gallery\GalleryPagesInstaller;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\TranslationModelInterface;
use PHPUnit\Framework\TestCase;

final class GalleryPagesInstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg(1);
        if (!\defined('OBJECT')) {
            \define('OBJECT', 'OBJECT');
        }
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testStatusReportsExpectedPagesForEnabledLanguages(): void
    {
        // fr+en activés → 4 pages attendues ; aucune n'existe.
        Functions\when('get_posts')->justReturn([]);

        $installer = new GalleryPagesInstaller($this->registry('fr', 'en'), $this->createMock(TranslationModelInterface::class));
        $status = $installer->status();

        self::assertCount(4, $status);
        $slugs = array_column($status, 'slug');
        self::assertSame(['photos', 'videos', 'photos-en', 'videos-en'], $slugs);
        foreach ($status as $row) {
            self::assertFalse($row['exists']);
        }
    }

    public function testStatusOnlyIncludesEnabledLanguages(): void
    {
        Functions\when('get_posts')->justReturn([]);

        $installer = new GalleryPagesInstaller($this->registry('fr'), $this->createMock(TranslationModelInterface::class));
        $status = $installer->status();

        self::assertSame(['photos', 'videos'], array_column($status, 'slug'));
    }

    public function testStatusDetectsExistingPublishedPage(): void
    {
        Functions\when('get_posts')->alias(static function (array $args): array {
            if ($args['name'] === 'photos') {
                return [(object) ['ID' => 42, 'post_status' => 'publish']];
            }
            return [];
        });

        $installer = new GalleryPagesInstaller($this->registry('fr'), $this->createMock(TranslationModelInterface::class));
        $status = $installer->status();

        $bySlug = array_column($status, null, 'slug');
        self::assertTrue($bySlug['photos']['exists']);
        self::assertSame(42, $bySlug['photos']['page_id']);
        self::assertFalse($bySlug['videos']['exists']);
    }

    public function testInstallMissingCreatesAbsentPagesAndLinksTranslations(): void
    {
        Functions\when('get_posts')->justReturn([]);
        $inserted = [];
        Functions\when('wp_insert_post')->alias(static function (array $data) use (&$inserted): int {
            $id = 100 + \count($inserted);
            $inserted[$data['post_name']] = $id;
            return $id;
        });
        Functions\when('is_wp_error')->justReturn(false);
        $terms = [];
        Functions\when('wp_set_object_terms')->alias(static function (int $id, string $lang) use (&$terms): array {
            $terms[$id] = $lang;
            return [];
        });

        $translations = $this->createMock(TranslationModelInterface::class);
        // photos↔photos-en et videos↔videos-en → 2 liaisons.
        $translations->expects(self::exactly(2))->method('link');

        $installer = new GalleryPagesInstaller($this->registry('fr', 'en'), $translations);
        $created = $installer->installMissing();

        self::assertSame(4, $created);
        self::assertArrayHasKey('photos', $inserted);
        self::assertArrayHasKey('videos-en', $inserted);
        self::assertSame('en', $terms[$inserted['photos-en']]);
    }

    public function testInstallMissingSkipsAlreadyPublishedPages(): void
    {
        Functions\when('get_posts')->alias(static fn (array $args): array => [(object) ['ID' => 7, 'post_status' => 'publish']]);
        Functions\when('wp_set_object_terms')->justReturn([]);
        Functions\expect('wp_insert_post')->never();

        $installer = new GalleryPagesInstaller($this->registry('fr'), $this->createMock(TranslationModelInterface::class));
        $created = $installer->installMissing();

        self::assertSame(0, $created);
    }

    private function registry(string ...$codes): LanguageRegistryInterface
    {
        $langs = array_map(
            static fn (string $c): Language => new Language($c, $c, $c, '', $c, 'ltr'),
            $codes,
        );

        $registry = $this->createMock(LanguageRegistryInterface::class);
        $registry->method('all')->willReturn($langs);
        $registry->method('isEnabled')->willReturnCallback(
            static fn (string $c): bool => \in_array($c, $codes, true),
        );

        return $registry;
    }
}
