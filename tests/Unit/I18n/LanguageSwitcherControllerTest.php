<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\RequestContext;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageSwitcherController;
use OliTheme\I18n\LanguageSwitcherItem;
use OliTheme\I18n\LanguageSwitcherViewModel;
use OliTheme\I18n\TranslationModel;
use PHPUnit\Framework\TestCase;

final class LanguageSwitcherControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('get_option')->justReturn(false);
        Functions\when('home_url')->alias(static fn (string $path = '') => 'https://example.test' . $path);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_view_model_lists_all_enabled_languages(): void
    {
        $controller = $this->buildController('fr');
        $vm = $controller->build(0);

        self::assertCount(4, $vm->items);
        self::assertSame('fr', $vm->current->code);
    }

    public function test_view_model_marks_current_language(): void
    {
        $controller = $this->buildController('en');
        $vm = $controller->build(0);

        $current = array_values(array_filter($vm->items, static fn ($i) => $i->isCurrent));
        self::assertCount(1, $current);
        self::assertSame('en', $current[0]->code);
    }

    public function test_view_model_url_uses_translation_when_available(): void
    {
        Functions\when('get_post_meta')->justReturn('group-A');
        Functions\when('get_posts')->justReturn([10, 20]);
        Functions\when('wp_get_post_terms')->alias(static function (int $postId) {
            $term = new \stdClass();
            $term->slug = $postId === 10 ? 'fr' : 'en';

            return [$term];
        });
        Functions\when('get_permalink')->alias(static fn (int $id) => "https://example.test/post-{$id}");

        $controller = $this->buildController('fr');
        $vm = $controller->build(10);
        $en = $this->itemByCode($vm, 'en');

        self::assertSame('https://example.test/post-20', $en->url);
    }

    public function test_view_model_url_falls_back_to_home_when_no_translation(): void
    {
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_posts')->justReturn([]);

        $controller = $this->buildController('fr');
        $vm = $controller->build(10);
        $en = $this->itemByCode($vm, 'en');

        self::assertSame('https://example.test/en/', $en->url);
    }

    private function buildController(string $current): LanguageSwitcherController
    {
        $request = new RequestContext(query: ['oli_lang' => $current]);
        $registry = new LanguageRegistry();
        $resolver = new LanguageResolver($registry, $request);

        return new LanguageSwitcherController($registry, $resolver, new TranslationModel());
    }

    private function itemByCode(LanguageSwitcherViewModel $vm, string $code): LanguageSwitcherItem
    {
        foreach ($vm->items as $item) {
            if ($item->code === $code) {
                return $item;
            }
        }

        throw new \LogicException('Item not found: ' . $code);
    }
}
