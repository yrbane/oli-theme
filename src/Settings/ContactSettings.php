<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * DTO immuable pour les paramètres du formulaire de contact.
 *
 * Centralise l'adresse e-mail de destination, le corps de la réponse
 * automatique et les drapeaux d'activation des fonctionnalités.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final readonly class ContactSettings
{
    /**
     * @param string|null $email Adresse e-mail de réception des messages.
     * @param string|null $autoreplyBody Corps du message de réponse automatique.
     * @param bool $autoreplyEnabled Activer l'envoi de la réponse automatique.
     * @param bool $loggingEnabled Enregistrer les messages en base de données.
     */
    public function __construct(
        public ?string $email,
        public ?string $autoreplyBody,
        public bool $autoreplyEnabled,
        public bool $loggingEnabled,
    ) {
    }
}
