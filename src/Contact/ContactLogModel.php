<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Modèle de journalisation des soumissions de contact.
 *
 * Crée un post privé de type oli_contact_log et y associe les métadonnées
 * de la soumission (nom, e-mail, IP, horodatage) pour traçabilité.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
final class ContactLogModel implements ContactLogModelInterface
{
    /**
     * Enregistre une soumission dans le CPT oli_contact_log.
     *
     * Retourne l'identifiant du post créé, ou 0 en cas d'échec.
     *
     * @param ContactSubmission $submission Soumission à journaliser.
     */
    public function log(ContactSubmission $submission): int
    {
        $postId = wp_insert_post([
            'post_type' => 'oli_contact_log',
            'post_status' => 'private',
            'post_title' => $submission->subject ?? 'Sans sujet',
            'post_content' => $submission->message,
        ]);

        if (! \is_int($postId) || $postId <= 0) {
            return 0;
        }

        update_post_meta($postId, '_oli_contact_name', $submission->name);
        update_post_meta($postId, '_oli_contact_email', $submission->email);
        update_post_meta($postId, '_oli_contact_ip', $submission->ip);
        update_post_meta($postId, '_oli_contact_timestamp', $submission->timestamp);

        return $postId;
    }
}
