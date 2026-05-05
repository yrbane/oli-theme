<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Contact;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Contact\ContactLogModel;
use OliTheme\Contact\ContactSubmission;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ContactLogModel.
 *
 * @package OliTheme\Tests\Unit\Contact
 *
 * @since 1.0.0
 */
final class ContactLogModelTest extends TestCase
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
     * Vérifie que log() retourne l'ID du post créé.
     */
    public function testLogReturnsPostId(): void
    {
        Functions\when('wp_insert_post')->justReturn(42);
        Functions\when('update_post_meta')->justReturn(true);

        $model = new ContactLogModel();
        $result = $model->log($this->makeSubmission());

        self::assertSame(42, $result);
    }

    /**
     * Vérifie que log() retourne 0 en cas d'échec d'insertion.
     */
    public function testLogReturnsZeroOnFailure(): void
    {
        Functions\when('wp_insert_post')->justReturn(0);

        $model = new ContactLogModel();
        $result = $model->log($this->makeSubmission());

        self::assertSame(0, $result);
    }

    /**
     * Construit une soumission de test.
     */
    private function makeSubmission(): ContactSubmission
    {
        return new ContactSubmission(
            name: 'Alice Dupont',
            email: 'alice@example.com',
            subject: 'Question',
            message: 'Mon message de test.',
            honeypot: '',
            timestamp: 1700000000,
            ip: '127.0.0.1',
        );
    }
}
