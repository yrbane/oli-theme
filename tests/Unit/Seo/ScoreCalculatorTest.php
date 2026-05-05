<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;
use OliTheme\Seo\ImageAuditor;
use OliTheme\Seo\KeywordAnalyzer;
use OliTheme\Seo\ReadabilityAnalyzer;
use OliTheme\Seo\ScoreCalculator;
use OliTheme\Seo\SeoMeta;
use PHPUnit\Framework\TestCase;

/**
 * Tests de ScoreCalculator.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class ScoreCalculatorTest extends TestCase
{
    private ScoreCalculator $calculator;
    private Language $lang;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->calculator = new ScoreCalculator(
            new ReadabilityAnalyzer(),
            new KeywordAnalyzer(),
            new ImageAuditor(),
        );
        $this->lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testScoresPerfectPost(): void
    {
        Functions\when('apply_filters')->returnArg(2);

        // Contenu simple d'environ 300 mots avec le mot-clé "yoga" présent 2-3 fois.
        $content = $this->buildPerfectContent('yoga');

        $meta = new SeoMeta(
            title: 'Le yoga pour débutants : guide complet',      // 40 chars, dans la plage
            description: 'Découvrez le yoga pour débutants avec ce guide complet de 130 caractères qui vous explique les bases du yoga facilement.',
            focusKeyword: 'yoga',
            ogImageId: 42,
            canonical: 'https://example.com/yoga-debutants',
        );

        $post = new PostEntity(
            id: 1,
            type: 'post',
            title: 'Le yoga pour débutants',
            content: $content,
            excerpt: null,
            slug: 'guide-yoga-debutants',
            language: $this->lang,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/yoga-debutants',
            publishedAt: new DateTimeImmutable('2024-01-01'),
            updatedAt: null,
            author: null,
        );

        $score = $this->calculator->calculate($meta, $post);
        self::assertGreaterThanOrEqual(90, $score);
    }

    public function testScoresLowOnEmptyMeta(): void
    {
        Functions\when('apply_filters')->returnArg(2);

        $meta = new SeoMeta(); // tout null / défauts

        $post = new PostEntity(
            id: 2,
            type: 'post',
            title: 'A',
            content: '<p>Court.</p>',
            excerpt: null,
            slug: 'a',
            language: $this->lang,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/a',
            publishedAt: new DateTimeImmutable('2024-01-01'),
            updatedAt: null,
            author: null,
        );

        $score = $this->calculator->calculate($meta, $post);
        self::assertLessThan(30, $score);
    }

    public function testFilterCanOverrideRules(): void
    {
        // Le filtre remplace toutes les règles par une seule règle valant 100.
        Functions\when('apply_filters')->alias(
            static function (string $tag, array $defaults): array {
                if ($tag === 'oli_seo_score_rules') {
                    return ['canonical_set' => 100];
                }
                return $defaults;
            },
        );

        // canonical est défini → la règle unique est validée → score = round(100/100*100) = 100.
        $meta = new SeoMeta(canonical: 'https://example.com/test');
        $post = new PostEntity(
            id: 3,
            type: 'post',
            title: 'Test',
            content: '',
            excerpt: null,
            slug: 'test',
            language: $this->lang,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/test',
            publishedAt: new DateTimeImmutable('2024-01-01'),
            updatedAt: null,
            author: null,
        );

        $score = $this->calculator->calculate($meta, $post);
        self::assertSame(100, $score);
    }

    /**
     * Génère un contenu HTML optimisé pour le test "perfect post".
     *
     * Le contenu contient :
     * - Un H1 avec le mot-clé.
     * - Le mot-clé 2 fois sur ~300 mots (densité ~0,67%, dans [0.5, 2.5]).
     * - Des phrases courtes pour un bon score Flesch.
     * - Aucune image (audit vide = règle images_have_alt validée).
     */
    private function buildPerfectContent(string $keyword): string
    {
        $words = str_repeat('Le chat dort sur le canapé. Il est content. ', 35);

        return \sprintf(
            '<h1>Guide de %s pour débutants</h1><p>Le %s est une pratique bénéfique. %s</p>',
            $keyword,
            $keyword,
            $words,
        );
    }
}
