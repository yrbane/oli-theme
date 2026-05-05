<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use OliTheme\Core\RequestContext;
use PHPUnit\Framework\TestCase;

/**
 * Tests du wrapper de la requête HTTP courante.
 */
final class RequestContextTest extends TestCase
{
    public function test_it_should_return_query_var_when_set(): void
    {
        $ctx = new RequestContext(query: ['oli_lang' => 'fr']);
        self::assertSame('fr', $ctx->queryVar('oli_lang'));
    }

    public function test_it_should_return_null_when_query_var_missing(): void
    {
        $ctx = new RequestContext();
        self::assertNull($ctx->queryVar('oli_lang'));
    }

    public function test_it_should_return_cookie_when_set(): void
    {
        $ctx = new RequestContext(cookies: ['oli_lang' => 'en']);
        self::assertSame('en', $ctx->cookie('oli_lang'));
    }

    public function test_it_should_return_request_method_uppercased(): void
    {
        $ctx = new RequestContext(server: ['REQUEST_METHOD' => 'post']);
        self::assertSame('POST', $ctx->method());
    }

    public function test_it_should_default_request_method_to_get(): void
    {
        $ctx = new RequestContext();
        self::assertSame('GET', $ctx->method());
    }

    public function test_it_should_return_remote_ip_from_remote_addr(): void
    {
        $ctx = new RequestContext(server: ['REMOTE_ADDR' => '203.0.113.5']);
        self::assertSame('203.0.113.5', $ctx->ip());
    }

    public function test_it_should_return_header_from_server_http_prefix(): void
    {
        $ctx = new RequestContext(server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9']);
        self::assertSame('fr-FR,fr;q=0.9', $ctx->header('Accept-Language'));
    }

    public function test_it_should_return_null_when_header_missing(): void
    {
        $ctx = new RequestContext();
        self::assertNull($ctx->header('Accept-Language'));
    }
}
