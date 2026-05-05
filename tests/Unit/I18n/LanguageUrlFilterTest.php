<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\RequestContext;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageUrlFilter;
use PHPUnit\Framework\TestCase;

final class LanguageUrlFilterTest extends TestCase
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

    public function test_it_should_prefix_home_url_with_current_language(): void
    {
        $filter = $this->buildFilter('en');
        $url = $filter->filterHomeUrl('https://example.test/', '/');

        self::assertSame('https://example.test/en/', $url);
    }

    public function test_it_should_not_prefix_when_default_language(): void
    {
        $filter = $this->buildFilter('fr');
        $url = $filter->filterHomeUrl('https://example.test/page', '/page');

        self::assertSame('https://example.test/page', $url);
    }

    public function test_it_should_not_double_prefix_existing_language_segment(): void
    {
        $filter = $this->buildFilter('en');
        $url = $filter->filterHomeUrl('https://example.test/en/page', '/en/page');

        self::assertSame('https://example.test/en/page', $url);
    }

    private function buildFilter(string $code): LanguageUrlFilter
    {
        $request = new RequestContext(query: ['oli_lang' => $code]);
        $registry = new LanguageRegistry();
        $resolver = new LanguageResolver($registry, $request);

        return new LanguageUrlFilter($registry, $resolver);
    }
}
