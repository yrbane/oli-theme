<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Events;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\RendererInterface;
use OliTheme\Events\EventMetabox;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de EventMetabox (admin avec nonce + save).
 *
 * @package OliTheme\Tests\Unit\Events
 *
 * @since 1.0.0
 */
final class EventMetaboxTest extends TestCase
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

    public function testRegisterRegistersMetabox(): void
    {
        $capturedId = null;
        $capturedScreen = null;

        Functions\when('__')->returnArg(1);
        Functions\when('add_meta_box')->alias(
            static function (string $id, string $title, callable $callback, string $screen) use (&$capturedId, &$capturedScreen): void {
                $capturedId = $id;
                $capturedScreen = $screen;
            },
        );

        $renderer = $this->createMock(RendererInterface::class);
        (new EventMetabox($renderer))->register();

        self::assertSame('oli_event_meta', $capturedId);
        self::assertSame('oli_event', $capturedScreen);
    }

    public function testSavePersistsAllFieldsWhenNonceValid(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('sanitize_text_field')->returnArg(1);

        $capturedCalls = [];
        Functions\when('update_post_meta')->alias(
            static function (int $postId, string $metaKey, string $value) use (&$capturedCalls): void {
                $capturedCalls[] = ['postId' => $postId, 'key' => $metaKey, 'value' => $value];
            },
        );

        $renderer = $this->createMock(RendererInterface::class);
        $metabox = new EventMetabox($renderer);
        $metabox->save(7, [
            'oli_event_meta_nonce' => 'valid-nonce',
            'startDate' => '2026-07-01 19:00:00',
            'endDate' => '2026-07-01 22:00:00',
            'location' => 'Salle Pleyel',
            'address' => '252 Rue du Faubourg',
            'flyerUrl' => 'https://cdn/flyer.jpg',
            'registrationUrl' => 'https://ticketing.example.com',
            'price' => '25€',
        ]);

        self::assertCount(7, $capturedCalls);

        $keys = array_column($capturedCalls, 'key');
        self::assertContains('_oli_event_start_date', $keys);
        self::assertContains('_oli_event_end_date', $keys);
        self::assertContains('_oli_event_location', $keys);
        self::assertContains('_oli_event_address', $keys);
        self::assertContains('_oli_event_flyer_url', $keys);
        self::assertContains('_oli_event_registration_url', $keys);
        self::assertContains('_oli_event_price', $keys);
    }

    public function testSaveAbortsWhenNonceInvalid(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\expect('update_post_meta')->never();

        $renderer = $this->createMock(RendererInterface::class);
        $metabox = new EventMetabox($renderer);
        $metabox->save(7, ['oli_event_meta_nonce' => 'invalid-nonce']);

        $this->addToAssertionCount(1);
    }
}
