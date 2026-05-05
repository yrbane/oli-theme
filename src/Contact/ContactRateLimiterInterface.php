<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Contrat du limiteur de débit pour le formulaire de contact.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
interface ContactRateLimiterInterface
{
    /**
     * Enregistre une tentative pour l'IP donnée et indique si elle est autorisée.
     *
     * Retourne true si la tentative est autorisée, false si la limite est atteinte.
     *
     * @param string $ip Adresse IP de l'expéditeur.
     */
    public function attempt(string $ip): bool;
}
