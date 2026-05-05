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
}
