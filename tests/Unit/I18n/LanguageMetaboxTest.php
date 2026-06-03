<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageMetabox;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\TranslationModel;
use PHPUnit\Framework\TestCase;

final class LanguageMetaboxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('get_option')->alias(static fn (string $k, $d = false) => $k === 'oli_languages' ? ['enabled' => ['fr', 'en', 'it', 'es'], 'default' => 'fr'] : $d);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_register_adds_metabox_for_post_and_page(): void
    {
        Functions\expect('add_meta_box')
            ->atLeast()->times(2);

        $renderer = $this->createMock(RendererInterface::class);
        (new LanguageMetabox(new LanguageRegistry(), new TranslationModel(), $renderer))->register();

        $this->addToAssertionCount(1);
    }

    public function test_save_updates_translation_group(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(true);

        Functions\expect('update_post_meta')
            ->once()
            ->with(7, '_oli_translation_group', 'group-X');

        $renderer = $this->createMock(RendererInterface::class);
        $metabox = new LanguageMetabox(new LanguageRegistry(), new TranslationModel(), $renderer);
        $metabox->save(7, ['_oli_lang_nonce' => 'noncev', 'oli_translation_group' => 'group-X']);

        $this->addToAssertionCount(1);
    }

    public function test_save_skips_when_user_cannot_edit(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        Functions\when('wp_verify_nonce')->justReturn(true);

        Functions\expect('update_post_meta')->never();

        $renderer = $this->createMock(RendererInterface::class);
        $metabox = new LanguageMetabox(new LanguageRegistry(), new TranslationModel(), $renderer);
        $metabox->save(7, ['_oli_lang_nonce' => 'noncev', 'oli_translation_group' => 'group-X']);

        $this->addToAssertionCount(1);
    }

    public function test_save_skips_when_nonce_invalid(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(false);

        Functions\expect('update_post_meta')->never();

        $renderer = $this->createMock(RendererInterface::class);
        $metabox = new LanguageMetabox(new LanguageRegistry(), new TranslationModel(), $renderer);
        $metabox->save(7, ['_oli_lang_nonce' => 'noncev', 'oli_translation_group' => 'group-X']);

        $this->addToAssertionCount(1);
    }
}
