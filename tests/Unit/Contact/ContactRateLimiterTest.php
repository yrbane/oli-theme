<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Contact;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Contact\ContactRateLimiter;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ContactRateLimiter.
 *
 * @package OliTheme\Tests\Unit\Contact
 *
 * @since 1.0.0
 */
final class ContactRateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Vérifie que la première tentative est autorisée et que le compteur est initialisé à 1.
     */
    public function testFirstAttemptAllowed(): void
    {
        $captured = [];

        Functions\when('get_transient')->justReturn(false);
        Functions\when('set_transient')->alias(
            static function (string $k, int $v, int $ttl) use (&$captured): bool {
                $captured[$k] = $v;

                return true;
            },
        );

        $limiter = new ContactRateLimiter();
        $result = $limiter->attempt('1.2.3.4');

        self::assertTrue($result);
        self::assertCount(1, $captured);
        self::assertSame(1, \array_values($captured)[0]);
    }

    /**
     * Vérifie que la quatrième tentative est bloquée lorsque le compteur vaut 3.
     */
    public function testFourthAttemptBlocked(): void
    {
        Functions\when('get_transient')->justReturn(3);

        $limiter = new ContactRateLimiter();
        $result = $limiter->attempt('1.2.3.4');

        self::assertFalse($result);
    }

    /**
     * Vérifie que le compteur est isolé par IP (clé de transient différente).
     */
    public function testCounterIsolatedPerIp(): void
    {
        $captured = [];

        Functions\when('get_transient')->justReturn(false);
        Functions\when('set_transient')->alias(
            static function (string $k, int $v, int $ttl) use (&$captured): bool {
                $captured[$k] = $v;

                return true;
            },
        );

        $limiter = new ContactRateLimiter();
        $r1 = $limiter->attempt('1.1.1.1');
        $r2 = $limiter->attempt('2.2.2.2');

        self::assertTrue($r1);
        self::assertTrue($r2);
        self::assertCount(2, $captured);

        $keys = \array_keys($captured);
        self::assertNotSame($keys[0], $keys[1]);
    }
}
