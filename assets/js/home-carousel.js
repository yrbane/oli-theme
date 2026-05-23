/**
 * Carousel plein écran de la page d'accueil (variation Olikalari).
 *
 * Livré avec le thème (auparavant dans un mu-plugin de test). Récupère les
 * slides publiés de la langue courante via l'API REST, construit un carrousel
 * plein écran inséré au-dessus du header, avec auto-rotation, points de
 * navigation, navigation clavier et image à la une réelle (fallback picsum).
 *
 * Le script n'est enqueué par le thème que sur la page d'accueil (ou sa
 * traduction) quand la variation Olikalari est active.
 */

/** Langue courante déduite du premier segment d'URL (/en/, /it/…), sinon fr. */
function currentLang() {
  const m = window.location.pathname.match(/^\/([a-z]{2})(\/|$)/);
  return m ? m[1] : 'fr';
}

/** URL d'arrière-plan d'un slide : image à la une si présente, sinon picsum. */
function slideImage(slide) {
  const media = slide._embedded && slide._embedded['wp:featuredmedia'];
  if (media && media[0] && media[0].source_url) {
    return media[0].source_url;
  }
  return 'https://picsum.photos/seed/oli-slide-' + slide.id + '/1920/1080';
}

/** Effet parallaxe : convertit les figures de couverture en background fixe. */
function applyCoverParallax() {
  document.querySelectorAll('figure.page-cover').forEach((fig) => {
    const img = fig.querySelector('img');
    if (!img) return;
    const src = img.currentSrc || img.src;
    if (!src) return;
    fig.style.backgroundImage = 'url("' + src + '")';
    fig.classList.add('page-cover--bg');
    img.remove();
  });
}

function buildCarousel(slides) {
  if (!slides || !slides.length) return;
  slides.sort((a, b) => (a.menu_order || 0) - (b.menu_order || 0));
  document.body.classList.add('home');

  const wrap = document.createElement('section');
  wrap.className = 'carousel-fullscreen';
  wrap.setAttribute('aria-roledescription', 'carrousel');
  wrap.setAttribute('aria-label', 'Mise en avant');
  wrap.tabIndex = 0;

  const esc = (s) => String(s == null ? '' : s);
  wrap.innerHTML =
    slides
      .map((s, i) => {
        const img = slideImage(s);
        const title = esc(s.title && s.title.rendered);
        const excerpt = esc(s.excerpt && s.excerpt.rendered);
        return (
          '<div class="carousel-fullscreen__slide' + (i === 0 ? ' is-active' : '') + '"' +
          ' role="group" aria-roledescription="diapositive"' +
          ' style="background-image:url(\'' + img + '\')"' +
          ' aria-hidden="' + (i === 0 ? 'false' : 'true') + '">' +
          '  <div class="carousel-fullscreen__caption">' +
          '    <p class="carousel-fullscreen__title">' + title + '</p>' +
          '    <div class="carousel-fullscreen__excerpt">' + excerpt + '</div>' +
          '  </div>' +
          '</div>'
        );
      })
      .join('') +
    '<div class="carousel-fullscreen__dots" role="tablist" aria-label="Choisir une diapositive">' +
    slides
      .map(
        (s, i) =>
          '<button type="button" class="carousel-fullscreen__dot' +
          (i === 0 ? ' is-active' : '') + '" data-idx="' + i +
          '" aria-label="Diapositive ' + (i + 1) + '"></button>',
      )
      .join('') +
    '</div>';

  const header = document.querySelector('.site-header, body > header');
  const firstAnchor = header || document.querySelector('main.site-main') || document.body.firstChild;
  document.body.insertBefore(wrap, firstAnchor);
  document.body.classList.add('has-fullscreen-hero');

  if (header) {
    const setH = () => {
      document.documentElement.style.setProperty('--header-h', header.offsetHeight + 'px');
    };
    setH();
    window.addEventListener('resize', setH, { passive: true });
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(setH);
  }

  const slideEls = wrap.querySelectorAll('.carousel-fullscreen__slide');
  const dotEls = wrap.querySelectorAll('.carousel-fullscreen__dot');
  let idx = 0;

  function go(next) {
    slideEls[idx].classList.remove('is-active');
    slideEls[idx].setAttribute('aria-hidden', 'true');
    if (dotEls[idx]) dotEls[idx].classList.remove('is-active');
    idx = (next + slideEls.length) % slideEls.length;
    slideEls[idx].classList.add('is-active');
    slideEls[idx].setAttribute('aria-hidden', 'false');
    if (dotEls[idx]) dotEls[idx].classList.add('is-active');
  }

  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let auto = reduced ? null : setInterval(() => go(idx + 1), 5500);
  const restart = () => {
    if (auto) clearInterval(auto);
    auto = reduced ? null : setInterval(() => go(idx + 1), 5500);
  };

  dotEls.forEach((d) => {
    d.addEventListener('click', () => {
      go(parseInt(d.dataset.idx, 10));
      restart();
    });
  });

  // Navigation clavier (flèches ← →) quand le carrousel a le focus.
  wrap.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') { go(idx + 1); restart(); }
    else if (e.key === 'ArrowLeft') { go(idx - 1); restart(); }
  });
}

export function initHomeCarousel() {
  applyCoverParallax();
  fetch('/wp-json/wp/v2/oli_slide?per_page=10&_embed=1&lang=' + encodeURIComponent(currentLang()))
    .then((r) => (r.ok ? r.json() : []))
    .catch(() => [])
    .then(buildCarousel);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initHomeCarousel);
} else {
  initHomeCarousel();
}
