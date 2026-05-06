/**
 * Galerie photos/vidéos : switch de l'élément principal au clic sur une vignette.
 * Détecte le type via data-gallery-photos ou data-gallery-videos.
 */
export function initGallery() {
    const root = document.querySelector('[data-gallery-photos], [data-gallery-videos]');
    if (!root) return;

    const isVideo    = root.matches('[data-gallery-videos]');
    const mainImg    = root.querySelector('[data-gallery-main-image]');
    const mainIframe = root.querySelector('[data-gallery-main-iframe]');
    const mainCaption = root.querySelector('[data-gallery-main-caption]');
    const thumbs     = root.querySelectorAll('[data-gallery-thumb]');

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
}
