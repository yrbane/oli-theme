<?php

declare(strict_types=1);

namespace OliTheme\I18n;

use RuntimeException;

/**
 * Registre des langues activées dans le thème.
 *
 * Lit l'option WordPress `oli_languages` (renseignée via Settings > Identité du site).
 * Si l'option est absente, utilise un jeu par défaut (FR/EN/IT/ES) avec FR par défaut.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final class LanguageRegistry
{
    private const OPTION_KEY = 'oli_languages';

    /**
     * Catalogue complet des langues connues du thème.
     *
     * @var array<string, Language>
     */
    private array $catalogue;

    /**
     * Langues effectivement activées sur ce site.
     *
     * @var array<string, Language>
     */
    private array $enabled;

    private Language $default;

    public function __construct()
    {
        $this->catalogue = self::buildCatalogue();
        [$this->enabled, $this->default] = $this->resolveFromOption();
    }

    /**
     * Toutes les langues activées (ordre stable).
     *
     * @return array<int, Language>
     */
    public function all(): array
    {
        return array_values($this->enabled);
    }

    public function default(): Language
    {
        return $this->default;
    }

    public function get(string $code): ?Language
    {
        return $this->enabled[$code] ?? null;
    }

    public function isEnabled(string $code): bool
    {
        return isset($this->enabled[$code]);
    }

    /**
     * Construit le catalogue par défaut des langues supportées par le thème.
     *
     * @return array<string, Language>
     */
    private static function buildCatalogue(): array
    {
        return [
            'fr' => new Language('fr', 'French', 'Français', '🇫🇷', 'fr_FR'),
            'en' => new Language('en', 'English', 'English', '🇬🇧', 'en_US'),
            'it' => new Language('it', 'Italian', 'Italiano', '🇮🇹', 'it_IT'),
            'es' => new Language('es', 'Spanish', 'Español', '🇪🇸', 'es_ES'),
        ];
    }

    /**
     * Résout les langues activées et la langue par défaut depuis l'option WP.
     *
     * @return array{0: array<string, Language>, 1: Language}
     */
    private function resolveFromOption(): array
    {
        /** @var mixed $stored */
        $stored = get_option(self::OPTION_KEY, false);

        if (!\is_array($stored)) {
            return [$this->catalogue, $this->catalogue['fr']];
        }

        $enabledCodes = \is_array($stored['enabled'] ?? null) ? $stored['enabled'] : [];
        $defaultCode = \is_string($stored['default'] ?? null) ? $stored['default'] : null;

        $enabled = [];
        foreach ($enabledCodes as $code) {
            if (\is_string($code) && isset($this->catalogue[$code])) {
                $enabled[$code] = $this->catalogue[$code];
            }
        }

        if ($enabled === []) {
            throw new RuntimeException('Aucune langue activée dans la configuration.');
        }

        $default = ($defaultCode !== null && isset($enabled[$defaultCode]))
            ? $enabled[$defaultCode]
            : reset($enabled);

        \assert($default instanceof Language);

        return [$enabled, $default];
    }
}
