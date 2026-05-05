<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Contrat d'envoi des e-mails liés au formulaire de contact.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
interface ContactMailerInterface
{
    /**
     * Envoie le message de contact à l'adresse de destination.
     *
     * @param ContactSubmission $submission Données de la soumission.
     * @param string            $to         Adresse e-mail du destinataire.
     */
    public function send(ContactSubmission $submission, string $to): bool;

    /**
     * Envoie une réponse automatique à l'expéditeur.
     *
     * @param ContactSubmission $submission Données de la soumission.
     * @param string            $body       Corps du message de confirmation.
     */
    public function sendAutoReply(ContactSubmission $submission, string $body): bool;
}
