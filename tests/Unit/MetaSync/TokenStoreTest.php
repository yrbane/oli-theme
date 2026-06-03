<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MetaSync;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\MetaSync\MetaSyncCredentials;
use OliTheme\MetaSync\TokenStore;
use PHPUnit\Framework\TestCase;

final class TokenStoreTest extends TestCase
{
    private string $stored = '';

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->stored = '';
        Functions\when('get_option')->alias(fn (string $k) => $k === TokenStore::OPTION_KEY ? $this->stored : '');
        Functions\when('update_option')->alias(function (string $k, mixed $v) {
            if ($k === TokenStore::OPTION_KEY) {
                $this->stored = (string) $v;
            }
            return true;
        });
        Functions\when('delete_option')->alias(function (string $k): bool {
            if ($k === TokenStore::OPTION_KEY) {
                $this->stored = '';
            }
            return true;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_constructor_rejects_empty_secret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TokenStore('');
    }

    public function test_save_then_load_roundtrip(): void
    {
        $store = new TokenStore('my-very-long-and-random-auth-key-for-testing');
        $original = new MetaSyncCredentials(
            appId: '123456789',
            appSecret: 'supersecret',
            pageId: '100123_456',
            igUserId: '17841400000000000',
            accessToken: 'EAA...XYZ',
            expiresAt: 1700000000,
        );
        $store->save($original);

        $loaded = $store->load();
        self::assertEquals($original, $loaded);
    }

    public function test_load_returns_empty_when_no_persistence(): void
    {
        $store = new TokenStore('auth-key');
        $loaded = $store->load();
        self::assertSame('', $loaded->accessToken);
        self::assertFalse($loaded->isConnected());
    }

    public function test_load_returns_empty_when_key_changed(): void
    {
        $store1 = new TokenStore('original-key-do-not-share');
        $store1->save(new MetaSyncCredentials(accessToken: 'token123', pageId: 'p'));
        $payload = $this->stored;

        $store2 = new TokenStore('different-key');
        // Le payload chiffré avec store1 doit être indéchiffrable par store2.
        $loaded = $store2->load();
        self::assertSame('', $loaded->accessToken);

        // Le payload reste intact (TokenStore ne le réécrit pas).
        self::assertSame($payload, $this->stored);
    }

    public function test_load_returns_empty_on_tampered_payload(): void
    {
        $store = new TokenStore('auth-key-for-tamper-test');
        $store->save(new MetaSyncCredentials(accessToken: 'token456', pageId: 'p'));

        // Altère le dernier caractère du base64 du ciphertext (3e partie).
        // AES-GCM rejette toute altération grâce à son auth tag.
        $parts    = explode('.', $this->stored);
        $last     = $parts[2];
        $parts[2] = substr($last, 0, -1) . (str_ends_with($last, '=') ? '/' : '=');
        $this->stored = implode('.', $parts);

        $loaded = $store->load();
        self::assertSame('', $loaded->accessToken);
    }

    public function test_clear_wipes_stored_value(): void
    {
        $store = new TokenStore('key');
        $store->save(new MetaSyncCredentials(accessToken: 'token', pageId: 'p'));
        self::assertNotSame('', $this->stored);

        $store->clear();
        self::assertSame('', $this->stored);
        self::assertSame('', $store->load()->accessToken);
    }

    public function test_credentials_is_expiring_soon(): void
    {
        $now = 1_700_000_000;
        $soon = new MetaSyncCredentials(expiresAt: $now + 3 * 86400);
        $far = new MetaSyncCredentials(expiresAt: $now + 30 * 86400);
        $never = new MetaSyncCredentials();

        self::assertTrue($soon->isExpiringSoon($now));
        self::assertFalse($far->isExpiringSoon($now));
        self::assertFalse($never->isExpiringSoon($now));
    }
}
