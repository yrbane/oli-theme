<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MetaSync;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\Lifecycle\MetaPostState;
use OliTheme\MetaSync\Lifecycle\MetaSyncDispatcher;
use OliTheme\MetaSync\Lifecycle\PayloadExtractorInterface;
use OliTheme\MetaSync\Publisher\PublisherInterface;
use OliTheme\MetaSync\Publisher\PublishPayload;
use PHPUnit\Framework\TestCase;

final class MetaSyncDispatcherTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $metas = [];
    /** @var list<array{platform:string, action:string, externalId?:string}> */
    private array $calls = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->metas = [];
        $this->calls = [];
        Functions\when('__')->returnArg(1);
        Functions\when('get_post_meta')->alias(function (int $id, string $key, bool $single = true) {
            return $this->metas[$id][$key] ?? '';
        });
        Functions\when('update_post_meta')->alias(function (int $id, string $key, mixed $value) {
            $this->metas[$id][$key] = $value;
            return true;
        });
        Functions\when('delete_post_meta')->alias(function (int $id, string $key) {
            unset($this->metas[$id][$key]);
            return true;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    private function dispatcher(string $fbResult = 'FB_ID', string $igResult = 'IG_ID'): MetaSyncDispatcher
    {
        $extractor = $this->createMock(PayloadExtractorInterface::class);
        $extractor->method('fromPost')->willReturnCallback(function (int $id) {
            return new PublishPayload($id, 'Titre', 'Extrait', 'Corps', 'https://oli/post-' . $id, 'https://oli/img.jpg');
        });
        $facebook = $this->createMock(PublisherInterface::class);
        $facebook->method('create')->willReturnCallback(function (PublishPayload $p) use ($fbResult) {
            $this->calls[] = ['platform' => 'facebook', 'action' => 'create'];
            return $fbResult;
        });
        $facebook->method('edit')->willReturnCallback(function (string $id, PublishPayload $p) {
            $this->calls[] = ['platform' => 'facebook', 'action' => 'edit', 'externalId' => $id];
            return $id;
        });
        $facebook->method('delete')->willReturnCallback(function (string $id) {
            $this->calls[] = ['platform' => 'facebook', 'action' => 'delete', 'externalId' => $id];
            return true;
        });
        $instagram = $this->createMock(PublisherInterface::class);
        $instagram->method('create')->willReturnCallback(function (PublishPayload $p) use ($igResult) {
            $this->calls[] = ['platform' => 'instagram', 'action' => 'create'];
            return $igResult;
        });
        $instagram->method('edit')->willReturnCallback(function (string $id, PublishPayload $p) {
            $this->calls[] = ['platform' => 'instagram', 'action' => 'edit', 'externalId' => $id];
            return $id;
        });
        $instagram->method('delete')->willReturnCallback(function (string $id) {
            $this->calls[] = ['platform' => 'instagram', 'action' => 'delete', 'externalId' => $id];
            return true;
        });
        return new MetaSyncDispatcher($extractor, new MetaPostState(), $facebook, $instagram);
    }

    public function test_on_publish_does_nothing_when_disabled(): void
    {
        $this->dispatcher()->onPublish(1);
        self::assertSame([], $this->calls);
    }

    public function test_on_publish_creates_on_both_targets_when_enabled(): void
    {
        $this->metas[1] = [MetaPostState::META_ENABLED => true];
        $this->dispatcher()->onPublish(1);
        $actions = array_column($this->calls, 'action');
        self::assertContains('create', $actions);
        self::assertCount(2, $this->calls);
        self::assertSame('FB_ID', $this->metas[1][MetaPostState::META_FB_ID]);
        self::assertSame('IG_ID', $this->metas[1][MetaPostState::META_IG_ID]);
        self::assertNotEmpty($this->metas[1][MetaPostState::META_CONTENT_HASH]);
    }

    public function test_on_publish_skips_create_if_external_id_exists(): void
    {
        $this->metas[1] = [
            MetaPostState::META_ENABLED => true,
            MetaPostState::META_FB_ID   => 'ALREADY_FB',
        ];
        $this->dispatcher()->onPublish(1);
        // FB déjà créé → on ne re-crée que IG.
        self::assertCount(1, $this->calls);
        self::assertSame('instagram', $this->calls[0]['platform']);
    }

    public function test_on_update_skips_when_content_hash_unchanged(): void
    {
        $payload = new PublishPayload(1, 'Titre', 'Extrait', 'Corps', 'https://oli/post-1', 'https://oli/img.jpg');
        $this->metas[1] = [
            MetaPostState::META_ENABLED       => true,
            MetaPostState::META_FB_ID         => 'FB',
            MetaPostState::META_IG_ID         => 'IG',
            MetaPostState::META_CONTENT_HASH  => $payload->contentHash(),
        ];
        $this->dispatcher()->onUpdate(1);
        self::assertSame([], $this->calls);
    }

    public function test_on_update_edits_when_hash_differs(): void
    {
        $this->metas[1] = [
            MetaPostState::META_ENABLED      => true,
            MetaPostState::META_FB_ID        => 'FB',
            MetaPostState::META_IG_ID        => 'IG',
            MetaPostState::META_CONTENT_HASH => 'old-hash',
        ];
        $this->dispatcher()->onUpdate(1);
        $actions = array_column($this->calls, 'action');
        self::assertContains('edit', $actions);
        self::assertCount(2, $this->calls);
    }

    public function test_on_delete_deletes_both_targets(): void
    {
        $this->metas[1] = [
            MetaPostState::META_FB_ID => 'FB',
            MetaPostState::META_IG_ID => 'IG',
        ];
        $this->dispatcher()->onDelete(1);
        self::assertCount(2, $this->calls);
        self::assertArrayNotHasKey(MetaPostState::META_FB_ID, $this->metas[1] ?? []);
    }

    public function test_records_error_on_create_failure(): void
    {
        $this->metas[1] = [MetaPostState::META_ENABLED => true];
        $extractor = $this->createMock(PayloadExtractorInterface::class);
        $extractor->method('fromPost')->willReturn(new PublishPayload(1, 'T', 'E', 'C', 'u'));
        $facebook = $this->createMock(PublisherInterface::class);
        $facebook->method('create')->willReturn(new GraphApiError(403, 'OAuthException', 'no perm', 200));
        $instagram = $this->createMock(PublisherInterface::class);
        $instagram->method('create')->willReturn('IG_OK');

        (new MetaSyncDispatcher($extractor, new MetaPostState(), $facebook, $instagram))->onPublish(1);
        // FB échoue + IG réussit → status `partial` (l'erreur n'est pas masquée
        // par le succès partiel) avec le message d'erreur conservé.
        self::assertSame('partial', $this->metas[1][MetaPostState::META_LAST_SYNC_STATUS]);
        self::assertStringContainsString('FACEBOOK create', (string) $this->metas[1][MetaPostState::META_LAST_SYNC_ERROR]);
    }
}
