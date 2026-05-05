<?php

declare(strict_types=1);

namespace OliTheme\Tests\Integration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\RequestContext;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageUrlFilter;
use PHPUnit\Framework\TestCase;

final class I18nFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('get_option')->justReturn(false);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_url_filter_uses_language_resolved_from_query_var(): void
    {
        $request = new RequestContext(query: ['oli_lang' => 'en']);
        $registry = new LanguageRegistry();
        $resolver = new LanguageResolver($registry, $request);
        $filter = new LanguageUrlFilter($registry, $resolver);

        $output = $filter->filterHomeUrl('https://example.test/', '/');

        self::assertSame('https://example.test/en/', $output);
    }

    public function test_default_language_does_not_prefix_urls(): void
    {
        $request = new RequestContext();
        $registry = new LanguageRegistry();
        $resolver = new LanguageResolver($registry, $request);
        $filter = new LanguageUrlFilter($registry, $resolver);

        $output = $filter->filterHomeUrl('https://example.test/contact', '/contact');

        self::assertSame('https://example.test/contact', $output);
    }
}
