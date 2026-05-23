<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Core\RendererInterface;
use OliTheme\Seo\Admin\RedirectsPage;
use OliTheme\Seo\RedirectEntity;
use OliTheme\Seo\RedirectModelInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de RedirectsPage (CRUD admin des redirections).
 *
 * @package OliTheme\Tests\Unit\Seo\Admin
 *
 * @since 1.0.0
 */
final class RedirectsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $_POST    = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        $_POST    = [];
        $_REQUEST = [];
        parent::tearDown();
    }

    public function testImplementsAdminTabInterface(): void
    {
        $redirects = $this->createMock(RedirectModelInterface::class);
        $renderer  = $this->createMock(RendererInterface::class);

        $page = new RedirectsPage($redirects, $renderer);

        self::assertSame('redirections', $page->id());
        self::assertSame('seo', $page->group());
        self::assertSame('manage_options', $page->capability());
    }

    public function testLabelIsTranslated(): void
    {
        Functions\when('__')->returnArg(1);

        $redirects = $this->createMock(RedirectModelInterface::class);
        $renderer  = $this->createMock(RendererInterface::class);

        self::assertSame('Redirections', (new RedirectsPage($redirects, $renderer))->label());
    }

    /**
     * Le formulaire create doit appeler save() avec les valeurs assainies.
     */
    public function testHandleSaveCreatesNewRedirectWhenIdEmpty(): void
    {
        $this->stubCommonFunctions();

        $_POST = [
            '_wpnonce' => 'valid-nonce',
            'id'       => '',
            'source'   => '/old-page',
            'target'   => 'https://example.com/new',
            'code'     => '301',
        ];

        $redirects = $this->createMock(RedirectModelInterface::class);
        $redirects->expects(self::once())
            ->method('save')
            ->with('/old-page', 'https://example.com/new', 301)
            ->willReturn($this->makeEntity());
        $redirects->expects(self::never())->method('update');

        $renderer = $this->createMock(RendererInterface::class);

        (new RedirectsPage($redirects, $renderer))->handleSave();
    }

    /**
     * Quand un id est fourni, handleSave doit appeler update().
     */
    public function testHandleSaveUpdatesExistingRedirectWhenIdProvided(): void
    {
        $this->stubCommonFunctions();

        $_POST = [
            '_wpnonce' => 'valid-nonce',
            'id'       => '5',
            'source'   => '/edited',
            'target'   => 'https://example.com/edited',
            'code'     => '302',
        ];

        $redirects = $this->createMock(RedirectModelInterface::class);
        $redirects->expects(self::once())
            ->method('update')
            ->with(5, '/edited', 'https://example.com/edited', 302)
            ->willReturn($this->makeEntity(id: 5));
        $redirects->expects(self::never())->method('save');

        $renderer = $this->createMock(RendererInterface::class);

        (new RedirectsPage($redirects, $renderer))->handleSave();
    }

    /**
     * Source vide → pas d'enregistrement, redirect avec notice d'erreur.
     */
    public function testHandleSaveRejectsEmptySource(): void
    {
        $this->stubCommonFunctions();

        $_POST = [
            '_wpnonce' => 'valid-nonce',
            'id'       => '',
            'source'   => '',
            'target'   => 'https://example.com/new',
            'code'     => '301',
        ];

        $redirects = $this->createMock(RedirectModelInterface::class);
        $redirects->expects(self::never())->method('save');
        $redirects->expects(self::never())->method('update');

        $renderer = $this->createMock(RendererInterface::class);

        (new RedirectsPage($redirects, $renderer))->handleSave();
    }

    /**
     * Code HTTP non autorisé → rejet (seuls 301/302/410 sont valides).
     */
    public function testHandleSaveRejectsInvalidHttpCode(): void
    {
        $this->stubCommonFunctions();

        $_POST = [
            '_wpnonce' => 'valid-nonce',
            'id'       => '',
            'source'   => '/old',
            'target'   => 'https://example.com/new',
            'code'     => '999',
        ];

        $redirects = $this->createMock(RedirectModelInterface::class);
        $redirects->expects(self::never())->method('save');

        $renderer = $this->createMock(RendererInterface::class);

        (new RedirectsPage($redirects, $renderer))->handleSave();
    }

    /**
     * 410 (Gone) tolère un target vide — la ressource est supprimée.
     */
    public function testHandleSaveAllowsEmptyTargetForGoneCode(): void
    {
        $this->stubCommonFunctions();

        $_POST = [
            '_wpnonce' => 'valid-nonce',
            'id'       => '',
            'source'   => '/disparue',
            'target'   => '',
            'code'     => '410',
        ];

        $redirects = $this->createMock(RedirectModelInterface::class);
        $redirects->expects(self::once())
            ->method('save')
            ->with('/disparue', '', 410)
            ->willReturn($this->makeEntity(source: '/disparue', target: '', code: 410));

        $renderer = $this->createMock(RendererInterface::class);

        (new RedirectsPage($redirects, $renderer))->handleSave();
    }

    /**
     * Target vide pour 301/302 → rejet.
     */
    public function testHandleSaveRejectsEmptyTargetForRedirectCodes(): void
    {
        $this->stubCommonFunctions();

        $_POST = [
            '_wpnonce' => 'valid-nonce',
            'id'       => '',
            'source'   => '/old',
            'target'   => '',
            'code'     => '301',
        ];

        $redirects = $this->createMock(RedirectModelInterface::class);
        $redirects->expects(self::never())->method('save');

        $renderer = $this->createMock(RendererInterface::class);

        (new RedirectsPage($redirects, $renderer))->handleSave();
    }

    /**
     * handleDelete supprime la redirection et redirige.
     */
    public function testHandleDeleteRemovesRedirect(): void
    {
        $this->stubCommonFunctions();

        $_REQUEST = [
            '_wpnonce' => 'valid-nonce',
            'id'       => '12',
        ];

        $redirects = $this->createMock(RedirectModelInterface::class);
        $redirects->expects(self::once())->method('delete')->with(12);

        $renderer = $this->createMock(RendererInterface::class);

        (new RedirectsPage($redirects, $renderer))->handleDelete();
    }

    /**
     * handleDelete avec id=0 ne supprime rien.
     */
    public function testHandleDeleteIgnoresZeroId(): void
    {
        $this->stubCommonFunctions();

        $_REQUEST = [
            '_wpnonce' => 'valid-nonce',
            'id'       => '0',
        ];

        $redirects = $this->createMock(RedirectModelInterface::class);
        $redirects->expects(self::never())->method('delete');

        $renderer = $this->createMock(RendererInterface::class);

        (new RedirectsPage($redirects, $renderer))->handleDelete();
    }

    /**
     * Crée une RedirectEntity factice pour les tests (RedirectEntity étant final readonly).
     */
    private function makeEntity(int $id = 1, string $source = '/old', string $target = 'https://example.com/new', int $code = 301): RedirectEntity
    {
        return new RedirectEntity(
            id: $id,
            source: $source,
            target: $target,
            code: $code,
            hits: 0,
            createdAt: new DateTimeImmutable('2026-01-01 00:00:00'),
        );
    }

    /**
     * Stubs des fonctions WP communes : auth + sanitize + redirect.
     */
    private function stubCommonFunctions(): void
    {
        Functions\when('__')->returnArg(1);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_admin_referer')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('esc_url_raw')->returnArg(1);
        Functions\when('wp_unslash')->returnArg(1);
        Functions\when('admin_url')->alias(
            static fn (string $path = ''): string => 'http://example.test/wp-admin/' . ltrim($path, '/'),
        );
        Functions\when('add_query_arg')->alias(
            static fn (array|string $key, string $value = '', string $url = ''): string => \is_array($key) ? $url . '?' . http_build_query($key) : $url . '?' . $key . '=' . $value,
        );
        Functions\when('wp_safe_redirect')->justReturn(true);
        Functions\when('wp_die')->justReturn(null);
    }
}
