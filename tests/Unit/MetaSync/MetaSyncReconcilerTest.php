<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MetaSync;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\MetaSync\Http\GraphApiClient;
use OliTheme\MetaSync\Lifecycle\MetaPostState;
use OliTheme\MetaSync\Lifecycle\MetaSyncReconciler;
use OliTheme\MetaSync\MetaSyncCredentials;
use OliTheme\MetaSync\TokenStore;
use PHPUnit\Framework\TestCase;

final class MetaSyncReconcilerTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $metas = [];
    private string $tokenStored = '';
    private int $fbReqs = 0;
    private int $igReqs = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->metas = [];
        $this->tokenStored = '';
        $this->fbReqs = 0;
        $this->igReqs = 0;

        Functions\when('__')->returnArg(1);
        Functions\when('get_post_meta')->alias(fn (int $id, string $key, bool $s = true) => $this->metas[$id][$key] ?? '');
        Functions\when('update_post_meta')->alias(function (int $id, string $key, mixed $value) {
            $this->metas[$id][$key] = $value;
            return true;
        });
        Functions\when('delete_post_meta')->alias(function (int $id, string $key) {
            unset($this->metas[$id][$key]);
            return true;
        });
        Functions\when('get_option')->alias(fn (string $k) => $k === TokenStore::OPTION_KEY ? $this->tokenStored : '');
        Functions\when('update_option')->alias(function (string $k, mixed $v) {
            if ($k === TokenStore::OPTION_KEY) {
                $this->tokenStored = (string) $v;
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
     * @param list<int> $fbPosts
     * @param list<int> $igPosts
     * @param array<string, 'ok'|'gone'> $statuses keyed by external id
     */
    private function reconciler(array $fbPosts, array $igPosts, array $statuses): MetaSyncReconciler
    {
        Functions\when('get_posts')->alias(function (array $args) use ($fbPosts, $igPosts) {
            $key = $args['meta_query'][0]['key'] ?? '';
            if ($key === MetaPostState::META_FB_ID) {
                return $fbPosts;
            }
            if ($key === MetaPostState::META_IG_ID) {
                return $igPosts;
            }
            return [];
        });

        $client = new GraphApiClient(GraphApiClient::DEFAULT_BASE, function (string $url) use ($statuses) {
            // Extract external id from URL `/extid?fields=...&access_token=...`.
            preg_match('~/([^/?]+)(\?|$)~', $url, $m);
            $id = $m[1] ?? '';
            $status = $statuses[$id] ?? 'ok';
            if ($status === 'gone') {
                return ['body' => json_encode(['error' => ['code' => 803, 'message' => 'gone']]), 'response' => ['code' => 404]];
            }
            return ['body' => json_encode(['id' => $id]), 'response' => ['code' => 200]];
        });
        $tokens = new TokenStore('test');
        $tokens->save(new MetaSyncCredentials(pageId: 'P', igUserId: 'IG', accessToken: 'TOK'));

        return new MetaSyncReconciler($client, $tokens, new MetaPostState());
    }

    public function test_returns_zero_when_not_connected(): void
    {
        Functions\when('get_posts')->justReturn([]);
        $tokens = new TokenStore('test');
        $rec = new MetaSyncReconciler(new GraphApiClient(), $tokens, new MetaPostState());
        self::assertSame(['checked' => 0, 'cleaned' => 0], $rec->run());
    }

    public function test_keeps_metas_when_external_exists(): void
    {
        $this->metas[1] = [MetaPostState::META_FB_ID => 'FB1', MetaPostState::META_IG_ID => 'IG1'];
        $rec = $this->reconciler([1], [1], ['FB1' => 'ok', 'IG1' => 'ok']);
        $result = $rec->run();
        self::assertSame(2, $result['checked']);
        self::assertSame(0, $result['cleaned']);
        self::assertSame('FB1', $this->metas[1][MetaPostState::META_FB_ID]);
        self::assertSame('IG1', $this->metas[1][MetaPostState::META_IG_ID]);
    }

    public function test_cleans_metas_when_external_returns_404(): void
    {
        $this->metas[1] = [MetaPostState::META_FB_ID => 'FB1', MetaPostState::META_IG_ID => 'IG1'];
        $rec = $this->reconciler([1], [1], ['FB1' => 'gone', 'IG1' => 'gone']);
        $result = $rec->run();
        self::assertSame(2, $result['checked']);
        self::assertSame(2, $result['cleaned']);
        self::assertArrayNotHasKey(MetaPostState::META_FB_ID, $this->metas[1]);
        self::assertArrayNotHasKey(MetaPostState::META_IG_ID, $this->metas[1]);
    }

    public function test_handles_one_present_one_missing(): void
    {
        $this->metas[1] = [MetaPostState::META_FB_ID => 'FB1', MetaPostState::META_IG_ID => 'IG1'];
        $rec = $this->reconciler([1], [1], ['FB1' => 'ok', 'IG1' => 'gone']);
        $result = $rec->run();
        self::assertSame(2, $result['checked']);
        self::assertSame(1, $result['cleaned']);
        self::assertSame('FB1', $this->metas[1][MetaPostState::META_FB_ID]);
        self::assertArrayNotHasKey(MetaPostState::META_IG_ID, $this->metas[1]);
    }
}
