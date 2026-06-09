/**
 * Galerie photos/vidéos : switch de l'élément principal au clic sur une vignette.
 * Sur la galerie photos : un clic sur l'image principale ouvre une lightbox.
 * Filtres dossier (photos uniquement) : rangée de boutons « Tous | <dossier> »
 * qui rechargent les vignettes depuis un JSON inline (`#oli-gallery-data`).
 * Détecte le type via data-gallery-photos ou data-gallery-videos.
 */
export function initGallery() {
    const root = document.querySelector('[data-gallery-photos], [data-gallery-videos]');
    if (!root) return;

    const isVideo     = root.matches('[data-gallery-videos]');
    const mainImg     = root.querySelector('[data-gallery-main-image]');
    const mainIframe  = root.querySelector('[data-gallery-main-iframe]');
    const mainCaption = root.querySelector('[data-gallery-main-caption]');
    const thumbsList  = root.querySelector('[data-gallery-thumbs-list]') || root.querySelector('.gallery__thumbs');

    // Event delegation sur la liste de vignettes pour survivre aux re-render.
    if (thumbsList) {
        thumbsList.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-gallery-thumb]');
            if (!btn) return;
            e.preventDefault();
            thumbsList.querySelectorAll('[data-gallery-thumb]').forEach((b) => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            applyThumb(btn);
        });
        const first = thumbsList.querySelector('[data-gallery-thumb]');
        if (first) first.classList.add('is-active');
    }

    function applyThumb(btn) {
        if (isVideo && mainIframe) {
            const embed = btn.getAttribute('data-embed') || '';
            if (embed) {
                const url = new URL(embed, window.location.origin);
                url.searchParams.set('autoplay', '1');
                mainIframe.src = url.toString();
            }
            if (mainCaption) {
                mainCaption.textContent = btn.getAttribute('data-caption') || '';
            }
        } else if (mainImg) {
            mainImg.src = btn.getAttribute('data-url') || '';
            // Met à jour le srcset au swap, sinon l'ancien resterait
            // prioritaire sur le nouveau src (le navigateur préfère srcset).
            const srcset = btn.getAttribute('data-srcset') || '';
            if (srcset) {
                mainImg.srcset = srcset;
                mainImg.sizes = '(max-width: 900px) 100vw, 720px';
            } else {
                mainImg.removeAttribute('srcset');
            }
            mainImg.alt = btn.getAttribute('data-alt') || '';
            if (mainCaption) {
                mainCaption.textContent = btn.getAttribute('data-caption') || '';
            }
        }
    }

    // === Filtres par dossier (galerie photos uniquement) ===
    const filtersNav = document.querySelector('[data-gallery-filters]');
    const dataScript = document.getElementById('oli-gallery-data');
    if (!isVideo && filtersNav && dataScript && thumbsList) {
        let galleryData = {};
        try {
            galleryData = JSON.parse(dataScript.textContent || '{}');
        } catch (_e) {
            galleryData = {};
        }

        function renderThumbs(photos) {
            thumbsList.innerHTML = photos.map((p) => {
                const srcset = p.srcset
                    ? ` srcset="${escapeAttr(p.srcset)}" sizes="160px"`
                    : '';
                return `
                    <li class="gallery__thumb-item">
                        <button type="button"
                                class="gallery__thumb-button"
                                data-gallery-thumb
                                data-url="${escapeAttr(p.url)}"
                                data-srcset="${escapeAttr(p.srcset || '')}"
                                data-alt="${escapeAttr(p.alt || '')}"
                                data-caption="${escapeAttr(p.caption || '')}">
                            <img class="gallery__thumb-image" src="${escapeAttr(p.thumb || p.url)}"${srcset} alt="${escapeAttr(p.alt || '')}" loading="lazy">
                        </button>
                    </li>`;
            }).join('');

            const first = thumbsList.querySelector('[data-gallery-thumb]');
            if (first) {
                first.classList.add('is-active');
                applyThumb(first);
            } else if (mainImg) {
                // Dossier vide → vide aussi la principale.
                mainImg.removeAttribute('src');
                mainImg.removeAttribute('srcset');
                mainImg.alt = '';
                if (mainCaption) mainCaption.textContent = '';
            }
        }

        filtersNav.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-gallery-filter]');
            if (!btn) return;
            const key = btn.getAttribute('data-gallery-filter') || 'all';
            filtersNav.querySelectorAll('[data-gallery-filter]').forEach((b) => {
                b.classList.remove('is-active');
                b.setAttribute('aria-pressed', 'false');
            });
            btn.classList.add('is-active');
            btn.setAttribute('aria-pressed', 'true');
            renderThumbs(Array.isArray(galleryData[key]) ? galleryData[key] : []);
        });
    }

    // Lightbox sur la photo principale (galerie photos uniquement).
    if (!isVideo && mainImg) {
        mainImg.style.cursor = 'zoom-in';
        mainImg.addEventListener('click', () => {
            // On reconstruit la liste à chaque ouverture pour refléter le
            // filtre courant (les vignettes ont pu être rerendues).
            const thumbs = thumbsList ? thumbsList.querySelectorAll('[data-gallery-thumb]') : [];
            const photos = Array.from(thumbs).map((btn) => ({
                url: btn.getAttribute('data-url') || '',
                alt: btn.getAttribute('data-alt') || '',
                caption: btn.getAttribute('data-caption') || '',
            }));
            const activeIndex = Array.from(thumbs).findIndex((b) => b.classList.contains('is-active'));
            openLightbox(photos, Math.max(0, activeIndex));
        });
    }
}

/**
 * Échappe une valeur pour usage dans un attribut HTML.
 *
 * @param {string} s
 * @returns {string}
 */
function escapeAttr(s) {
    return String(s).replace(/[&"'<>]/g, (c) => ({
        '&': '&amp;',
        '"': '&quot;',
        "'": '&#39;',
        '<': '&lt;',
        '>': '&gt;',
    }[c]));
}

/**
 * Lightbox plein écran avec navigation prev/next.
 * - Clic sur fond ou bouton × → ferme
 * - Clavier : Escape → ferme, ← → → naviguer
 * - Clic sur l'image → next (geste naturel)
 *
 * @param {Array<{url:string, alt:string, caption:string}>} photos
 * @param {number} startIndex
 */
function openLightbox(photos, startIndex) {
    if (!photos.length || document.querySelector('.oli-lightbox')) return;

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
        // Fond cliqué (pas un enfant) → ferme.
        if (e.target === overlay) dismiss();
    });
    closeBtn.addEventListener('click', dismiss);
    prevBtn.addEventListener('click',  (e) => { e.stopPropagation(); show(index - 1); });
    nextBtn.addEventListener('click',  (e) => { e.stopPropagation(); show(index + 1); });
    img.addEventListener('click',       (e) => { e.stopPropagation(); show(index + 1); });
    document.addEventListener('keydown', onKey);

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
    show(index);

    requestAnimationFrame(() => closeBtn.focus());
}
