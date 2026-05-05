<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Expéditeur d'e-mails pour le formulaire de contact.
 *
 * Utilise wp_mail() pour envoyer le message à l'administrateur ainsi que
 * la réponse automatique à l'expéditeur. Le header Reply-To est positionné
 * sur l'adresse de l'expéditeur pour faciliter la réponse directe.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
final class ContactMailer implements ContactMailerInterface
{
    /**
     * Envoie le message de contact à l'adresse de destination.
     *
     * @param ContactSubmission $submission Données de la soumission.
     * @param string $to Adresse e-mail du destinataire.
     */
    public function send(ContactSubmission $submission, string $to): bool
    {
        $subject = '[Contact] ' . ($submission->subject ?? 'Nouveau message');
        $body = \sprintf(
            "De : %s <%s>\nIP : %s\nDate : %s\n\n%s",
            $submission->name,
            $submission->email,
            $submission->ip,
            gmdate('Y-m-d H:i:s', $submission->timestamp),
            $submission->message,
        );
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            \sprintf('Reply-To: %s <%s>', $submission->name, $submission->email),
        ];

        return wp_mail($to, $subject, $body, $headers);
    }

    /**
     * Envoie une réponse automatique à l'expéditeur.
     *
     * @param ContactSubmission $submission Données de la soumission.
     * @param string $body Corps du message de confirmation.
     */
    public function sendAutoReply(ContactSubmission $submission, string $body): bool
    {
        $subject = 'Confirmation de votre message';
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        return wp_mail($submission->email, $subject, $body, $headers);
    }
}
