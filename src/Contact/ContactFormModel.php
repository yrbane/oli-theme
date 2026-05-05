<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Modèle de formulaire de contact : validation et sanitisation.
 *
 * Implémente les règles métier de validation (longueur, format e-mail,
 * honeypot, délai anti-flood) et la sanitisation WordPress des données
 * soumises par l'utilisateur.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
final class ContactFormModel implements ContactFormModelInterface
{
    /**
     * @param callable(): int $clock Fournisseur de l'horodatage courant (pour la testabilité).
     */
    public function __construct(
        private $clock = 'time',
    ) {}

    /**
     * Valide une soumission et retourne le résultat de validation.
     *
     * Règles :
     * - nom : 2-100 caractères.
     * - email : doit passer is_email().
     * - sujet : optionnel ; si renseigné, 1-150 caractères.
     * - message : 10-5000 caractères.
     * - honeypot : doit être vide.
     * - horodatage : au moins 3 secondes avant la soumission.
     *
     * @param ContactSubmission $submission Soumission à valider.
     */
    public function validate(ContactSubmission $submission): ContactValidationResult
    {
        $errors = [];

        $name = trim($submission->name);
        if (\mb_strlen($name) < 2 || \mb_strlen($name) > 100) {
            $errors['name'] = 'name_invalid';
        }

        if (! is_email($submission->email)) {
            $errors['email'] = 'email_invalid';
        }

        if ($submission->subject !== null) {
            $sLen = \mb_strlen($submission->subject);
            if ($sLen < 1 || $sLen > 150) {
                $errors['subject'] = 'subject_too_long';
            }
        }

        $mLen = \mb_strlen(trim($submission->message));
        if ($mLen < 10 || $mLen > 5000) {
            $errors['message'] = 'message_invalid';
        }

        if (trim($submission->honeypot) !== '') {
            $errors['honeypot'] = 'spam_detected';
        }

        $now = ($this->clock)();
        if ($now - $submission->timestamp < 3) {
            $errors['timestamp'] = 'too_fast';
        }

        return $errors === [] ? ContactValidationResult::ok() : ContactValidationResult::failed($errors);
    }

    /**
     * Sanitise une soumission et retourne une nouvelle instance nettoyée.
     *
     * @param ContactSubmission $submission Soumission à sanitiser.
     */
    public function sanitize(ContactSubmission $submission): ContactSubmission
    {
        return new ContactSubmission(
            name: sanitize_text_field($submission->name),
            email: sanitize_email($submission->email),
            subject: $submission->subject !== null ? sanitize_text_field($submission->subject) : null,
            message: sanitize_textarea_field($submission->message),
            honeypot: $submission->honeypot,
            timestamp: $submission->timestamp,
            ip: $submission->ip,
        );
    }
}
