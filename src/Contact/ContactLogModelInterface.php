<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Contrat d'enregistrement des soumissions de contact dans un log WordPress.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
interface ContactLogModelInterface
{
    /**
     * Enregistre une soumission dans le CPT oli_contact_log.
     *
     * Retourne l'identifiant du post créé, ou 0 en cas d'échec.
     *
     * @param ContactSubmission $submission Soumission à journaliser.
     */
    public function log(ContactSubmission $submission): int;
}
