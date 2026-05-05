<?php

declare(strict_types=1);

namespace OliTheme\Contact;

/**
 * Contrat du contrôleur de formulaire de contact.
 *
 * @package OliTheme\Contact
 *
 * @since 1.0.0
 */
interface ContactFormControllerInterface
{
    /**
     * Traite la soumission du formulaire de contact.
     *
     * @param array<string, mixed> $postData Données brutes de la requête POST.
     */
    public function handle(array $postData): void;
}
