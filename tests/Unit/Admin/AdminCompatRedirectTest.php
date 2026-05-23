<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Admin\LegacySlugRedirector;
use PHPUnit\Framework\TestCase;

final class AdminCompatRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('sanitize_key')->returnArg(1);
        Functions\when('admin_url')->alias(static fn (string $p = ''): string => 'http://x/wp-admin/' . $p);
        Functions\when('add_query_arg')->alias(static fn (array $a, string $u): string => $u . '?' . http_build_query($a));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testMapsSocialSlugToTab(): void
    {
        $target = (new LegacySlugRedirector())->targetFor('oli-social-links', []);
        self::assertNotNull($target);
        self::assertStringContainsString('page=oli-theme-settings', $target);
        self::assertStringContainsString('tab=identite', $target);
        self::assertStringContainsString('sub=social', $target);
    }

    public function testMapsRedirectsSlugAndKeepsExtraParams(): void
    {
        $target = (new LegacySlugRedirector())->targetFor('oli-seo-redirects', ['paged' => '2']);
        self::assertNotNull($target);
        self::assertStringContainsString('tab=seo', $target);
        self::assertStringContainsString('sub=redirections', $target);
        self::assertStringContainsString('paged=2', $target);
    }

    public function testReturnsNullForUnknownSlug(): void
    {
        self::assertNull((new LegacySlugRedirector())->targetFor('some-other-page', []));
    }
}
