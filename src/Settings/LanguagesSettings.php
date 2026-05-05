<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * DTO immuable pour les paramètres multilingues du thème.
 *
 * Définit les langues activées, la langue par défaut et le comportement
 * de repli lorsqu'une traduction est absente.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final readonly class LanguagesSettings
{
    /** Rediriger vers la page d'accueil si la traduction est absente. */
    public const FALLBACK_HOME = 'home';

    /** Afficher le contenu source (langue originale) si la traduction est absente. */
    public const FALLBACK_SHOW_SOURCE = 'show_source';

    /** Afficher un message d'erreur si la traduction est absente. */
    public const FALLBACK_MESSAGE = 'message';

    /**
     * @param string[] $enabled Codes ISO des langues activées.
     * @param string $default Code ISO de la langue par défaut.
     * @param string $fallbackBehavior Comportement de repli (voir constantes FALLBACK_*).
     */
    public function __construct(
        public array $enabled,
        public string $default,
        public string $fallbackBehavior,
    ) {
    }
}
