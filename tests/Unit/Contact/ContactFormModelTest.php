<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Contact;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Contact\ContactFormModel;
use OliTheme\Contact\ContactSubmission;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ContactFormModel.
 *
 * @package OliTheme\Tests\Unit\Contact
 *
 * @since 1.0.0
 */
final class ContactFormModelTest extends TestCase
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
     * Vérifie qu'une soumission valide passe sans erreur.
     */
    public function testValidatesValidSubmission(): void
    {
        Functions\when('is_email')->justReturn('alice@example.com');

        $model = $this->makeModel();
        $result = $model->validate($this->makeSubmission());

        self::assertTrue($result->valid);
        self::assertSame([], $result->errors);
    }

    /**
     * Vérifie qu'un nom trop court génère l'erreur name_invalid.
     */
    public function testRejectsShortName(): void
    {
        Functions\when('is_email')->justReturn('alice@example.com');

        $model = $this->makeModel();
        $result = $model->validate($this->makeSubmission(['name' => 'a']));

        self::assertFalse($result->valid);
        self::assertSame('name_invalid', $result->errors['name']);
    }

    /**
     * Vérifie qu'un email invalide génère l'erreur email_invalid.
     */
    public function testRejectsBadEmail(): void
    {
        Functions\when('is_email')->justReturn(false);

        $model = $this->makeModel();
        $result = $model->validate($this->makeSubmission(['email' => 'not-an-email']));

        self::assertFalse($result->valid);
        self::assertSame('email_invalid', $result->errors['email']);
    }

    /**
     * Vérifie qu'un honeypot non vide génère l'erreur spam_detected.
     */
    public function testRejectsHoneypot(): void
    {
        Functions\when('is_email')->justReturn('alice@example.com');

        $model = $this->makeModel();
        $result = $model->validate($this->makeSubmission(['honeypot' => 'spam']));

        self::assertFalse($result->valid);
        self::assertSame('spam_detected', $result->errors['honeypot']);
    }

    /**
     * Vérifie qu'une soumission trop rapide génère l'erreur too_fast.
     */
    public function testRejectsTooFast(): void
    {
        Functions\when('is_email')->justReturn('alice@example.com');

        // Horloge fixe à 1700000000, timestamp = now - 1 → différence < 3 → too_fast
        $model = $this->makeModel();
        $result = $model->validate($this->makeSubmission(['timestamp' => 1699999999]));

        self::assertFalse($result->valid);
        self::assertSame('too_fast', $result->errors['timestamp']);
    }

    /**
     * Vérifie que sanitize retourne une nouvelle instance avec les champs sanitisés.
     */
    public function testSanitizeReturnsNewInstance(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('sanitize_email')->returnArg(1);
        Functions\when('sanitize_textarea_field')->returnArg(1);

        $original = $this->makeSubmission();
        $model = $this->makeModel();
        $sanitized = $model->sanitize($original);

        self::assertNotSame($original, $sanitized);
        self::assertSame($original->timestamp, $sanitized->timestamp);
        self::assertSame($original->ip, $sanitized->ip);
        self::assertSame($original->name, $sanitized->name);
        self::assertSame($original->email, $sanitized->email);
    }

    /**
     * Construit une soumission valide pour les tests.
     *
     * @param array<string, mixed> $overrides Valeurs à surcharger.
     */
    private function makeSubmission(array $overrides = []): ContactSubmission
    {
        return new ContactSubmission(
            name: $overrides['name'] ?? 'Alice Dupont',
            email: $overrides['email'] ?? 'alice@example.com',
            subject: $overrides['subject'] ?? 'Bonjour',
            message: $overrides['message'] ?? 'Ceci est un message suffisamment long.',
            honeypot: $overrides['honeypot'] ?? '',
            timestamp: $overrides['timestamp'] ?? 1699999997,
            ip: $overrides['ip'] ?? '127.0.0.1',
        );
    }

    /**
     * Construit un modèle avec une horloge fixe à 1700000000.
     */
    private function makeModel(): ContactFormModel
    {
        return new ContactFormModel(clock: static fn (): int => 1700000000);
    }
}
