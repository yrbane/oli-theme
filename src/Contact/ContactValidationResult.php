<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Résultat de la validation d'une soumission de formulaire de contact.
 *
 * Ce DTO immuable transporte le statut de validation ainsi que la carte
 * des erreurs éventuelles (champ → clé d'erreur) pour permettre au contrôleur
 * de générer des messages localisés.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
final readonly class ContactValidationResult
{
    /**
     * @param bool                  $valid  Indique si la soumission est valide.
     * @param array<string, string> $errors Carte champ → clé d'erreur.
     */
    public function __construct(
        public bool $valid,
        public array $errors,
    ) {}

    /**
     * Construit un résultat de validation réussie.
     */
    public static function ok(): self
    {
        return new self(true, []);
    }

    /**
     * Construit un résultat de validation échouée avec les erreurs données.
     *
     * @param array<string, string> $errors Carte champ → clé d'erreur.
     */
    public static function failed(array $errors): self
    {
        return new self(false, $errors);
    }
}
