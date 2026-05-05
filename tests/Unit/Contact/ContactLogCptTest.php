<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Contact;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Contact\ContactLogCpt;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ContactLogCpt.
 *
 * @package OliTheme\Tests\Unit\Contact
 *
 * @since 1.0.0
 */
final class ContactLogCptTest extends TestCase
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
     * Vérifie que register() appelle register_post_type avec le bon slug et la bonne structure.
     */
    public function testRegisterCallsRegisterPostType(): void
    {
        $capturedSlug = null;
        $capturedArgs = null;

        Functions\when('__')->returnArg(1);

        Functions\expect('register_post_type')
            ->once()
            ->andReturnUsing(
                static function (string $slug, array $args) use (&$capturedSlug, &$capturedArgs): void {
                    $capturedSlug = $slug;
                    $capturedArgs = $args;
                },
            );

        $cpt = new ContactLogCpt();
        $cpt->register();

        self::assertSame('oli_contact_log', $capturedSlug);
        self::assertSame('oli_contact_log', $cpt->slug());
        self::assertFalse($capturedArgs['public']);
        self::assertTrue($capturedArgs['show_ui']);
        self::assertSame('tools.php', $capturedArgs['show_in_menu']);
        self::assertContains('title', $capturedArgs['supports']);
        self::assertSame('do_not_allow', $capturedArgs['capabilities']['create_posts']);
    }
}
