/**
 * Point d'entrée ES module — auto-init des composants présents dans le DOM.
 */
import { initMobileMenu } from './menu-mobile.js';
import { initCarousel } from './carousel.js';
import { initContactForm } from './contact-form.js';

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-nav-toggle]')) {
        initMobileMenu();
    }
    if (document.querySelector('[data-carousel]')) {
        initCarousel();
    }
    if (document.querySelector('[data-contact-form]')) {
        initContactForm();
    }
});
