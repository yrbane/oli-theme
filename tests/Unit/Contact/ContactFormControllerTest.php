<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Contact;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Contact\ContactFormController;
use OliTheme\Contact\ContactFormModelInterface;
use OliTheme\Contact\ContactLogModelInterface;
use OliTheme\Contact\ContactMailerInterface;
use OliTheme\Contact\ContactRateLimiterInterface;
use OliTheme\Contact\ContactSubmission;
use OliTheme\Contact\ContactValidationResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ContactFormController.
 *
 * @package OliTheme\Tests\Unit\Contact
 *
 * @since 1.0.0
 */
final class ContactFormControllerTest extends TestCase
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
     * Vérifie que handle() appelle wp_die si le nonce est invalide.
     */
    public function testRejectsInvalidNonce(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\when('esc_html__')->returnArg(1);

        Functions\expect('wp_die')->once()->andReturnNull();

        $model = $this->createMock(ContactFormModelInterface::class);
        $model->expects(self::never())->method('validate');

        $rateLimiter = $this->createMock(ContactRateLimiterInterface::class);
        $rateLimiter->expects(self::never())->method('attempt');

        $mailer = $this->createMock(ContactMailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $controller = $this->makeController(model: $model, rateLimiter: $rateLimiter, mailer: $mailer);
        $controller->handle($this->makePostData());

        $this->addToAssertionCount(1);
    }

    /**
     * Vérifie que handle() redirige avec error=rate_limit si le débit est dépassé.
     */
    public function testRedirectsOnRateLimit(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('home_url')->justReturn('https://example.com/');

        $capturedUrl = null;
        Functions\expect('wp_safe_redirect')
            ->once()
            ->andReturnUsing(static function (string $url) use (&$capturedUrl): void {
                $capturedUrl = $url;
            });

        $rateLimiter = $this->createMock(ContactRateLimiterInterface::class);
        $rateLimiter->method('attempt')->willReturn(false);

        $mailer = $this->createMock(ContactMailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $controller = $this->makeController(rateLimiter: $rateLimiter, mailer: $mailer);
        $controller->handle($this->makePostData());

        self::assertNotNull($capturedUrl);
        self::assertStringContainsString('error=rate_limit', $capturedUrl);
    }

    /**
     * Vérifie que handle() redirige avec errors=name si la validation échoue.
     */
    public function testRedirectsOnValidationError(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('home_url')->justReturn('https://example.com/');

        $capturedUrl = null;
        Functions\expect('wp_safe_redirect')
            ->once()
            ->andReturnUsing(static function (string $url) use (&$capturedUrl): void {
                $capturedUrl = $url;
            });

        $rateLimiter = $this->createMock(ContactRateLimiterInterface::class);
        $rateLimiter->method('attempt')->willReturn(true);

        $model = $this->createMock(ContactFormModelInterface::class);
        $model->method('validate')->willReturn(ContactValidationResult::failed(['name' => 'name_invalid']));

        $mailer = $this->createMock(ContactMailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $controller = $this->makeController(model: $model, rateLimiter: $rateLimiter, mailer: $mailer);
        $controller->handle($this->makePostData());

        self::assertNotNull($capturedUrl);
        self::assertStringContainsString('errors=name', $capturedUrl);
    }

    /**
     * Vérifie que handle() envoie l'e-mail en cas de succès.
     */
    public function testSendsEmailOnSuccess(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('home_url')->justReturn('https://example.com/');
        Functions\when('get_option')->justReturn('to@x.com');
        Functions\when('get_bloginfo')->justReturn('admin@x.com');
        Functions\when('wp_safe_redirect')->justReturn();

        $rateLimiter = $this->createMock(ContactRateLimiterInterface::class);
        $rateLimiter->method('attempt')->willReturn(true);

        $submission = new ContactSubmission('Alice', 'alice@example.com', null, 'Message test', '', 1699999997, '127.0.0.1');

        $model = $this->createMock(ContactFormModelInterface::class);
        $model->method('validate')->willReturn(ContactValidationResult::ok());
        $model->method('sanitize')->willReturn($submission);

        $mailer = $this->createMock(ContactMailerInterface::class);
        $mailer->expects(self::once())->method('send');
        $mailer->expects(self::never())->method('sendAutoReply');

        $log = $this->createMock(ContactLogModelInterface::class);
        $log->expects(self::never())->method('log');

        $controller = $this->makeController(model: $model, rateLimiter: $rateLimiter, mailer: $mailer, log: $log);
        $controller->handle($this->makePostData());
    }

    /**
     * Vérifie que handle() appelle sendAutoReply si l'option autoreply est activée.
     */
    public function testCallsAutoReplyWhenEnabled(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('home_url')->justReturn('https://example.com/');
        Functions\when('get_bloginfo')->justReturn('admin@x.com');
        Functions\when('wp_safe_redirect')->justReturn();

        Functions\when('get_option')->alias(static function (string $key, mixed $default = false): mixed {
            return match ($key) {
                'oli_contact_email' => 'to@x.com',
                'oli_contact_autoreply' => '1',
                'oli_contact_autoreply_body' => 'Merci !',
                default => $default,
            };
        });

        $rateLimiter = $this->createMock(ContactRateLimiterInterface::class);
        $rateLimiter->method('attempt')->willReturn(true);

        $submission = new ContactSubmission('Alice', 'alice@example.com', null, 'Message test', '', 1699999997, '127.0.0.1');

        $model = $this->createMock(ContactFormModelInterface::class);
        $model->method('validate')->willReturn(ContactValidationResult::ok());
        $model->method('sanitize')->willReturn($submission);

        $mailer = $this->createMock(ContactMailerInterface::class);
        $mailer->expects(self::once())->method('send');
        $mailer->expects(self::once())->method('sendAutoReply');

        $log = $this->createMock(ContactLogModelInterface::class);
        $log->expects(self::never())->method('log');

        $controller = $this->makeController(model: $model, rateLimiter: $rateLimiter, mailer: $mailer, log: $log);
        $controller->handle($this->makePostData());
    }

    /**
     * Vérifie que handle() journalise la soumission si l'option logging est activée.
     */
    public function testLogsWhenEnabled(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('home_url')->justReturn('https://example.com/');
        Functions\when('get_bloginfo')->justReturn('admin@x.com');
        Functions\when('wp_safe_redirect')->justReturn();

        Functions\when('get_option')->alias(static function (string $key, mixed $default = false): mixed {
            return match ($key) {
                'oli_contact_email' => 'to@x.com',
                'oli_contact_logging' => '1',
                default => $default,
            };
        });

        $rateLimiter = $this->createMock(ContactRateLimiterInterface::class);
        $rateLimiter->method('attempt')->willReturn(true);

        $submission = new ContactSubmission('Alice', 'alice@example.com', null, 'Message test', '', 1699999997, '127.0.0.1');

        $model = $this->createMock(ContactFormModelInterface::class);
        $model->method('validate')->willReturn(ContactValidationResult::ok());
        $model->method('sanitize')->willReturn($submission);

        $mailer = $this->createMock(ContactMailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $log = $this->createMock(ContactLogModelInterface::class);
        $log->expects(self::once())->method('log');

        $controller = $this->makeController(model: $model, rateLimiter: $rateLimiter, mailer: $mailer, log: $log);
        $controller->handle($this->makePostData());
    }

    /**
     * Construit les données POST de base pour les tests.
     *
     * @param array<string, mixed> $overrides Valeurs à surcharger.
     *
     * @return array<string, mixed>
     */
    private function makePostData(array $overrides = []): array
    {
        return array_merge([
            '_oli_nonce' => 'valid-nonce',
            'name' => 'Alice Dupont',
            'email' => 'alice@example.com',
            'subject' => 'Question',
            'message' => 'Mon message de test suffisamment long.',
            'honeypot' => '',
            '_oli_timestamp' => 1699999997,
            '_oli_redirect' => 'https://example.com/contact',
        ], $overrides);
    }

    /**
     * Construit un contrôleur avec des mocks pour tous ses collaborateurs.
     */
    private function makeController(
        ?ContactFormModelInterface $model = null,
        ?ContactRateLimiterInterface $rateLimiter = null,
        ?ContactMailerInterface $mailer = null,
        ?ContactLogModelInterface $log = null,
    ): ContactFormController {
        return new ContactFormController(
            model: $model ?? $this->createMock(ContactFormModelInterface::class),
            rateLimiter: $rateLimiter ?? $this->createMock(ContactRateLimiterInterface::class),
            mailer: $mailer ?? $this->createMock(ContactMailerInterface::class),
            log: $log ?? $this->createMock(ContactLogModelInterface::class),
        );
    }
}
