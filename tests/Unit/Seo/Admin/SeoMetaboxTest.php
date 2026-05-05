<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\RendererInterface;
use OliTheme\Seo\Admin\SeoMetabox;
use OliTheme\Seo\SeoMeta;
use OliTheme\Seo\SeoMetaModelInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de SeoMetabox (admin métabox SEO par post).
 *
 * @package OliTheme\Tests\Unit\Seo\Admin
 *
 * @since 1.0.0
 */
final class SeoMetaboxTest extends TestCase
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

    public function testRegisterAddsMetaBoxesForAllPostTypes(): void
    {
        Functions\when('__')->returnArg(1);

        $calls = [];
        Functions\when('add_meta_box')->alias(
            static function (string $id, string $title, callable $callback, string $screen) use (&$calls): void {
                $calls[] = $screen;
            },
        );

        $metaModel = $this->createMock(SeoMetaModelInterface::class);
        $renderer  = $this->createMock(RendererInterface::class);

        (new SeoMetabox($metaModel, $renderer))->register();

        self::assertCount(3, $calls);
        self::assertContains('post', $calls);
        self::assertContains('page', $calls);
        self::assertContains('oli_event', $calls);
    }

    public function testSavePersistsMetaWhenNonceValid(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('sanitize_text_field')->returnArg(1);

        $metaModel = $this->createMock(SeoMetaModelInterface::class);
        $metaModel->expects(self::once())
            ->method('save')
            ->with(
                42,
                self::callback(static fn (SeoMeta $meta): bool => $meta->title === 'Mon titre SEO'
                        && $meta->description === 'Ma description'
                        && $meta->focusKeyword === 'yoga'
                        && $meta->noindex === true
                        && $meta->nofollow === false
                        && $meta->twitterCardType === 'summary'),
            );

        $renderer = $this->createMock(RendererInterface::class);

        $postData = [
            'oli_seo_meta_nonce'  => 'valid_nonce',
            'seo_title'           => 'Mon titre SEO',
            'seo_description'     => 'Ma description',
            'focus_keyword'       => 'yoga',
            'additional_keywords' => 'pilates, relaxation',
            'twitter_card_type'   => 'summary',
            'noindex'             => '1',
        ];

        (new SeoMetabox($metaModel, $renderer))->save(42, $postData);
    }

    public function testSaveAbortsWhenNonceInvalid(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(false);

        $metaModel = $this->createMock(SeoMetaModelInterface::class);
        $metaModel->expects(self::never())->method('save');

        $renderer = $this->createMock(RendererInterface::class);

        (new SeoMetabox($metaModel, $renderer))->save(42, ['oli_seo_meta_nonce' => 'bad_nonce']);
    }
}
