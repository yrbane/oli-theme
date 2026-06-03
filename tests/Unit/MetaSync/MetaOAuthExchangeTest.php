<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MetaSync;

use OliTheme\MetaSync\Auth\MetaOAuthExchange;
use OliTheme\MetaSync\Http\GraphApiClient;
use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\MetaSyncCredentials;
use PHPUnit\Framework\TestCase;

final class MetaOAuthExchangeTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $responses
     */
    private function exchange(array $responses): MetaOAuthExchange
    {
        $index = 0;
        $client = new GraphApiClient(GraphApiClient::DEFAULT_BASE, function (string $url, array $opts) use (&$index, $responses) {
            $r = $responses[$index] ?? ['body' => '{}', 'response' => ['code' => 200]];
            $index++;
            return $r;
        });
        return new MetaOAuthExchange($client);
    }

    public function test_exchange_returns_credentials_on_happy_path(): void
    {
        $ex = $this->exchange([
            ['body' => json_encode(['access_token' => 'SHORT', 'expires_in' => 3600]),         'response' => ['code' => 200]],
            ['body' => json_encode(['access_token' => 'LONG_USER', 'expires_in' => 5184000]),   'response' => ['code' => 200]],
            ['body' => json_encode(['data' => [['id' => 'PAGE1', 'name' => 'Olikalari', 'access_token' => 'PAGE_TOK']]]), 'response' => ['code' => 200]],
            ['body' => json_encode(['instagram_business_account' => ['id' => 'IG_USER_123']]), 'response' => ['code' => 200]],
        ]);

        $result = $ex->exchange('APPID', 'APPSECRET', 'CODE_FROM_FB', 'https://oli.test/cb');
        self::assertInstanceOf(MetaSyncCredentials::class, $result);
        self::assertSame('APPID', $result->appId);
        self::assertSame('PAGE1', $result->pageId);
        self::assertSame('IG_USER_123', $result->igUserId);
        self::assertSame('PAGE_TOK', $result->accessToken);
        self::assertGreaterThan(time() + 30 * 86400, $result->expiresAt);
    }

    public function test_exchange_rejects_missing_input(): void
    {
        $ex = $this->exchange([['body' => '{}', 'response' => ['code' => 200]]]);
        $result = $ex->exchange('', '', '', 'cb');
        self::assertInstanceOf(GraphApiError::class, $result);
        self::assertSame('invalid_input', $result->type);
    }

    public function test_exchange_propagates_short_token_error(): void
    {
        $ex = $this->exchange([
            ['body' => json_encode(['error' => ['code' => 100, 'type' => 'OAuthException', 'message' => 'bad code']]), 'response' => ['code' => 400]],
        ]);
        $result = $ex->exchange('A', 'B', 'C', 'cb');
        self::assertInstanceOf(GraphApiError::class, $result);
        self::assertSame(100, $result->graphCode);
    }

    public function test_exchange_handles_no_pages(): void
    {
        $ex = $this->exchange([
            ['body' => json_encode(['access_token' => 'S']), 'response' => ['code' => 200]],
            ['body' => json_encode(['access_token' => 'L']), 'response' => ['code' => 200]],
            ['body' => json_encode(['data' => []]),          'response' => ['code' => 200]],
        ]);
        $result = $ex->exchange('A', 'B', 'C', 'cb');
        self::assertInstanceOf(GraphApiError::class, $result);
        self::assertSame('no_pages', $result->type);
    }

    public function test_exchange_works_when_no_instagram_linked(): void
    {
        $ex = $this->exchange([
            ['body' => json_encode(['access_token' => 'S']),                                                            'response' => ['code' => 200]],
            ['body' => json_encode(['access_token' => 'L']),                                                            'response' => ['code' => 200]],
            ['body' => json_encode(['data' => [['id' => 'PAGE1', 'name' => 'Olikalari', 'access_token' => 'PT']]]),     'response' => ['code' => 200]],
            ['body' => json_encode(['error' => ['code' => 100, 'message' => 'no IG']]),                                 'response' => ['code' => 400]],
        ]);
        $result = $ex->exchange('A', 'B', 'C', 'cb');
        self::assertInstanceOf(MetaSyncCredentials::class, $result);
        self::assertSame('', $result->igUserId);
        self::assertSame('PAGE1', $result->pageId);
    }

    public function test_refresh_updates_token_and_expiry(): void
    {
        $ex = $this->exchange([
            ['body' => json_encode(['access_token' => 'REFRESHED', 'expires_in' => 5184000]), 'response' => ['code' => 200]],
        ]);
        $current = new MetaSyncCredentials(
            appId: 'A', appSecret: 'B', pageId: 'P', accessToken: 'OLD', expiresAt: 1,
        );
        $result = $ex->refresh($current);
        self::assertInstanceOf(MetaSyncCredentials::class, $result);
        self::assertSame('REFRESHED', $result->accessToken);
        self::assertGreaterThan(time() + 30 * 86400, $result->expiresAt);
    }

    public function test_refresh_rejects_when_not_connected(): void
    {
        $ex = $this->exchange([['body' => '{}', 'response' => ['code' => 200]]]);
        $result = $ex->refresh(new MetaSyncCredentials());
        self::assertInstanceOf(GraphApiError::class, $result);
        self::assertSame('not_connected', $result->type);
    }
}
