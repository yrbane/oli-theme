<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MetaSync;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\MetaSync\Http\GraphApiClient;
use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\MetaSyncCredentials;
use OliTheme\MetaSync\Publisher\InstagramEditStrategy;
use OliTheme\MetaSync\Publisher\InstagramPublisher;
use OliTheme\MetaSync\Publisher\PublishPayload;
use OliTheme\MetaSync\TokenStore;
use PHPUnit\Framework\TestCase;

final class InstagramPublisherTest extends TestCase
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
    private function publisher(array $responses, MetaSyncCredentials $creds, InstagramEditStrategy $strategy = InstagramEditStrategy::Skip): InstagramPublisher
    {
        $index = 0;
        $client = new GraphApiClient(GraphApiClient::DEFAULT_BASE, function (string $url, array $opts) use (&$index, $responses) {
            $r = $responses[$index] ?? ['body' => '{}', 'response' => ['code' => 200]];
            $index++;
            return $r;
        });
        $tokens = new TokenStore('test-key');
        $tokens->save($creds);
        return new InstagramPublisher($client, $tokens, $strategy);
    }

    private function payloadWithImage(): PublishPayload
    {
        return new PublishPayload(1, 'Titre', 'Extrait', 'Corps', 'https://oli/post-1', 'https://oli/img.jpg');
    }

    public function test_create_returns_media_id_after_two_step_workflow(): void
    {
        $pub = $this->publisher(
            [
                ['body' => json_encode(['id' => 'CONTAINER1']),  'response' => ['code' => 200]],
                ['body' => json_encode(['id' => 'MEDIA12345']),  'response' => ['code' => 200]],
            ],
            new MetaSyncCredentials(pageId: 'P', igUserId: 'IG1', accessToken: 'T'),
        );
        $result = $pub->create($this->payloadWithImage());
        self::assertSame('MEDIA12345', $result);
    }

    public function test_create_rejects_when_no_image(): void
    {
        $pub = $this->publisher(
            [['body' => '{}', 'response' => ['code' => 200]]],
            new MetaSyncCredentials(pageId: 'P', igUserId: 'IG', accessToken: 'T'),
        );
        $payload = new PublishPayload(1, 'T', 'E', 'C', 'https://x'); // pas de featuredImageUrl
        $result = $pub->create($payload);
        self::assertInstanceOf(GraphApiError::class, $result);
        self::assertSame('no_image', $result->type);
    }

    public function test_create_rejects_when_ig_not_linked(): void
    {
        $pub = $this->publisher(
            [['body' => '{}', 'response' => ['code' => 200]]],
            new MetaSyncCredentials(pageId: 'P', igUserId: '', accessToken: 'T'),
        );
        $result = $pub->create($this->payloadWithImage());
        self::assertInstanceOf(GraphApiError::class, $result);
        self::assertSame('not_connected', $result->type);
    }

    public function test_edit_skip_strategy_returns_existing_id(): void
    {
        $pub = $this->publisher(
            [['body' => '{}', 'response' => ['code' => 200]]],
            new MetaSyncCredentials(pageId: 'P', igUserId: 'IG', accessToken: 'T'),
            InstagramEditStrategy::Skip,
        );
        $result = $pub->edit('EXISTING_IG_ID', $this->payloadWithImage());
        self::assertSame('EXISTING_IG_ID', $result);
    }

    public function test_edit_delete_recreate_returns_new_id(): void
    {
        $pub = $this->publisher(
            [
                ['body' => json_encode(['success' => true]),       'response' => ['code' => 200]], // delete
                ['body' => json_encode(['id' => 'NEW_CONTAINER']), 'response' => ['code' => 200]], // create step 1
                ['body' => json_encode(['id' => 'NEW_MEDIA']),     'response' => ['code' => 200]], // publish step 2
            ],
            new MetaSyncCredentials(pageId: 'P', igUserId: 'IG', accessToken: 'T'),
            InstagramEditStrategy::DeleteRecreate,
        );
        $result = $pub->edit('OLD_IG_ID', $this->payloadWithImage());
        self::assertSame('NEW_MEDIA', $result);
    }

    public function test_delete_treats_404_as_success(): void
    {
        $pub = $this->publisher(
            [['body' => json_encode(['error' => ['code' => 803, 'message' => 'gone']]), 'response' => ['code' => 404]]],
            new MetaSyncCredentials(pageId: 'P', igUserId: 'IG', accessToken: 'T'),
        );
        $result = $pub->delete('GONE');
        self::assertTrue($result);
    }
}
