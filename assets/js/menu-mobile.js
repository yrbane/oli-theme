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

    // Le drawer est rendu dans `.site-header`, mais celui-ci a un
    // `backdrop-filter: blur(20px)` qui crée un containing block pour
    // les descendants `position: fixed` (CSS spec). Conséquence : le
    // drawer se positionne par rapport au header (qui est en bas du
    // hero sur la home), pas par rapport au viewport. On le déplace
    // donc directement sous `<body>` au boot pour rester en pleine
    // hauteur viewport.
    if (drawer.parentElement !== document.body) {
        document.body.appendChild(drawer);
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

    // Accordéon des items à sous-menu : on intercepte le tap sur le lien
    // parent pour ouvrir/fermer le sous-menu plutôt que de naviguer
    // directement. Premier tap = ouvre, second tap = navigue (URL réelle).
    // Les autres items ouverts se ferment (un seul ouvert à la fois).
    drawer.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }
        const parentLink = event.target.closest('.nav__item--has-children > .nav__link');
        if (parentLink) {
            const item = parentLink.parentElement;
            if (item && !item.classList.contains('is-open')) {
                event.preventDefault();
                drawer.querySelectorAll('.nav__item--has-children.is-open').forEach((el) => {
                    if (el !== item) {
                        el.classList.remove('is-open');
                    }
                });
                item.classList.add('is-open');
                return;
            }
        }
        // Si on clique sur un lien enfant (pas un parent à sous-menu),
        // on ferme le drawer après la navigation.
        const link = event.target.closest('a[href]');
        if (link && !link.matches('.nav__item--has-children > .nav__link')) {
            setOpen(false);
        }
    });
}
