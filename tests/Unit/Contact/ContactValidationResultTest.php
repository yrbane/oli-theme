<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Contact;

use OliTheme\Contact\ContactValidationResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ContactValidationResult.
 *
 * @package OliTheme\Tests\Unit\Contact
 *
 * @since 1.0.0
 */
final class ContactValidationResultTest extends TestCase
{
    /**
     * Vérifie que ok() construit un résultat valide sans erreurs.
     */
    public function testOkConstructsValidResult(): void
    {
        $result = ContactValidationResult::ok();

        self::assertTrue($result->valid);
        self::assertSame([], $result->errors);
    }

    /**
     * Vérifie que failed() construit un résultat invalide avec les erreurs données.
     */
    public function testFailedConstructsInvalidResult(): void
    {
        $errors = ['name' => 'name_invalid', 'email' => 'email_invalid'];
        $result = ContactValidationResult::failed($errors);

        self::assertFalse($result->valid);
        self::assertSame($errors, $result->errors);
    }
}
