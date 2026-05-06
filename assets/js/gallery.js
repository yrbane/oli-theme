/**
 * Galerie photos/vidéos : switch de l'élément principal au clic sur une vignette.
 * Sur la galerie photos : un clic sur l'image principale ouvre une lightbox.
 * Détecte le type via data-gallery-photos ou data-gallery-videos.
 */
export function initGallery() {
    const root = document.querySelector('[data-gallery-photos], [data-gallery-videos]');
    if (!root) return;

    const isVideo     = root.matches('[data-gallery-videos]');
    const mainImg     = root.querySelector('[data-gallery-main-image]');
    const mainIframe  = root.querySelector('[data-gallery-main-iframe]');
    const mainCaption = root.querySelector('[data-gallery-main-caption]');
    const thumbs      = root.querySelectorAll('[data-gallery-thumb]');

    if (!thumbs.length) return;
    thumbs[0].classList.add('is-active');

    thumbs.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            thumbs.forEach((b) => b.classList.remove('is-active'));
            btn.classList.add('is-active');

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
                mainImg.alt = btn.getAttribute('data-alt') || '';
                if (mainCaption) {
                    mainCaption.textContent = btn.getAttribute('data-caption') || '';
                }
            }
        });
    });

    // Lightbox sur la photo principale (galerie photos uniquement).
    if (!isVideo && mainImg) {
        mainImg.style.cursor = 'zoom-in';
        mainImg.addEventListener('click', () => openLightbox(mainImg));
    }
}

/**
 * Lightbox plein écran : overlay sombre + image agrandie + caption.
 * Fermeture au clic, à la touche Escape, ou via le bouton ×.
 */
function openLightbox(sourceImg) {
    if (document.querySelector('.oli-lightbox')) return; // déjà ouverte

    const overlay = document.createElement('div');
    overlay.className = 'oli-lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', sourceImg.alt || 'Photo agrandie');

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'oli-lightbox__close';
    close.setAttribute('aria-label', 'Fermer');
    close.innerHTML = '&times;';

    const img = document.createElement('img');
    img.className = 'oli-lightbox__image';
    img.src = sourceImg.src;
    img.alt = sourceImg.alt || '';

    overlay.appendChild(close);
    overlay.appendChild(img);

    // Caption (depuis le sibling figcaption si présent)
    const figcaption = sourceImg.closest('figure')?.querySelector('figcaption');
    if (figcaption && figcaption.textContent.trim() !== '') {
        const cap = document.createElement('p');
        cap.className = 'oli-lightbox__caption';
        cap.textContent = figcaption.textContent.trim();
        overlay.appendChild(cap);
    }

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    const dismiss = () => {
        overlay.remove();
        document.body.style.overflow = '';
        document.removeEventListener('keydown', onKey);
    };
    const onKey = (e) => {
        if (e.key === 'Escape') dismiss();
    };

    overlay.addEventListener('click', (e) => {
        // ferme au clic n'importe où SAUF sur l'image elle-même
        if (e.target !== img) dismiss();
    });
    close.addEventListener('click', dismiss);
    document.addEventListener('keydown', onKey);

    // focus sur le bouton de fermeture pour l'accessibilité clavier
    requestAnimationFrame(() => close.focus());
}
