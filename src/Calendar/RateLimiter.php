<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

/**
 * Limiteur de fréquence par identifiant (typiquement hash d'IP).
 *
 * Implémenté via les transients WordPress (TTL natif). Pour chaque clé,
 * un compteur expire automatiquement après la fenêtre configurée.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final class RateLimiter
{
    private const PREFIX = 'oli_calendar_rl_';

    public function __construct(
        private readonly int $maxAttempts = 5,
        private readonly int $windowSeconds = 3600,
    ) {
    }

    /**
     * Tente de consommer 1 jeton. Retourne true si autorisé, false si bloqué.
     */
    public function tryConsume(string $identifier): bool
    {
        if (!\function_exists('get_transient') || !\function_exists('set_transient')) {
            // En CLI / hors WP : toujours autoriser (les tests stubent ces fonctions).
            return true;
        }
        $key   = self::PREFIX . hash('sha256', $identifier);
        $count = (int) get_transient($key);
        if ($count >= $this->maxAttempts) {
            return false;
        }
        set_transient($key, $count + 1, $this->windowSeconds);

        return true;
    }
}
