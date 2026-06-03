<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MetaSync;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\MetaSync\Http\GraphApiClient;
use OliTheme\MetaSync\MetaSyncCredentials;
use OliTheme\MetaSync\Publisher\FacebookEventPublisher;
use OliTheme\MetaSync\Publisher\PublishPayload;
use OliTheme\MetaSync\TokenStore;
use PHPUnit\Framework\TestCase;

final class FacebookEventPublisherTest extends TestCase
{
    private string $stored = '';
    /** @var list<array{url:string,opts:array<string,mixed>}> */
    private array $spy = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->stored = '';
        $this->spy    = [];
        Functions\when('__')->returnArg(1);
        Functions\when('get_option')->alias(fn (string $k) => $k === TokenStore::OPTION_KEY ? $this->stored : '');
        Functions\when('update_option')->alias(function (string $k, mixed $v) {
            if ($k === TokenStore::OPTION_KEY) {
                $this->stored = (string) $v;
            }
            return true;
        });
        Functions\when('get_post_meta')->alias(fn (int $id, string $key, bool $s = true) =>
            $key === '_oli_event_start'    ? '2026-06-10 14:00:00' :
            ($key === '_oli_event_location' ? 'Atelier d\'Olivier' : '')
        );
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * @param list<array<string, mixed>> $responses
     */
    private function publisher(array $responses): FacebookEventPublisher
    {
        $index = 0;
        $client = new GraphApiClient(GraphApiClient::DEFAULT_BASE, function (string $url, array $opts) use (&$index, $responses) {
            $this->spy[] = ['url' => $url, 'opts' => $opts];
            $r = $responses[$index] ?? ['body' => '{}', 'response' => ['code' => 200]];
            $index++;
            return $r;
        });
        $tokens = new TokenStore('test');
        $tokens->save(new MetaSyncCredentials(pageId: 'PAGE', accessToken: 'TOK'));
        return new FacebookEventPublisher($client, $tokens);
    }

    private function payload(): PublishPayload
    {
        return new PublishPayload(42, 'Atelier Kalari', 'Découverte', 'Long contenu', 'https://oli/event-42');
    }

    public function test_native_event_creation_succeeds(): void
    {
        $pub = $this->publisher([
            ['body' => json_encode(['id' => 'EVENT_NATIVE_123']), 'response' => ['code' => 200]],
        ]);
        $result = $pub->create($this->payload());
        self::assertSame('EVENT_NATIVE_123', $result);
        self::assertStringContainsString('/PAGE/events', $this->spy[0]['url']);
    }

    public function test_fallback_to_feed_when_events_api_returns_permission_error(): void
    {
        $pub = $this->publisher([
            ['body' => json_encode(['error' => ['code' => 200, 'message' => 'Events API disabled']]), 'response' => ['code' => 403]],
            ['body' => json_encode(['id' => 'POST_FALLBACK_456']),                                     'response' => ['code' => 200]],
        ]);
        $result = $pub->create($this->payload());
        self::assertSame('POST_FALLBACK_456', $result);
        self::assertStringContainsString('/PAGE/feed', $this->spy[1]['url']);

        // Le message fallback inclut titre + date + lieu + permalien.
        $message = $this->spy[1]['opts']['body']['message'];
        self::assertStringContainsString('Atelier Kalari', $message);
        self::assertStringContainsString('10/06/2026', $message);
        self::assertStringContainsString('Atelier d\'Olivier', $message);
        self::assertStringContainsString('https://oli/event-42', $message);
    }

    public function test_fallback_when_no_event_date(): void
    {
        Functions\when('get_post_meta')->alias(fn (int $id, string $k, bool $s = true) => '');
        $pub = $this->publisher([
            ['body' => json_encode(['id' => 'POST_NO_DATE']), 'response' => ['code' => 200]],
        ]);
        $result = $pub->create($this->payload());
        self::assertSame('POST_NO_DATE', $result);
        // Le premier (et seul) appel est /feed, pas /events.
        self::assertStringContainsString('/PAGE/feed', $this->spy[0]['url']);
    }

    public function test_delete_uses_unified_endpoint(): void
    {
        $pub = $this->publisher([['body' => json_encode(['success' => true]), 'response' => ['code' => 200]]]);
        $result = $pub->delete('EVENT_OR_POST_ID');
        self::assertTrue($result);
    }

    public function test_delete_treats_404_as_success(): void
    {
        $pub = $this->publisher([
            ['body' => json_encode(['error' => ['code' => 803, 'message' => 'gone']]), 'response' => ['code' => 404]],
        ]);
        self::assertTrue($pub->delete('GONE'));
    }
}
