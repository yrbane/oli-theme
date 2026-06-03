<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MetaSync;

use OliTheme\MetaSync\Http\GraphApiClient;
use OliTheme\MetaSync\Http\GraphApiError;
use PHPUnit\Framework\TestCase;

final class GraphApiClientTest extends TestCase
{
    /** @var list<array{url:string, opts:array<string,mixed>}> */
    private array $spy = [];

    /**
     * @param list<array<string, mixed>> $responses
     */
    private function clientWithResponses(array $responses): GraphApiClient
    {
        $this->spy = [];
        $index = 0;
        return new GraphApiClient(GraphApiClient::DEFAULT_BASE, function (string $url, array $opts) use (&$index, $responses) {
            $this->spy[] = ['url' => $url, 'opts' => $opts];
            $r = $responses[$index] ?? end($responses);
            $index++;
            return $r;
        });
    }

    public function test_get_decodes_successful_json(): void
    {
        $client = $this->clientWithResponses([[
            'body'     => json_encode(['data' => ['ok' => true]]),
            'response' => ['code' => 200],
        ]]);
        $result = $client->get('/me', ['fields' => 'id'], 'token123');
        self::assertIsArray($result);
        self::assertSame(['ok' => true], $result['data']);
        self::assertStringContainsString('access_token=token123', $this->spy[0]['url']);
    }

    public function test_returns_error_on_graph_error_payload(): void
    {
        $client = $this->clientWithResponses([[
            'body'     => json_encode(['error' => ['code' => 190, 'type' => 'OAuthException', 'message' => 'Invalid token']]),
            'response' => ['code' => 400],
        ]]);
        $result = $client->get('/me');
        self::assertInstanceOf(GraphApiError::class, $result);
        self::assertSame(190, $result->graphCode);
        self::assertTrue($result->isAuthError());
    }

    public function test_retries_on_500_then_succeeds(): void
    {
        $client = $this->clientWithResponses([
            ['body' => '', 'response' => ['code' => 500]],
            ['body' => json_encode(['ok' => true]), 'response' => ['code' => 200]],
        ]);
        $result = $client->get('/me');
        self::assertIsArray($result);
        self::assertTrue($result['ok']);
    }

    public function test_returns_server_error_after_max_attempts(): void
    {
        $client = $this->clientWithResponses([
            ['body' => '', 'response' => ['code' => 500]],
            ['body' => '', 'response' => ['code' => 503]],
        ]);
        $result = $client->get('/me');
        self::assertInstanceOf(GraphApiError::class, $result);
        self::assertSame('server_error', $result->type);
    }

    public function test_post_sends_body_and_token(): void
    {
        $client = $this->clientWithResponses([[
            'body' => json_encode(['id' => '123']),
            'response' => ['code' => 200],
        ]]);
        $result = $client->post('/pageid/feed', ['message' => 'hi'], 'tok');
        self::assertIsArray($result);
        self::assertSame('POST', $this->spy[0]['opts']['method']);
        self::assertSame('hi', $this->spy[0]['opts']['body']['message']);
        self::assertSame('tok', $this->spy[0]['opts']['body']['access_token']);
    }

    public function test_delete_emits_delete_method(): void
    {
        $client = $this->clientWithResponses([[
            'body' => json_encode(['success' => true]),
            'response' => ['code' => 200],
        ]]);
        $result = $client->delete('/postid', 'tok');
        self::assertIsArray($result);
        self::assertSame('DELETE', $this->spy[0]['opts']['method']);
        self::assertStringContainsString('access_token=tok', $this->spy[0]['url']);
    }

    public function test_returns_error_on_invalid_json(): void
    {
        $client = $this->clientWithResponses([[
            'body' => '<html>Not JSON</html>',
            'response' => ['code' => 200],
        ]]);
        $result = $client->get('/me');
        self::assertInstanceOf(GraphApiError::class, $result);
        self::assertSame('invalid_response', $result->type);
    }

    public function test_graph_error_helpers(): void
    {
        $auth  = new GraphApiError(401, 'OAuthException', 'Invalid token', 190);
        $perm  = new GraphApiError(403, 'OAuthException', 'No permission', 200);
        $rate  = new GraphApiError(429, 'OAuthException', 'Too many', 17);
        $other = new GraphApiError(400, 'OAuthException', 'Bad request', 999);

        self::assertTrue($auth->isAuthError());
        self::assertTrue($perm->isPermissionError());
        self::assertTrue($rate->isRateLimited());
        self::assertFalse($other->isAuthError());
        self::assertFalse($other->isPermissionError());
        self::assertFalse($other->isRateLimited());
    }
}
