<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Contact;

use OliTheme\Contact\ContactSubmission;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ContactSubmission.
 *
 * @package OliTheme\Tests\Unit\Contact
 *
 * @since 1.0.0
 */
final class ContactSubmissionTest extends TestCase
{
    /**
     * Vérifie que toutes les propriétés du DTO sont exposées correctement.
     */
    public function testItExposesAllProperties(): void
    {
        $submission = new ContactSubmission(
            name: 'Alice',
            email: 'alice@example.com',
            subject: 'Bonjour',
            message: 'Ceci est un message de test.',
            honeypot: '',
            timestamp: 1700000000,
            ip: '127.0.0.1',
        );

        self::assertSame('Alice', $submission->name);
        self::assertSame('alice@example.com', $submission->email);
        self::assertSame('Bonjour', $submission->subject);
        self::assertSame('Ceci est un message de test.', $submission->message);
        self::assertSame('', $submission->honeypot);
        self::assertSame(1700000000, $submission->timestamp);
        self::assertSame('127.0.0.1', $submission->ip);
    }
}
