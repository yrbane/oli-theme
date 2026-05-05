<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Contact;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Contact\ContactShortcode;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageResolverInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ContactShortcode.
 *
 * @package OliTheme\Tests\Unit\Contact
 *
 * @since 1.0.0
 */
final class ContactShortcodeTest extends TestCase
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
     * Construit un shortcode avec des mocks par défaut.
     *
     * @param array<string, mixed>|null $capturedVm Référence pour capturer les variables du template.
     */
    private function makeShortcode(?array &$capturedVm = null): ContactShortcode
    {
        $renderer = $this->createMock(RendererInterface::class);
        $renderer->method('render')
            ->willReturnCallback(
                static function (string $tpl, array $vm) use (&$capturedVm): string {
                    $capturedVm = $vm;

                    return '<form>';
                },
            );

        $lang = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $resolver = $this->createMock(LanguageResolverInterface::class);
        $resolver->method('current')->willReturn($lang);

        return new ContactShortcode($renderer, $resolver);
    }

    /**
     * Vérifie que render() retourne le HTML produit par le renderer.
     */
    public function testRendersFormHtml(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_create_nonce')->justReturn('test-nonce');
        Functions\when('admin_url')->justReturn('https://example.com/wp-admin/admin-post.php');

        $shortcode = $this->makeShortcode();
        $result = $shortcode->render([]);

        self::assertSame('<form>', $result);
    }

    /**
     * Vérifie que le viewmodel contient nonce (non vide) et timestamp (int).
     */
    public function testIncludesNonceAndTimestamp(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_create_nonce')->justReturn('abc123');
        Functions\when('admin_url')->justReturn('https://example.com/wp-admin/admin-post.php');

        $vm = null;
        $shortcode = $this->makeShortcode($vm);
        $shortcode->render([]);

        self::assertNotNull($vm);
        self::assertIsString($vm['nonce']);
        self::assertNotEmpty($vm['nonce']);
        self::assertIsInt($vm['timestamp']);
    }
}
