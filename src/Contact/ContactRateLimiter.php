<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Limiteur de débit basé sur les transients WordPress.
 *
 * Autorise au maximum 3 soumissions par IP sur une fenêtre glissante
 * de 15 minutes. Chaque dépassement est rejeté silencieusement.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
final class ContactRateLimiter implements ContactRateLimiterInterface
{
    /** Nombre maximum de tentatives autorisées par fenêtre. */
    private const MAX_ATTEMPTS = 3;

    /** Durée de la fenêtre glissante en secondes (15 minutes). */
    private const WINDOW_SECONDS = 900;

    /**
     * Enregistre une tentative pour l'IP donnée et indique si elle est autorisée.
     *
     * @param string $ip Adresse IP de l'expéditeur.
     */
    public function attempt(string $ip): bool
    {
        $key = 'oli_contact_rate_' . \md5($ip);
        $count = (int) get_transient($key);

        if ($count >= self::MAX_ATTEMPTS) {
            return false;
        }

        set_transient($key, $count + 1, self::WINDOW_SECONDS);

        return true;
    }
}
