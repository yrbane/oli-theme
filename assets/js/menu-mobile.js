/**
 * Menu mobile (drawer) — gestion ouverture/fermeture + clavier.
 * Sans dépendance, ES module.
 */
export function initMobileMenu() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const drawer = document.querySelector('[data-nav-mobile]');
    if (!toggle || !drawer) {
        return;
    }

    const setOpen = (isOpen) => {
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        drawer.hidden = !isOpen;
        document.body.classList.toggle('has-mobile-menu-open', isOpen);
    };

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        setOpen(!isOpen);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && drawer.hidden === false) {
            setOpen(false);
            toggle.focus();
        }
    });

    // Clique en dehors du drawer (sur le backdrop sombre) → ferme.
    // Le backdrop est rendu en pseudo-élément `body::after` donc non
    // cliquable directement ; on écoute le `click` sur document et on
    // ferme dès que la cible n'est ni le drawer ni le bouton.
    document.addEventListener('click', (event) => {
        if (drawer.hidden) {
            return;
        }
        const target = event.target;
        if (target instanceof Node && !drawer.contains(target) && !toggle.contains(target)) {
            setOpen(false);
        }
    });

    // Fermeture automatique quand on suit un lien interne du menu.
    drawer.addEventListener('click', (event) => {
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
        if (link) {
            setOpen(false);
        }
    });
}
