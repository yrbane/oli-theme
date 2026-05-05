/**
 * Carousel d'accueil — autoplay, swipe, clavier, reduced-motion.
 * Sans dépendance, ES module.
 *
 * Sans JS, les slides sont rendus en flex et scrollables nativement.
 * Avec JS, on prend le relais : transform: translateX et contrôles visibles.
 */
export function initCarousel() {
    const root = document.querySelector('[data-carousel]');
    if (!root) {
        return;
    }

    const list = root.querySelector('.carousel__list');
    const slides = list ? Array.from(list.querySelectorAll('.carousel__slide')) : [];
    if (slides.length === 0) {
        return;
    }

    const controls = root.querySelector('[data-carousel-controls]');
    const prevBtn = root.querySelector('[data-carousel-prev]');
    const nextBtn = root.querySelector('[data-carousel-next]');

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const autoplay = root.dataset.autoplay === 'true' && !reducedMotion;
    const interval = parseInt(root.dataset.interval || '5000', 10);
    const loop = root.dataset.loop === 'true';

    let current = 0;
    let timer = null;

    const goTo = (index) => {
        if (slides.length === 0) {
            return;
        }
        if (index < 0) {
            current = loop ? slides.length - 1 : 0;
        } else if (index >= slides.length) {
            current = loop ? 0 : slides.length - 1;
        } else {
            current = index;
        }
        list.style.transform = `translateX(-${current * 100}%)`;
    };

    const next = () => goTo(current + 1);
    const prev = () => goTo(current - 1);

    const stop = () => {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }
    };

    const start = () => {
        if (!autoplay || timer !== null) {
            return;
        }
        timer = window.setInterval(next, interval);
    };

    // Active le mode JS (CSS bascule le scroll natif au transform contrôlé).
    root.setAttribute('data-carousel-active', '');
    if (controls) {
        controls.removeAttribute('hidden');
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => { stop(); prev(); start(); });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => { stop(); next(); start(); });
    }

    // Pause au hover/focus, reprise quand on quitte.
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);

    // Pause quand l'onglet n'est pas visible.
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stop();
        } else {
            start();
        }
    });

    // Clavier : flèches gauche/droite quand le focus est dans le carousel.
    root.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            stop(); prev(); start();
        } else if (event.key === 'ArrowRight') {
            stop(); next(); start();
        }
    });

    // Swipe tactile (Pointer Events).
    let pointerStartX = null;
    root.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse') {
            return;
        }
        pointerStartX = event.clientX;
        stop();
    });
    root.addEventListener('pointerup', (event) => {
        if (pointerStartX === null) {
            return;
        }
        const delta = event.clientX - pointerStartX;
        pointerStartX = null;
        if (Math.abs(delta) > 40) {
            if (delta < 0) { next(); } else { prev(); }
        }
        start();
    });

    goTo(0);
    start();
}
