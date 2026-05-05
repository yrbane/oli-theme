/**
 * Point d'entrée ES module — auto-init des composants présents dans le DOM.
 */
import { initMobileMenu } from './menu-mobile.js';

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-nav-toggle]')) {
        initMobileMenu();
    }
});
