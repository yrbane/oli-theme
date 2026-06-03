// Parallaxe JS pour les navigateurs sans support background-attachment: fixed (mobile iOS notamment).
const init = () => {
  if (!document.body.classList.contains('gabarit-photo-story')) return;
  const heroes = document.querySelectorAll('.post__hero, .page__hero, .page__cover, .post__featured');
  if (!heroes.length || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  let ticking = false;
  const update = () => {
    const y = window.scrollY;
    heroes.forEach((el) => { el.style.backgroundPositionY = `${y * 0.4}px`; });
    ticking = false;
  };
  window.addEventListener('scroll', () => {
    if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
  }, { passive: true });
};
document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
