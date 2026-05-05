<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\RequestContext;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\LanguageResolver;
use PHPUnit\Framework\TestCase;

final class LanguageResolverTest extends TestCase
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

    public function test_it_should_resolve_from_url_query_var(): void
    {
        $request = new RequestContext(query: ['oli_lang' => 'en']);
        $resolver = new LanguageResolver(new LanguageRegistry(), $request);

        self::assertSame('en', $resolver->resolve()->code);
    }

    public function test_it_should_fall_back_to_cookie_when_no_url_prefix(): void
    {
        $request = new RequestContext(cookies: ['oli_lang' => 'it']);
        $resolver = new LanguageResolver(new LanguageRegistry(), $request);

        self::assertSame('it', $resolver->resolve()->code);
    }

    public function test_it_should_fall_back_to_accept_language_header(): void
    {
        $request = new RequestContext(server: ['HTTP_ACCEPT_LANGUAGE' => 'es-ES,es;q=0.9,en;q=0.8']);
        $resolver = new LanguageResolver(new LanguageRegistry(), $request);

        self::assertSame('es', $resolver->resolve()->code);
    }

    public function test_it_should_return_default_when_no_signal(): void
    {
        $request = new RequestContext();
        $resolver = new LanguageResolver(new LanguageRegistry(), $request);

        self::assertSame('fr', $resolver->resolve()->code);
    }

    public function test_it_should_ignore_unknown_codes(): void
    {
        $request = new RequestContext(query: ['oli_lang' => 'zz']);
        $resolver = new LanguageResolver(new LanguageRegistry(), $request);

        self::assertSame('fr', $resolver->resolve()->code);
    }

    public function test_it_should_memoize_result(): void
    {
        $request = new RequestContext(query: ['oli_lang' => 'en']);
        $resolver = new LanguageResolver(new LanguageRegistry(), $request);

        self::assertSame($resolver->current(), $resolver->current());
    }
}
