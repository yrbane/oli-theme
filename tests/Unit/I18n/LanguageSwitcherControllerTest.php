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
        // Brain Monkey + Patchwork laissent `function_exists` à true entre tests :
        // si une suite précédente a stubé get_template_directory, function_exists
        // renvoie true ici et flagUrlFor() tente l'appel sans stub actif.
        // On stub avec une chaîne vide → is_file('') renvoie false → null retourné.
        Functions\when('get_template_directory')->justReturn('');
        Functions\when('get_template_directory_uri')->justReturn('');
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

    public function test_view_model_is_empty_when_single_language_enabled(): void
    {
        Functions\when('get_option')->justReturn(['enabled' => ['fr'], 'default' => 'fr']);

        $controller = $this->buildController('fr');
        $vm = $controller->build(0);

        // Une seule langue activée → aucun item (sélecteur masqué côté template).
        self::assertSame([], $vm->items);
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

        // Depuis fr (default), le lien vers EN doit être préfixé /en/ pour
        // que la langue cible soit bien activée par les rewrite rules.
        self::assertSame('https://example.test/en/post-20', $en->url);
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

    /**
     * Scénario réel : on est sur /en/, on clique vers FR. get_permalink()
     * retourne une URL préfixée /en/ (filtre permalink). Le switcher doit
     * relocaliser vers /post-fr (sans préfixe car FR = langue par défaut).
     */
    public function test_target_url_strips_active_lang_prefix_when_target_is_default(): void
    {
        Functions\when('get_post_meta')->justReturn('group-A');
        Functions\when('get_posts')->justReturn([10, 20]);
        Functions\when('wp_get_post_terms')->alias(static function (int $postId) {
            $term = new \stdClass();
            $term->slug = $postId === 10 ? 'fr' : 'en';

            return [$term];
        });
        // Simule le permalien filtré par LanguageUrlFilter qui ajoute /en/.
        Functions\when('get_permalink')->alias(static fn (int $id) => "https://example.test/en/post-{$id}");

        $controller = $this->buildController('en');
        $vm = $controller->build(20);
        $fr = $this->itemByCode($vm, 'fr');

        self::assertSame('https://example.test/post-10', $fr->url);
    }

    /**
     * Inverse : on est sur /, on clique vers EN. get_permalink retourne une
     * URL non préfixée. Le switcher doit ajouter /en/.
     */
    public function test_target_url_adds_target_prefix_when_active_is_default(): void
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

        self::assertSame('https://example.test/en/post-20', $en->url);
    }

    /**
     * Cas pathologique : sur /en/ avec une URL déjà préfixée /en/, le lien
     * vers EN lui-même ne doit pas dupliquer le préfixe.
     */
    public function test_target_url_does_not_duplicate_prefix_when_target_matches_active(): void
    {
        Functions\when('get_post_meta')->justReturn('group-A');
        Functions\when('get_posts')->justReturn([10, 20]);
        Functions\when('wp_get_post_terms')->alias(static function (int $postId) {
            $term = new \stdClass();
            $term->slug = $postId === 10 ? 'fr' : 'en';

            return [$term];
        });
        Functions\when('get_permalink')->alias(static fn (int $id) => "https://example.test/en/post-{$id}");

        $controller = $this->buildController('en');
        $vm = $controller->build(20);
        $en = $this->itemByCode($vm, 'en');

        self::assertSame('https://example.test/en/post-20', $en->url);
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
