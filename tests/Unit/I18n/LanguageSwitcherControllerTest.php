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
        Functions\when('get_option')->alias(static fn (string $k, $d = false) => $k === 'oli_languages' ? ['enabled' => ['fr', 'en', 'it', 'es'], 'default' => 'fr'] : $d);
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

    /**
     * Invariant : le switcher ne doit afficher QUE les langues cochées dans
     * Réglages → Identité → Langues (option `oli_languages.enabled`).
     */
    public function test_view_model_only_shows_enabled_languages(): void
    {
        // Seules FR et EN cochées : IT et ES (du catalogue) ne doivent PAS apparaître.
        Functions\when('get_option')->alias(static fn (string $k, $d = false) => $k === 'oli_languages'
            ? ['enabled' => ['fr', 'en'], 'default' => 'fr']
            : $d);

        $controller = $this->buildController('fr');
        $vm = $controller->build(0);

        self::assertCount(2, $vm->items);
        $codes = array_map(static fn ($i) => $i->code, $vm->items);
        self::assertSame(['fr', 'en'], $codes);
    }

    /**
     * Fallback : si l'option `oli_languages` n'a jamais été enregistrée
     * (premier déploiement), le site est monolingue FR — un seul item, donc
     * le sélecteur est masqué côté template.
     */
    public function test_view_model_is_empty_when_option_absent_falls_back_to_french_only(): void
    {
        Functions\when('get_option')->justReturn(false);

        $controller = $this->buildController('fr');
        $vm = $controller->build(0);

        // Une seule langue effective → switcher vide (template masque).
        self::assertSame([], $vm->items);
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

    /**
     * Sur /en/ avec un post sans traduction en IT/ES, les drapeaux IT/ES ne
     * doivent PAS hériter du préfixe /en/. Bug observé : home_url('/it/') est
     * filtré par LanguageUrlFilter qui préfixe avec la langue active → /en/it/.
     */
    public function test_target_url_for_missing_translation_strips_active_lang_prefix(): void
    {
        Functions\when('get_post_meta')->justReturn('group-A');
        Functions\when('get_posts')->justReturn([20]);
        Functions\when('wp_get_post_terms')->alias(static function () {
            $term = new \stdClass();
            $term->slug = 'en';

            return [$term];
        });
        Functions\when('get_permalink')->alias(static fn (int $id) => "https://example.test/en/post-{$id}");
        // Simule LanguageUrlFilter en production : home_url() préfixe la langue active.
        Functions\when('home_url')->alias(static fn (string $path = '') => 'https://example.test/en' . $path);

        $controller = $this->buildController('en');
        $vm = $controller->build(20);
        $it = $this->itemByCode($vm, 'it');
        $fr = $this->itemByCode($vm, 'fr');

        // IT : pas de traduction → home racine IT, sans préfixe /en/ parasite.
        self::assertSame('https://example.test/it/', $it->url);
        // FR (défaut) : pas de traduction → home racine, sans préfixe /en/ ni /fr/.
        self::assertSame('https://example.test/', $fr->url);
    }

    /**
     * Quand la traduction cible est la page d'accueil (page_on_front) de sa langue,
     * l'URL doit pointer vers la home racine, pas vers le permalien /accueil/.
     */
    public function test_target_url_uses_home_root_when_translation_is_front_page(): void
    {
        Functions\when('get_post_meta')->justReturn('group-A');
        Functions\when('get_posts')->justReturn([76, 77]);
        Functions\when('wp_get_post_terms')->alias(static function (int $postId) {
            $term = new \stdClass();
            $term->slug = $postId === 76 ? 'fr' : 'en';

            return [$term];
        });
        Functions\when('get_option')->alias(static function (string $key, $default = false) {
            return match ($key) {
                'show_on_front' => 'page',
                'page_on_front' => 76,
                'oli_languages' => ['enabled' => ['fr', 'en', 'it', 'es'], 'default' => 'fr'],
                default => $default,
            };
        });
        Functions\when('get_permalink')->alias(static fn (int $id) => "https://example.test/en/accueil");
        Functions\when('home_url')->alias(static fn (string $path = '') => 'https://example.test/en' . $path);

        $controller = $this->buildController('en');
        $vm = $controller->build(77);
        $fr = $this->itemByCode($vm, 'fr');

        // FR pointe vers la page d'accueil FR (page 76 = page_on_front) → racine.
        self::assertSame('https://example.test/', $fr->url);
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
