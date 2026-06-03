<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MetaSync;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\MetaSync\Http\GraphApiClient;
use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\MetaSyncCredentials;
use OliTheme\MetaSync\Publisher\FacebookPublisher;
use OliTheme\MetaSync\Publisher\PublishPayload;
use OliTheme\MetaSync\TokenStore;
use PHPUnit\Framework\TestCase;

final class FacebookPublisherTest extends TestCase
{
    private string $stored = '';

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg(1);
        Functions\when('get_option')->alias(fn (string $k) => $k === TokenStore::OPTION_KEY ? $this->stored : '');
        Functions\when('update_option')->alias(function (string $k, mixed $v) {
            if ($k === TokenStore::OPTION_KEY) {
                $this->stored = (string) $v;
            }
            return true;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * @param list<array<string, mixed>> $responses
     */
    private function publisher(array $responses, MetaSyncCredentials $creds): FacebookPublisher
    {
        $index = 0;
        $client = new GraphApiClient(GraphApiClient::DEFAULT_BASE, function (string $url, array $opts) use (&$index, $responses) {
            $r = $responses[$index] ?? ['body' => '{}', 'response' => ['code' => 200]];
            $index++;
            return $r;
        });
        $tokens = new TokenStore('test-key');
        $tokens->save($creds);
        return new FacebookPublisher($client, $tokens);
    }

    private function payload(): PublishPayload
    {
        return new PublishPayload(1, 'Titre', 'Extrait', 'Corps', 'https://oli/post-1');
    }

    public function test_create_returns_fb_post_id(): void
    {
        $publisher = $this->publisher(
            [['body' => json_encode(['id' => '100123_456789']), 'response' => ['code' => 200]]],
            new MetaSyncCredentials(pageId: 'PAGE', accessToken: 'TOK'),
        );
        $result = $publisher->create($this->payload());
        self::assertSame('100123_456789', $result);
    }

    public function test_create_fails_when_not_connected(): void
    {
        $publisher = $this->publisher(
            [['body' => '{}', 'response' => ['code' => 200]]],
            new MetaSyncCredentials(),
        );
        $result = $publisher->create($this->payload());
        self::assertInstanceOf(GraphApiError::class, $result);
        self::assertSame('not_connected', $result->type);
    }

    public function test_create_propagates_graph_error(): void
    {
        $publisher = $this->publisher(
            [['body' => json_encode(['error' => ['code' => 200, 'message' => 'no perm']]), 'response' => ['code' => 403]]],
            new MetaSyncCredentials(pageId: 'PAGE', accessToken: 'TOK'),
        );
        $result = $publisher->create($this->payload());
        self::assertInstanceOf(GraphApiError::class, $result);
        self::assertTrue($result->isPermissionError());
    }

    public function test_edit_returns_same_external_id(): void
    {
        $publisher = $this->publisher(
            [['body' => json_encode(['success' => true]), 'response' => ['code' => 200]]],
            new MetaSyncCredentials(pageId: 'PAGE', accessToken: 'TOK'),
        );
        $result = $publisher->edit('EXISTING_ID', $this->payload());
        self::assertSame('EXISTING_ID', $result);
    }

    public function test_delete_returns_true_on_success(): void
    {
        $publisher = $this->publisher(
            [['body' => json_encode(['success' => true]), 'response' => ['code' => 200]]],
            new MetaSyncCredentials(pageId: 'PAGE', accessToken: 'TOK'),
        );
        $result = $publisher->delete('SOME_ID');
        self::assertTrue($result);
    }

    public function test_delete_treats_404_as_success(): void
    {
        $publisher = $this->publisher(
            [['body' => json_encode(['error' => ['code' => 803, 'message' => 'not found']]), 'response' => ['code' => 404]]],
            new MetaSyncCredentials(pageId: 'PAGE', accessToken: 'TOK'),
        );
        $result = $publisher->delete('GONE_ID');
        self::assertTrue($result);
    }
}
