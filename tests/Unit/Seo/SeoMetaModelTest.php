<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Seo\SeoMeta;
use OliTheme\Seo\SeoMetaModel;
use PHPUnit\Framework\TestCase;

/**
 * Tests du modèle SeoMetaModel.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class SeoMetaModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFindReturnsEmptyMetaWhenNoData(): void
    {
        Functions\when('get_post_meta')->justReturn('');

        $model = new SeoMetaModel();
        $meta = $model->find(1);

        self::assertNull($meta->title);
        self::assertNull($meta->description);
        self::assertNull($meta->focusKeyword);
        self::assertSame([], $meta->additionalKeywords);
        self::assertNull($meta->ogImageId);
        self::assertSame('summary_large_image', $meta->twitterCardType);
        self::assertFalse($meta->noindex);
        self::assertFalse($meta->nofollow);
        self::assertNull($meta->canonical);
        self::assertNull($meta->priority);
        self::assertNull($meta->changefreq);
        self::assertNull($meta->readabilityScore);
        self::assertNull($meta->seoScore);
    }

    public function testFindBuildsFromMetaKeys(): void
    {
        Functions\when('get_post_meta')->alias(static function (int $postId, string $key, bool $single) {
            return match ($key) {
                '_oli_seo_title' => 'Mon titre',
                '_oli_seo_description' => 'Ma description',
                '_oli_seo_focus_keyword' => 'php',
                '_oli_seo_additional_keywords' => ['wordpress', 'thème'],
                '_oli_seo_og_image_id' => '42',
                '_oli_seo_twitter_card_type' => 'summary',
                '_oli_seo_noindex' => '1',
                '_oli_seo_nofollow' => '1',
                '_oli_seo_canonical' => 'https://example.com/page',
                '_oli_seo_priority' => '0.8',
                '_oli_seo_changefreq' => 'weekly',
                '_oli_seo_readability_score' => '75',
                '_oli_seo_seo_score' => '90',
                default => '',
            };
        });

        $model = new SeoMetaModel();
        $meta = $model->find(1);

        self::assertSame('Mon titre', $meta->title);
        self::assertSame('Ma description', $meta->description);
        self::assertSame('php', $meta->focusKeyword);
        self::assertSame(['wordpress', 'thème'], $meta->additionalKeywords);
        self::assertSame(42, $meta->ogImageId);
        self::assertSame('summary', $meta->twitterCardType);
        self::assertTrue($meta->noindex);
        self::assertTrue($meta->nofollow);
        self::assertSame('https://example.com/page', $meta->canonical);
        self::assertSame(0.8, $meta->priority);
        self::assertSame('weekly', $meta->changefreq);
        self::assertSame(75, $meta->readabilityScore);
        self::assertSame(90, $meta->seoScore);
    }

    public function testSavePersistsAllFields(): void
    {
        $calls = [];

        Functions\when('update_post_meta')->alias(static function (int $postId, string $key, mixed $value) use (&$calls): void {
            $calls[$key] = $value;
        });
        Functions\when('delete_post_meta')->justReturn(true);

        $meta = new SeoMeta(
            title: 'Mon titre',
            description: 'Ma description',
            focusKeyword: 'php',
            additionalKeywords: ['wordpress'],
            ogImageId: 42,
            twitterCardType: 'summary',
            noindex: true,
            nofollow: false,
            canonical: 'https://example.com/page',
            priority: 0.8,
            changefreq: 'weekly',
            readabilityScore: 75,
            seoScore: 90,
        );

        $model = new SeoMetaModel();
        $model->save(1, $meta);

        self::assertSame('Mon titre', $calls['_oli_seo_title']);
        self::assertSame('Ma description', $calls['_oli_seo_description']);
        self::assertSame('php', $calls['_oli_seo_focus_keyword']);
        self::assertSame(['wordpress'], $calls['_oli_seo_additional_keywords']);
        self::assertSame(42, $calls['_oli_seo_og_image_id']);
        self::assertSame('summary', $calls['_oli_seo_twitter_card_type']);
        self::assertSame('1', $calls['_oli_seo_noindex']);
        self::assertSame('', $calls['_oli_seo_nofollow']);
        self::assertSame('https://example.com/page', $calls['_oli_seo_canonical']);
        self::assertSame(0.8, $calls['_oli_seo_priority']);
        self::assertSame('weekly', $calls['_oli_seo_changefreq']);
        self::assertSame(75, $calls['_oli_seo_readability_score']);
        self::assertSame(90, $calls['_oli_seo_seo_score']);
    }

    public function testGetMetaReturnsDefaultWhenAbsent(): void
    {
        Functions\when('get_post_meta')->justReturn('');

        $model = new SeoMetaModel();
        $result = $model->getMeta(1, '_oli_seo_title', 'default');

        self::assertSame('default', $result);
    }
}
