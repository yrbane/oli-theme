<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Contact;

use Brain\Monkey;
use Brain\Monkey\Actions;
use OliTheme\Contact\ContactFormController;
use OliTheme\Contact\ContactFormControllerInterface;
use OliTheme\Contact\ContactFormModel;
use OliTheme\Contact\ContactFormModelInterface;
use OliTheme\Contact\ContactLogCpt;
use OliTheme\Contact\ContactLogModel;
use OliTheme\Contact\ContactLogModelInterface;
use OliTheme\Contact\ContactMailer;
use OliTheme\Contact\ContactMailerInterface;
use OliTheme\Contact\ContactModule;
use OliTheme\Contact\ContactRateLimiter;
use OliTheme\Contact\ContactRateLimiterInterface;
use OliTheme\Contact\ContactShortcode;
use OliTheme\Container;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolverInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de ContactModule.
 *
 * @package OliTheme\Tests\Unit\Contact
 *
 * @since 1.0.0
 */
final class ContactModuleTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->container = new Container();
        $this->container->set(RendererInterface::class, $this->createMock(RendererInterface::class));
        $this->container->set(LanguageResolverInterface::class, $this->createMock(LanguageResolverInterface::class));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Vérifie que register() enregistre tous les services dans le container.
     */
    public function testRegisterBindsAllServices(): void
    {
        $module = new ContactModule($this->container);
        $module->register();

        self::assertTrue($this->container->has(ContactFormModel::class));
        self::assertTrue($this->container->has(ContactFormModelInterface::class));
        self::assertTrue($this->container->has(ContactRateLimiter::class));
        self::assertTrue($this->container->has(ContactRateLimiterInterface::class));
        self::assertTrue($this->container->has(ContactMailer::class));
        self::assertTrue($this->container->has(ContactMailerInterface::class));
        self::assertTrue($this->container->has(ContactLogCpt::class));
        self::assertTrue($this->container->has(ContactLogModel::class));
        self::assertTrue($this->container->has(ContactLogModelInterface::class));
        self::assertTrue($this->container->has(ContactFormController::class));
        self::assertTrue($this->container->has(ContactFormControllerInterface::class));
        self::assertTrue($this->container->has(ContactShortcode::class));

        self::assertInstanceOf(ContactFormModel::class, $this->container->get(ContactFormModel::class));
        self::assertInstanceOf(ContactFormModelInterface::class, $this->container->get(ContactFormModelInterface::class));
        self::assertInstanceOf(ContactRateLimiter::class, $this->container->get(ContactRateLimiter::class));
        self::assertInstanceOf(ContactRateLimiterInterface::class, $this->container->get(ContactRateLimiterInterface::class));
        self::assertInstanceOf(ContactMailer::class, $this->container->get(ContactMailer::class));
        self::assertInstanceOf(ContactMailerInterface::class, $this->container->get(ContactMailerInterface::class));
        self::assertInstanceOf(ContactLogCpt::class, $this->container->get(ContactLogCpt::class));
        self::assertInstanceOf(ContactLogModel::class, $this->container->get(ContactLogModel::class));
        self::assertInstanceOf(ContactLogModelInterface::class, $this->container->get(ContactLogModelInterface::class));
        self::assertInstanceOf(ContactFormController::class, $this->container->get(ContactFormController::class));
        self::assertInstanceOf(ContactFormControllerInterface::class, $this->container->get(ContactFormControllerInterface::class));
        self::assertInstanceOf(ContactShortcode::class, $this->container->get(ContactShortcode::class));
    }

    /**
     * Vérifie que register() branche les hooks WordPress attendus.
     */
    public function testRegisterHooksWordPressActions(): void
    {
        Actions\expectAdded('init')->twice();
        Actions\expectAdded('admin_post_oli_contact')->once();
        Actions\expectAdded('admin_post_nopriv_oli_contact')->once();

        $module = new ContactModule($this->container);
        $module->register();

        $this->addToAssertionCount(1);
    }
}
