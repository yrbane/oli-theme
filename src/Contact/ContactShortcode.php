<?php

declare(strict_types=1);

namespace OliTheme\Contact;

use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolverInterface;

/**
 * Shortcode [oli_contact_form] affichant le formulaire de contact.
 *
 * Récupère les erreurs et le statut de succès depuis les paramètres GET
 * de l'URL de retour, et transmet les variables nécessaires au template.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
final class ContactShortcode
{
    /**
     * @param RendererInterface         $renderer Moteur de rendu de templates.
     * @param LanguageResolverInterface $resolver Résolveur de langue courante.
     */
    public function __construct(
        private readonly RendererInterface $renderer,
        private readonly LanguageResolverInterface $resolver,
    ) {}

    /**
     * Rend le formulaire de contact et retourne le HTML produit.
     *
     * @param array<string, mixed> $atts Attributs du shortcode (non utilisés actuellement).
     */
    public function render(array $atts = []): string
    {
        $errors = [];

        if (isset($_GET['errors']) && \is_string($_GET['errors'])) {
            foreach (\explode(',', sanitize_text_field((string) $_GET['errors'])) as $key) {
                $key = trim($key);

                if ($key !== '') {
                    $errors[$key] = $key;
                }
            }
        }

        return $this->renderer->render('partials/contact-form.html', [
            'nonce' => wp_create_nonce('oli_contact'),
            'redirect' => (string) (\is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/'),
            'lang' => $this->resolver->current(),
            'errors' => $errors,
            'success' => (($_GET['contact'] ?? null) === 'ok'),
            'timestamp' => time(),
            'actionUrl' => admin_url('admin-post.php'),
        ]);
    }
}
