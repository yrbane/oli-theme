<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\RendererInterface;
use OliTheme\Seo\Admin\SeoOverviewPage;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de SeoOverviewPage (dashboard SEO admin MVP).
 *
 * @package OliTheme\Tests\Unit\Seo\Admin
 *
 * @since 1.0.0
 */
final class SeoOverviewPageTest extends TestCase
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

        $capturedSlug       = null;
        $capturedCapability = null;

        Functions\when('add_management_page')->alias(
            static function (string $pageTitle, string $menuTitle, string $capability, string $menuSlug) use (&$capturedSlug, &$capturedCapability): void {
                $capturedSlug       = $menuSlug;
                $capturedCapability = $capability;
            },
        );

        $renderer = $this->createMock(RendererInterface::class);
        (new SeoOverviewPage($renderer))->register();

        self::assertSame('oli-seo-dashboard', $capturedSlug);
        self::assertSame('manage_options', $capturedCapability);
    }
}
