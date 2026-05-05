<?php

declare(strict_types=1);

namespace OliTheme\Contact;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolverInterface;

/**
 * Module Contact : enregistre le CPT oli_contact_log et tous les services
 * associés (modèle, limiteur, mailer, journal, contrôleur, shortcode)
 * dans le container, puis branche les hooks WordPress.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
final class ContactModule implements ModuleInterface
{
    /**
     * @param Container $container Container de services du thème.
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Enregistre les services Contact et branche les hooks WordPress.
     */
    public function register(): void
    {
        $container = $this->container;

        if (! $container->has(ContactFormModel::class)) {
            $container->factory(
                ContactFormModel::class,
                static fn (): ContactFormModel => new ContactFormModel(),
            );
        }

        if (! $container->has(ContactFormModelInterface::class)) {
            $container->factory(
                ContactFormModelInterface::class,
                static fn (Container $c): ContactFormModelInterface => $c->get(ContactFormModel::class),
            );
        }

        if (! $container->has(ContactRateLimiter::class)) {
            $container->factory(
                ContactRateLimiter::class,
                static fn (): ContactRateLimiter => new ContactRateLimiter(),
            );
        }

        if (! $container->has(ContactRateLimiterInterface::class)) {
            $container->factory(
                ContactRateLimiterInterface::class,
                static fn (Container $c): ContactRateLimiterInterface => $c->get(ContactRateLimiter::class),
            );
        }

        if (! $container->has(ContactMailer::class)) {
            $container->factory(
                ContactMailer::class,
                static fn (): ContactMailer => new ContactMailer(),
            );
        }

        if (! $container->has(ContactMailerInterface::class)) {
            $container->factory(
                ContactMailerInterface::class,
                static fn (Container $c): ContactMailerInterface => $c->get(ContactMailer::class),
            );
        }

        if (! $container->has(ContactLogCpt::class)) {
            $container->factory(
                ContactLogCpt::class,
                static fn (): ContactLogCpt => new ContactLogCpt(),
            );
        }

        if (! $container->has(ContactLogModel::class)) {
            $container->factory(
                ContactLogModel::class,
                static fn (): ContactLogModel => new ContactLogModel(),
            );
        }

        if (! $container->has(ContactLogModelInterface::class)) {
            $container->factory(
                ContactLogModelInterface::class,
                static fn (Container $c): ContactLogModelInterface => $c->get(ContactLogModel::class),
            );
        }

        if (! $container->has(ContactFormController::class)) {
            $container->factory(
                ContactFormController::class,
                static fn (Container $c): ContactFormController => new ContactFormController(
                    $c->get(ContactFormModelInterface::class),
                    $c->get(ContactRateLimiterInterface::class),
                    $c->get(ContactMailerInterface::class),
                    $c->get(ContactLogModelInterface::class),
                ),
            );
        }

        if (! $container->has(ContactFormControllerInterface::class)) {
            $container->factory(
                ContactFormControllerInterface::class,
                static fn (Container $c): ContactFormControllerInterface => $c->get(ContactFormController::class),
            );
        }

        if (! $container->has(ContactShortcode::class)) {
            $container->factory(
                ContactShortcode::class,
                static fn (Container $c): ContactShortcode => new ContactShortcode(
                    $c->get(RendererInterface::class),
                    $c->get(LanguageResolverInterface::class),
                ),
            );
        }

        add_action('init', function (): void {
            $this->container->get(ContactLogCpt::class)->register();
        });

        add_action('admin_post_oli_contact', function (): void {
            $this->container->get(ContactFormControllerInterface::class)->handle($_POST);
        });

        add_action('admin_post_nopriv_oli_contact', function (): void {
            $this->container->get(ContactFormControllerInterface::class)->handle($_POST);
        });

        add_action('init', function (): void {
            add_shortcode('oli_contact_form', function (array|string $atts): string {
                // @phpstan-ignore-next-line
                return $this->container->get(ContactShortcode::class)->render(\is_array($atts) ? $atts : []);
            });
        });
    }
}
