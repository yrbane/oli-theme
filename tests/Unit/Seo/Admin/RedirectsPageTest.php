<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\RendererInterface;
use OliTheme\Seo\Admin\RedirectsPage;
use OliTheme\Seo\RedirectModelInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de RedirectsPage (page admin des redirections MVP).
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
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRegisterAddsAdminPage(): void
    {
        Functions\when('__')->returnArg(1);

        $capturedSlug = null;

        Functions\when('add_management_page')->alias(
            static function (string $pageTitle, string $menuTitle, string $capability, string $menuSlug) use (&$capturedSlug): void {
                $capturedSlug = $menuSlug;
            },
        );

        $redirects = $this->createMock(RedirectModelInterface::class);
        $renderer  = $this->createMock(RendererInterface::class);

        (new RedirectsPage($redirects, $renderer))->register();

        self::assertSame('oli-seo-redirects', $capturedSlug);
    }
}
