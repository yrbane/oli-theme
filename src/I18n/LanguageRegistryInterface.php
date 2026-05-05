<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Contrat du registre des langues activées dans le thème.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
interface LanguageRegistryInterface
{
    /**
     * Toutes les langues activées (ordre stable).
     *
     * @return array<int, Language>
     */
    public function all(): array;

    /**
     * Langue par défaut du site.
     */
    public function default(): Language;

    /**
     * Récupère une langue par son code ISO 639-1.
     */
    public function get(string $code): ?Language;

    /**
     * Indique si le code de langue est activé.
     */
    public function isEnabled(string $code): bool;
}
