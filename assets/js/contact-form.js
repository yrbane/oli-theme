/**
 * Formulaire de contact — progressive enhancement.
 *
 * Sans JS : POST natif vers admin-post.php (action `oli_contact`).
 * Avec JS : validation client améliorée + UX (auto-focus sur erreur, désactivation du bouton).
 */
export function initContactForm() {
    const root = document.querySelector('[data-contact-form]');
    if (!root) {
        return;
    }
    const form = root.querySelector('.contact-form__form');
    if (!form) {
        return;
    }

    // Auto-focus sur le premier champ en erreur côté serveur.
    const firstError = root.querySelector('.contact-form__field--error input, .contact-form__field--error textarea');
    if (firstError instanceof HTMLElement) {
        firstError.focus();
    }

    // UX : désactive le bouton submit pendant l'envoi pour éviter les double-clicks.
    form.addEventListener('submit', () => {
        const submit = form.querySelector('.contact-form__submit');
        if (submit instanceof HTMLButtonElement) {
            submit.disabled = true;
            submit.textContent = 'Envoi en cours…';
        }
    });
}
