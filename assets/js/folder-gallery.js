/**
 * Lightbox des galeries de dossier insérées via le shortcode
 * `[oli_folder_gallery]` ou le bloc Gutenberg `oli/folder-gallery`.
 *
 * Chaque `<section class="oli-folder-gallery">` rendu sur la page est
 * activée : au clic sur une vignette, on ouvre une lightbox plein écran
 * naviguable au clavier (Escape ferme, flèches naviguent). Plusieurs
 * sections cohabitent sur la même page — chacune a sa propre liste de
 * photos.
 *
 * Réutilise les styles `.oli-lightbox` déjà fournis par
 * `assets/css/folder-gallery.css` (eux-mêmes alignés sur le lightbox de
 * la galerie principale `.gallery--photos`).
 */
(function () {
    'use strict';

    const sections = document.querySelectorAll('.oli-folder-gallery');
    if (sections.length === 0) {
        return;
    }

    sections.forEach((section) => {
        const links = Array.from(section.querySelectorAll('.oli-folder-gallery__link'));
        if (links.length === 0) {
            return;
        }
        // Snapshot des photos de cette section (lue une fois).
        const photos = links.map((a) => {
            const img = a.querySelector('img');

            return {
                url: a.getAttribute('href') || '',
                alt: img ? (img.getAttribute('alt') || '') : '',
                caption: a.getAttribute('data-caption') || '',
            };
        });

        links.forEach((link, index) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                openLightbox(photos, index);
            });
        });
    });

    /**
     * Lightbox plein écran avec navigation prev/next + clavier.
     *
     * @param {Array<{url:string, alt:string, caption:string}>} photos
     * @param {number} startIndex
     */
    function openLightbox(photos, startIndex) {
        if (!photos.length || document.querySelector('.oli-lightbox')) {
            return;
        }

        let index = Math.min(Math.max(0, startIndex), photos.length - 1);

        const overlay = document.createElement('div');
        overlay.className = 'oli-lightbox';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Galerie photos agrandie');

        overlay.innerHTML = `
            <button type="button" class="oli-lightbox__close" aria-label="Fermer">×</button>
            <button type="button" class="oli-lightbox__nav oli-lightbox__nav--prev" aria-label="Précédent">‹</button>
            <button type="button" class="oli-lightbox__nav oli-lightbox__nav--next" aria-label="Suivant">›</button>
            <figure class="oli-lightbox__figure">
                <img class="oli-lightbox__image" alt="">
                <figcaption class="oli-lightbox__caption"></figcaption>
                <p class="oli-lightbox__counter"></p>
            </figure>
        `;

        const closeBtn = overlay.querySelector('.oli-lightbox__close');
        const prevBtn  = overlay.querySelector('.oli-lightbox__nav--prev');
        const nextBtn  = overlay.querySelector('.oli-lightbox__nav--next');
        const img      = overlay.querySelector('.oli-lightbox__image');
        const caption  = overlay.querySelector('.oli-lightbox__caption');
        const counter  = overlay.querySelector('.oli-lightbox__counter');

        function show(i) {
            index = ((i % photos.length) + photos.length) % photos.length;
            const p = photos[index];
            img.src = p.url;
            img.alt = p.alt || '';
            caption.textContent = p.caption || '';
            caption.style.display = p.caption ? 'block' : 'none';
            counter.textContent = `${index + 1} / ${photos.length}`;
        }

        function dismiss() {
            overlay.remove();
            document.body.style.overflow = '';
            document.removeEventListener('keydown', onKey);
        }

        function onKey(e) {
            switch (e.key) {
                case 'Escape':     dismiss(); break;
                case 'ArrowLeft':  show(index - 1); break;
                case 'ArrowRight': show(index + 1); break;
            }
        }

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) dismiss();
        });
        closeBtn.addEventListener('click', dismiss);
        prevBtn.addEventListener('click',  (e) => { e.stopPropagation(); show(index - 1); });
        nextBtn.addEventListener('click',  (e) => { e.stopPropagation(); show(index + 1); });
        img.addEventListener('click',      (e) => { e.stopPropagation(); show(index + 1); });
        document.addEventListener('keydown', onKey);

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
        show(index);

        requestAnimationFrame(() => closeBtn.focus());
    }
})();
