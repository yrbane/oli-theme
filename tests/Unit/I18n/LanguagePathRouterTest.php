<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\LanguagePathRouter;
use OliTheme\I18n\LanguageRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests du routeur universel qui retire le préfixe `/<lang>/` de REQUEST_URI
 * avant que WP_Rewrite ne résolve l'URL, et qui réinjecte `oli_lang` dans
 * les query_vars après parse_request.
 *
 * @package OliTheme\Tests\Unit\I18n
 *
 * @since 1.0.0
 */
final class LanguagePathRouterTest extends TestCase
{
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('get_option')->alias(static fn (string $k, $d = false) => $k === 'oli_languages'
            ? ['enabled' => ['fr', 'en', 'it', 'es'], 'default' => 'fr']
            : ($k === 'home' ? 'https://example.test' : $d));
        Functions\when('__')->returnArg(1);
        $this->serverBackup = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * /en/2026/06/03/slug doit devenir /2026/06/03/slug afin que WP applique
     * la rule date-permalink standard et résolve le post.
     */
    public function test_strips_language_prefix_from_request_uri_for_non_default_language(): void
    {
        $_SERVER['REQUEST_URI'] = '/en/2026/06/03/my-post/';

        $router = new LanguagePathRouter(new LanguageRegistry());
        $shouldContinue = $router->interceptParseRequest(true);

        self::assertTrue($shouldContinue);
        self::assertSame('/2026/06/03/my-post/', $_SERVER['REQUEST_URI']);
        self::assertSame('en', $router->detectedLanguage());
    }

    /**
     * /en/ (racine de langue) ne doit PAS être strippée : c'est LanguageHomeRouter
     * qui gère ce cas. Le path strippé serait vide et casserait la résolution.
     */
    public function test_does_not_strip_when_path_equals_language_root(): void
    {
        $_SERVER['REQUEST_URI'] = '/en/';

        $router = new LanguagePathRouter(new LanguageRegistry());
        $router->interceptParseRequest(true);

        self::assertSame('/en/', $_SERVER['REQUEST_URI']);
        self::assertNull($router->detectedLanguage());
    }

    public function test_does_not_strip_default_language_prefix(): void
    {
        $_SERVER['REQUEST_URI'] = '/fr/2026/06/03/my-post/';

        $router = new LanguagePathRouter(new LanguageRegistry());
        $router->interceptParseRequest(true);

        // /fr/... n'est pas une URL canonique (fr = défaut) ; on ne strippe pas.
        self::assertSame('/fr/2026/06/03/my-post/', $_SERVER['REQUEST_URI']);
        self::assertNull($router->detectedLanguage());
    }

    public function test_does_not_strip_when_no_language_prefix(): void
    {
        $_SERVER['REQUEST_URI'] = '/2026/06/03/my-post/';

        $router = new LanguagePathRouter(new LanguageRegistry());
        $router->interceptParseRequest(true);

        self::assertSame('/2026/06/03/my-post/', $_SERVER['REQUEST_URI']);
        self::assertNull($router->detectedLanguage());
    }

    public function test_preserves_query_string_when_stripping(): void
    {
        $_SERVER['REQUEST_URI'] = '/en/blog/?paged=2&utm=foo';

        $router = new LanguagePathRouter(new LanguageRegistry());
        $router->interceptParseRequest(true);

        self::assertSame('/blog/?paged=2&utm=foo', $_SERVER['REQUEST_URI']);
        self::assertSame('en', $router->detectedLanguage());
    }

    /**
     * Après strip, le filter `request` doit ajouter `oli_lang` aux query_vars
     * résolus par WP, pour que LanguageResolver puisse rétablir la langue.
     */
    public function test_injects_detected_language_into_request_query_vars(): void
    {
        $_SERVER['REQUEST_URI'] = '/en/2026/06/03/my-post/';

        $router = new LanguagePathRouter(new LanguageRegistry());
        $router->interceptParseRequest(true);

        $vars = $router->injectLanguageQueryVar(['name' => 'my-post', 'year' => '2026']);

        self::assertSame('en', $vars['oli_lang']);
        self::assertSame('my-post', $vars['name']);
    }

    public function test_does_not_inject_when_no_language_detected(): void
    {
        $_SERVER['REQUEST_URI'] = '/2026/06/03/my-post/';

        $router = new LanguagePathRouter(new LanguageRegistry());
        $router->interceptParseRequest(true);

        $vars = $router->injectLanguageQueryVar(['name' => 'my-post']);

        self::assertArrayNotHasKey('oli_lang', $vars);
    }

    /**
     * Si WP est installé dans un sous-répertoire (home_url() = /wp/),
     * le préfixe de langue suit le sous-rép : /wp/en/path → /wp/path.
     */
    public function test_restores_original_request_uri_after_parse_request(): void
    {
        $original = '/en/2026/06/03/my-post/?paged=2';
        $_SERVER['REQUEST_URI'] = $original;

        $router = new LanguagePathRouter(new LanguageRegistry());
        $router->interceptParseRequest(true);
        self::assertSame('/2026/06/03/my-post/?paged=2', $_SERVER['REQUEST_URI']);

        $router->restoreRequestUri();
        self::assertSame($original, $_SERVER['REQUEST_URI']);
    }

    public function test_restore_is_no_op_when_no_strip_happened(): void
    {
        $_SERVER['REQUEST_URI'] = '/about/';

        $router = new LanguagePathRouter(new LanguageRegistry());
        $router->interceptParseRequest(true);
        $router->restoreRequestUri();

        self::assertSame('/about/', $_SERVER['REQUEST_URI']);
    }

    public function test_handles_wordpress_subdirectory_installation(): void
    {
        Functions\when('get_option')->alias(static fn (string $k, $d = false) => match ($k) {
            'oli_languages' => ['enabled' => ['fr', 'en'], 'default' => 'fr'],
            'home'          => 'https://example.test/wp',
            default         => $d,
        });
        $_SERVER['REQUEST_URI'] = '/wp/en/2026/06/03/my-post/';

        $router = new LanguagePathRouter(new LanguageRegistry());
        $router->interceptParseRequest(true);

        self::assertSame('/wp/2026/06/03/my-post/', $_SERVER['REQUEST_URI']);
        self::assertSame('en', $router->detectedLanguage());
    }
}
