<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Contrôleur orchestrant le traitement sécurisé du formulaire de contact.
 *
 * Vérifie le nonce WordPress, applique le rate-limiting, valide et sanitise
 * la soumission, puis envoie l'e-mail, déclenche l'auto-réponse et journalise
 * selon la configuration. Redirige vers l'URL d'origine avec un paramètre de
 * statut.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
final class ContactFormController implements ContactFormControllerInterface
{
    /**
     * @param ContactFormModelInterface    $model       Modèle de validation/sanitisation.
     * @param ContactRateLimiterInterface  $rateLimiter Limiteur de débit par IP.
     * @param ContactMailerInterface       $mailer      Expéditeur d'e-mails.
     * @param ContactLogModelInterface     $log         Journaliseur de soumissions.
     */
    public function __construct(
        private readonly ContactFormModelInterface $model,
        private readonly ContactRateLimiterInterface $rateLimiter,
        private readonly ContactMailerInterface $mailer,
        private readonly ContactLogModelInterface $log,
    ) {}

    /**
     * Traite la soumission du formulaire de contact.
     *
     * @param array<string, mixed> $postData Données brutes de la requête POST.
     */
    public function handle(array $postData): void
    {
        $nonce = (string) ($postData['_oli_nonce'] ?? '');

        if (! wp_verify_nonce($nonce, 'oli_contact')) {
            wp_die(esc_html__('Vérification de sécurité échouée.', 'oli-theme'), 403);

            return;
        }

        $submission = new ContactSubmission(
            name: (string) ($postData['name'] ?? ''),
            email: (string) ($postData['email'] ?? ''),
            subject: isset($postData['subject']) && $postData['subject'] !== '' ? (string) $postData['subject'] : null,
            message: (string) ($postData['message'] ?? ''),
            honeypot: (string) ($postData['honeypot'] ?? ''),
            timestamp: (int) ($postData['_oli_timestamp'] ?? 0),
            ip: (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        );

        $redirect = (string) ($postData['_oli_redirect'] ?? home_url('/'));

        if (! $this->rateLimiter->attempt($submission->ip)) {
            $this->redirect($redirect, ['error' => 'rate_limit']);

            return;
        }

        $validation = $this->model->validate($submission);

        if (! $validation->valid) {
            $this->redirect($redirect, ['errors' => \implode(',', \array_keys($validation->errors))]);

            return;
        }

        $sanitized = $this->model->sanitize($submission);
        $to = (string) get_option('oli_contact_email', get_bloginfo('admin_email'));
        $this->mailer->send($sanitized, $to);

        if (get_option('oli_contact_autoreply') === '1') {
            $autoReplyBody = (string) get_option(
                'oli_contact_autoreply_body',
                'Merci pour votre message, nous vous répondrons rapidement.',
            );
            $this->mailer->sendAutoReply($sanitized, $autoReplyBody);
        }

        if (get_option('oli_contact_logging') === '1') {
            $this->log->log($sanitized);
        }

        $this->redirect($redirect, ['contact' => 'ok']);
    }

    /**
     * Redirige vers l'URL avec les paramètres de statut ajoutés.
     *
     * @param string               $url    URL de redirection.
     * @param array<string, string> $params Paramètres à ajouter.
     */
    private function redirect(string $url, array $params): void
    {
        $sep = \str_contains($url, '?') ? '&' : '?';
        $query = \http_build_query($params);
        wp_safe_redirect($url . $sep . $query);
    }
}
