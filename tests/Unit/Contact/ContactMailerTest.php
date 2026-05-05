<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Contact;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Contact\ContactMailer;
use OliTheme\Contact\ContactSubmission;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ContactMailer.
 *
 * @package OliTheme\Tests\Unit\Contact
 *
 * @since 1.0.0
 */
final class ContactMailerTest extends TestCase
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
     * Construit une soumission de test.
     */
    private function makeSubmission(): ContactSubmission
    {
        return new ContactSubmission(
            name: 'Alice Dupont',
            email: 'alice@example.com',
            subject: null,
            message: 'Ceci est un message de test.',
            honeypot: '',
            timestamp: 1700000000,
            ip: '127.0.0.1',
        );
    }

    /**
     * Vérifie que send() appelle wp_mail avec les bons headers (Reply-To).
     */
    public function testSendCallsWpMailWithExpectedHeaders(): void
    {
        $capturedHeaders = null;

        Functions\expect('wp_mail')
            ->once()
            ->andReturnUsing(
                static function (string $to, string $subject, string $body, array $headers) use (&$capturedHeaders): bool {
                    $capturedHeaders = $headers;

                    return true;
                },
            );

        $mailer = new ContactMailer();
        $mailer->send($this->makeSubmission(), 'admin@example.com');

        self::assertNotNull($capturedHeaders);
        $replyTo = \implode("\n", $capturedHeaders);
        self::assertStringContainsString('Reply-To: Alice Dupont <alice@example.com>', $replyTo);
    }

    /**
     * Vérifie que send() utilise le sujet de la soumission lorsqu'il est renseigné.
     */
    public function testSendUsesSubjectFromSubmissionWhenPresent(): void
    {
        $capturedSubject = null;

        Functions\expect('wp_mail')
            ->once()
            ->andReturnUsing(
                static function (string $to, string $subject, string $body, array $headers) use (&$capturedSubject): bool {
                    $capturedSubject = $subject;

                    return true;
                },
            );

        $submission = new ContactSubmission(
            name: 'Alice Dupont',
            email: 'alice@example.com',
            subject: 'Question',
            message: 'Mon message de test.',
            honeypot: '',
            timestamp: 1700000000,
            ip: '127.0.0.1',
        );

        $mailer = new ContactMailer();
        $mailer->send($submission, 'admin@example.com');

        self::assertSame('[Contact] Question', $capturedSubject);
    }

    /**
     * Vérifie que sendAutoReply() envoie à l'adresse e-mail de l'expéditeur.
     */
    public function testSendAutoReplySendsToSubmissionEmail(): void
    {
        $capturedTo = null;

        Functions\expect('wp_mail')
            ->once()
            ->andReturnUsing(
                static function (string $to, string $subject, string $body, array $headers) use (&$capturedTo): bool {
                    $capturedTo = $to;

                    return true;
                },
            );

        $mailer = new ContactMailer();
        $mailer->sendAutoReply($this->makeSubmission(), 'Merci pour votre message.');

        self::assertSame('alice@example.com', $capturedTo);
    }
}
