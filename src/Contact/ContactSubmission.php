<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Représentation immuable d'une soumission du formulaire de contact.
 *
 * Ce DTO capture l'ensemble des champs soumis par l'utilisateur ainsi que
 * les métadonnées de la requête (IP, horodatage) nécessaires à la validation
 * et à la traçabilité.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
final readonly class ContactSubmission
{
    /**
     * @param string $name Nom de l'expéditeur.
     * @param string $email Adresse e-mail de l'expéditeur.
     * @param string|null $subject Objet du message (optionnel).
     * @param string $message Corps du message.
     * @param string $honeypot Champ anti-spam (doit être vide).
     * @param int $timestamp Horodatage Unix de la soumission.
     * @param string $ip Adresse IP de l'expéditeur.
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $subject,
        public string $message,
        public string $honeypot,
        public int $timestamp,
        public string $ip,
    ) {
    }
}
