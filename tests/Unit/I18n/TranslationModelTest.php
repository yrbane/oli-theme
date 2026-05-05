<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\TranslationModel;
use PHPUnit\Framework\TestCase;

final class TranslationModelTest extends TestCase
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

    public function test_get_group_id_returns_meta_value(): void
    {
        Functions\when('get_post_meta')->justReturn('abc-123');
        $model = new TranslationModel();

        self::assertSame('abc-123', $model->getGroupId(7));
    }

    public function test_get_group_id_returns_null_when_meta_empty(): void
    {
        Functions\when('get_post_meta')->justReturn('');
        $model = new TranslationModel();

        self::assertNull($model->getGroupId(7));
    }

    public function test_set_group_id_writes_post_meta(): void
    {
        Functions\expect('update_post_meta')
            ->once()
            ->with(7, '_oli_translation_group', 'abc-123');

        (new TranslationModel())->setGroupId(7, 'abc-123');
        $this->addToAssertionCount(1);
    }

    public function test_create_group_returns_a_uuid_v4_like_string(): void
    {
        $uuid = (new TranslationModel())->createGroupId();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
    }

    public function test_get_translations_maps_lang_code_to_post_id(): void
    {
        Functions\when('get_posts')->justReturn([10, 20]);
        Functions\when('get_post_meta')->alias(static fn (int $postId, string $key, bool $single) => $key === '_oli_translation_group' ? 'group-1' : '');
        Functions\when('wp_get_post_terms')->alias(static function (int $postId, string $taxonomy) {
            $term = new \stdClass();
            $term->slug = $postId === 10 ? 'fr' : 'en';

            return [$term];
        });

        $model = new TranslationModel();
        $translations = $model->getTranslations(10);

        self::assertSame(['fr' => 10, 'en' => 20], $translations);
    }

    public function test_link_writes_same_group_to_both_posts(): void
    {
        Functions\when('get_post_meta')->justReturn('source-group');

        Functions\expect('update_post_meta')
            ->once()
            ->with(20, '_oli_translation_group', 'source-group');

        (new TranslationModel())->link(10, 20);
        $this->addToAssertionCount(1);
    }

    public function test_unlink_deletes_group_meta(): void
    {
        Functions\expect('delete_post_meta')
            ->once()
            ->with(7, '_oli_translation_group');

        (new TranslationModel())->unlink(7);
        $this->addToAssertionCount(1);
    }
}
