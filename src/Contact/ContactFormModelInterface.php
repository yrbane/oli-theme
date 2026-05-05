<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Contrat du modèle de formulaire de contact.
 *
 * Définit les opérations de validation et de sanitisation d'une soumission
 * de formulaire de contact.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
interface ContactFormModelInterface
{
    /**
     * Valide une soumission et retourne le résultat de validation.
     *
     * @param ContactSubmission $submission Soumission à valider.
     */
    public function validate(ContactSubmission $submission): ContactValidationResult;

    /**
     * Sanitise une soumission et retourne une nouvelle instance nettoyée.
     *
     * @param ContactSubmission $submission Soumission à sanitiser.
     */
    public function sanitize(ContactSubmission $submission): ContactSubmission;
}
