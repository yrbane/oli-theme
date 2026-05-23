<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\TranslationAuditor;
use OliTheme\I18n\TranslationModelInterface;
use PHPUnit\Framework\TestCase;

final class TranslationAuditorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg(1);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testReportsContentMissingALanguage(): void
    {
        // Une page FR (id 10) sans traduction EN.
        $this->stubPosts([['ID' => 10, 'post_title' => 'À propos', 'post_type' => 'page']]);
        Functions\when('wp_get_post_terms')->justReturn([(object) ['slug' => 'fr']]);

        $translations = $this->createMock(TranslationModelInterface::class);
        $translations->method('getTranslations')->willReturn([]); // pas de groupe

        $rows = (new TranslationAuditor($this->registry('fr', 'en'), $translations))->audit();

        self::assertCount(1, $rows);
        self::assertSame(10, $rows[0]['post_id']);
        self::assertSame(['fr'], $rows[0]['present']);
        self::assertSame(['en'], $rows[0]['missing']);
    }

    public function testFullyLocalizedContentIsNotReported(): void
    {
        $this->stubPosts([
            ['ID' => 10, 'post_title' => 'Accueil', 'post_type' => 'page'],
            ['ID' => 11, 'post_title' => 'Home', 'post_type' => 'page'],
        ]);
        Functions\when('wp_get_post_terms')->justReturn([(object) ['slug' => 'fr']]);

        $translations = $this->createMock(TranslationModelInterface::class);
        // Les deux posts appartiennent au même groupe fr+en complet.
        $translations->method('getTranslations')->willReturn(['fr' => 10, 'en' => 11]);

        $rows = (new TranslationAuditor($this->registry('fr', 'en'), $translations))->audit();

        self::assertSame([], $rows);
    }

    public function testGroupMembersAreReportedOnce(): void
    {
        // Groupe fr(10)+en(11) mais it manquant → reporté une seule fois.
        $this->stubPosts([
            ['ID' => 10, 'post_title' => 'Accueil', 'post_type' => 'page'],
            ['ID' => 11, 'post_title' => 'Home', 'post_type' => 'page'],
        ]);
        Functions\when('wp_get_post_terms')->justReturn([(object) ['slug' => 'fr']]);

        $translations = $this->createMock(TranslationModelInterface::class);
        $translations->method('getTranslations')->willReturn(['fr' => 10, 'en' => 11]);

        $rows = (new TranslationAuditor($this->registry('fr', 'en', 'it'), $translations))->audit();

        self::assertCount(1, $rows);
        self::assertSame(['it'], $rows[0]['missing']);
    }

    public function testInstallMissingDraftsCreatesLinkedDrafts(): void
    {
        $this->stubPosts([['ID' => 10, 'post_title' => 'À propos', 'post_type' => 'page']]);
        Functions\when('wp_get_post_terms')->justReturn([(object) ['slug' => 'fr']]);
        Functions\when('is_wp_error')->justReturn(false);

        $created = [];
        Functions\when('wp_insert_post')->alias(static function (array $data) use (&$created): int {
            $id = 200 + \count($created);
            $created[] = $data;
            return $id;
        });
        $terms = [];
        Functions\when('wp_set_object_terms')->alias(static function (int $id, string $lang) use (&$terms): array {
            $terms[$id] = $lang;
            return [];
        });

        $translations = $this->createMock(TranslationModelInterface::class);
        $translations->method('getTranslations')->willReturn([]);
        $translations->expects(self::once())->method('link')->with(10, 200);

        $count = (new TranslationAuditor($this->registry('fr', 'en'), $translations))->installMissingDrafts();

        self::assertSame(1, $count);
        self::assertSame('page', $created[0]['post_type']);
        self::assertSame('draft', $created[0]['post_status']);
        self::assertSame('en', $terms[200]);
    }

    private function registry(string ...$codes): LanguageRegistryInterface
    {
        $langs = array_map(
            static fn (string $c): Language => new Language($c, $c, $c, '', $c, 'ltr'),
            $codes,
        );
        $registry = $this->createMock(LanguageRegistryInterface::class);
        $registry->method('all')->willReturn($langs);

        return $registry;
    }

    /**
     * @param list<array{ID: int, post_title: string, post_type: string}> $posts
     */
    private function stubPosts(array $posts): void
    {
        Functions\when('get_posts')->justReturn(array_map(
            static fn (array $p): object => (object) $p,
            $posts,
        ));
    }
}
