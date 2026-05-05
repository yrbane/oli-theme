# Slides & Home Carousel Implementation Plan (oli-theme — Cycle 1, Plan 5/10)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the slides system: a CPT `oli_slide` editable in admin, an immutable `SlideEntity` DTO, a `SlideModel` returning active slides per language, a `HomeCarouselController` exposing the carousel view-model, a Lunar partial `partials/carousel.html.tpl`, a vanilla JS module `carousel.js`, BEM CSS module, and a `SlidesModule` orchestrating everything. After this plan, the front page renders a real, multilingual, accessible carousel.

**Architecture:** `SlideCpt::register()` declares `oli_slide` (with thumbnail, title, excerpt, custom-fields, language taxonomy). `SlideModel::findActive(Language)` queries WP for non-expired slides ordered by `menu_order`, returns `SlideEntity[]`. `HomeCarouselController::build()` resolves the current language and assembles the view-model (slides + auto-play config). The Lunar partial is included in `pages/front-page.html.tpl`. The carousel is progressively enhanced: without JS, the first slide is visible; with JS, full controls + autoplay + swipe.

**Tech Stack:** Same as Plans 1-4. Lunar `2de89f0+` (hybrid array/object access). Vanilla CSS + ES module JS, no build.

**Reference spec:** `docs/superpowers/specs/2026-05-05-oli-theme-design.md` — sections 2.4 (CPT `oli_slide`), 4.5 (Module HomeCarousel), 3.6-3.8 (assets).

**Out of scope:** thumbnail metabox UI improvements, slide reordering UX, A/B testing, image lazy-loading priority hints — only Plan 5 essentials.

---

## File Structure

### Source (`src/Slides/`, namespace `OliTheme\Slides`)

- `src/Slides/SlideEntity.php` — DTO immuable
- `src/Slides/SlideCpt.php` — `register()` enregistre le CPT `oli_slide`
- `src/Slides/SlideModelInterface.php` — interface narrow
- `src/Slides/SlideModel.php` — `findActive(Language)` + `findById(int)`
- `src/Slides/HomeCarouselViewModel.php` — DTO immuable du view-model du carousel (slides + config autoplay/loop)
- `src/Slides/HomeCarouselControllerInterface.php` — interface narrow
- `src/Slides/HomeCarouselController.php` — `build(): HomeCarouselViewModel`
- `src/Slides/SlidesModule.php` — orchestrateur

### Tests (`tests/Unit/Slides/`)

- `SlideEntityTest.php`
- `SlideCptTest.php`
- `SlideModelTest.php`
- `HomeCarouselViewModelTest.php`
- `HomeCarouselControllerTest.php`
- `SlidesModuleTest.php`

### Templates Lunar

- `templates/partials/carousel.html.tpl` (créer)
- Modify: `templates/pages/front-page.html.tpl` — inclure le carousel

### Assets

- `assets/css/carousel.css` (créer + import dans `main.css`)
- `assets/js/carousel.js` (créer)
- Modify: `assets/js/main.js` — auto-init carousel

### Modifications

- `src/Theme.php` — wire `SlidesModule`
- `src/I18n/LanguageTaxonomy.php` — étendre la taxonomie `language` au CPT `oli_slide` (Plan 2 a fait `page`/`post` ; à confirmer en lisant le fichier)

### Documentation

- `docs/slides.md` — guide utilisateur (créer un slide, durée, expiration)
- `docs/decisions/0006-slides-cpt.md` — ADR (CPT dédié vs blocs Gutenberg vs ACF Repeater)
- `CHANGELOG.md` — entrée `1.0.0-alpha.5`

---

## Conventions (rappel)

- Code English, French PHPDoc + Conventional Commits.
- TDD strict. WP functions in `src/`: NO leading backslash.
- Brain Monkey for WP mocks. Final classes → narrow interfaces (T3-T5 pattern).
- Lunar 2de89f0+ supports `[[ obj.prop ]]` on objects via `Runtime\Access::get`.
- NO `Co-Authored-By: Claude` lines in commits.

---

## Tasks

### T1 — Branche + warm-up

- mkdir `src/Slides tests/Unit/Slides`
- gitkeep + commit `chore(plan5): squelette de dossiers pour les slides`

### T2 — `SlideEntity` (DTO immuable)

Properties (final readonly): `id (int)`, `title (string)`, `caption (?string)`, `imageUrl (string)`, `imageAlt (?string)`, `linkUrl (?string)`, `linkLabel (?string)`, `order (int)`, `expiresAt (?DateTimeImmutable)`, `language (Language)`.

Test: 1 method `testItExposesAllProperties` asserting all fields including a non-null `expiresAt`. Plus `testItAcceptsNullableOptionals`.

Commit: `feat(slides): ajoute SlideEntity (DTO immuable de slide)`.

### T3 — `SlideCpt` (CPT `oli_slide`)

Class with `register(): void` that calls `register_post_type('oli_slide', [...])`. Args:

```php
[
    'public' => false,
    'show_ui' => true,
    'show_in_rest' => true,
    'menu_position' => 22,
    'menu_icon' => 'dashicons-images-alt2',
    'supports' => ['title', 'thumbnail', 'excerpt', 'page-attributes'],
    'taxonomies' => ['language'],
    'has_archive' => false,
    'rewrite' => false,
    'labels' => [
        'name' => __('Slides', 'oli-theme'),
        'singular_name' => __('Slide', 'oli-theme'),
        'add_new_item' => __('Ajouter un slide', 'oli-theme'),
        'edit_item' => __('Modifier le slide', 'oli-theme'),
        'menu_name' => __('Slides', 'oli-theme'),
    ],
]
```

Test: stub `register_post_type` and `__`, capture args, assert key shape. Class implements `OliTheme\Core\PostTypeInterface` if it exists (Plan 1 contracts), else just `register(): void`.

Also add a `slug(): string` returning `'oli_slide'` for reuse.

Commit: `feat(slides): ajoute SlideCpt (CPT oli_slide)`.

### T4 — `SlideModel` + `SlideModelInterface`

Methods:
- `findActive(Language $lang, int $limit = 10): array` — returns `SlideEntity[]` (non-expired, published, ordered by `menu_order ASC`).
- `findById(int $id): ?SlideEntity` — returns null if missing.

Implementation calls `get_posts` with `post_type=oli_slide`, `posts_per_page=$limit`, `orderby=menu_order`, `order=ASC`, `tax_query` for language, and a `meta_query` filtering out expired slides (`_oli_slide_expires_at` either absent or > now).

Hydrate `SlideEntity` from `get_the_post_thumbnail_url`, `get_post_meta` (caption, link, expires).

Tests: 4 tests
- `testFindActiveReturnsEmptyArrayWhenNothing`
- `testFindActiveBuildsEntities`
- `testFindByIdReturnsNullWhenMissing`
- `testFindByIdHydratesEntity`

Brain Monkey stubs all WP calls.

Interface `SlideModelInterface` extracted (T3-T5 pattern).

Commit: `feat(slides): ajoute SlideModel (findActive + findById par langue)`.

### T5 — `HomeCarouselViewModel`

Properties: `slides (SlideEntity[])`, `autoplay (bool)`, `intervalMs (int)`, `loop (bool)`. Final readonly. Defaults: `autoplay=true`, `intervalMs=5000`, `loop=true`.

Tests: 1 method asserting properties.

Commit: `feat(slides): ajoute HomeCarouselViewModel`.

### T6 — `HomeCarouselController` + Interface

`build(): HomeCarouselViewModel` — resolves current language from `LanguageResolverInterface`, calls `$slides->findActive($current)`, wraps in `HomeCarouselViewModel` with default config.

Constructor: `(SlideModelInterface $slides, LanguageResolverInterface $resolver)`.

Tests: 2 methods
- `testBuildReturnsViewModelWithSlides`
- `testBuildReturnsEmptyViewModelWhenNoSlides` (empty array but valid VM with config defaults)

Interface extracted.

Commit: `feat(slides): ajoute HomeCarouselController + interface`.

### T7 — `SlidesModule`

`register()` :
1. Hooke `init` → `SlideCpt::register()`
2. Enregistre dans le Container : `SlideCpt`, `SlideModel`/`SlideModelInterface`, `HomeCarouselController`/`HomeCarouselControllerInterface`.

Tests: 2 methods
- `testRegisterBindsAllSlidesServices`
- `testRegisterHooksInit`

Commit: `feat(slides): ajoute SlidesModule (services + hook init pour CPT)`.

### T8 — Wire `SlidesModule` in `Theme::boot()`

Insert `(new \OliTheme\Slides\SlidesModule($container))->register();` near the other modules in `Theme::registerCoreHooks()`. Place after `I18nModule` (it consumes `LanguageResolverInterface`) and before `PostsModule` (controllers in Plan 5+ might consume the carousel, but PageController for the front page has its own logic — verify).

Add `testBootRegistersSlidesModule` in `ThemeTest`.

Commit: `feat(theme): branche SlidesModule au boot`.

### T9 — Inject carousel into front-page view-model

Modify `PageController::renderSingular()` to detect when the rendered post is the front page (`is_front_page()` or check against `get_option('page_on_front')`). If yes, add `carousel` (HomeCarouselViewModel) to the view-model. Otherwise leave it out.

Inject `HomeCarouselControllerInterface` into `PageController` constructor; update `PostsModule` factory and the test.

Alternative simpler: always include `carousel` in the front-page view-model and let the template decide. But the simpler approach pollutes other pages' view-models. **Choose** the conditional approach.

If detection from `is_front_page()` is hard to mock cleanly, use a private `isFrontPage(int $postId): bool` helper that compares against `(int) get_option('page_on_front')` — easy to stub.

Add a `testRenderFrontPageIncludesCarousel` test.

Commit: `feat(posts): expose le carousel dans le view-model de la page d'accueil`.

### T10 — `partials/carousel.html.tpl`

```html
[# Carousel d'accueil (slides Oli).
   Variables attendues:
     - carousel (HomeCarouselViewModel) avec slides[], autoplay, intervalMs, loop.
   Sans JS: la première slide est visible (CSS gère).
#]
[% if carousel.slides %]
<section class="carousel" data-carousel
         data-autoplay="[% if carousel.autoplay %]true[% else %]false[% endif %]"
         data-interval="[[ carousel.intervalMs ]]"
         data-loop="[% if carousel.loop %]true[% else %]false[% endif %]"
         aria-roledescription="carousel"
         aria-label="Diaporama d'accueil">
    <ul class="carousel__list" role="list">
        [% for slide in carousel.slides %]
            <li class="carousel__slide" role="group" aria-roledescription="slide" aria-label="[[ slide.title ]]">
                <figure class="carousel__figure">
                    <img class="carousel__image" src="[[ slide.imageUrl ]]" alt="[[ slide.imageAlt ]]" loading="lazy">
                    [% if slide.caption %]
                        <figcaption class="carousel__caption">[[! slide.caption !]]</figcaption>
                    [% endif %]
                </figure>
                [% if slide.linkUrl %]
                    <a class="carousel__cta btn btn--primary" href="[[ slide.linkUrl ]]">
                        [% if slide.linkLabel %][[ slide.linkLabel ]][% else %]En savoir plus[% endif %]
                    </a>
                [% endif %]
            </li>
        [% endfor %]
    </ul>
    <div class="carousel__controls" data-carousel-controls hidden>
        <button class="carousel__btn carousel__btn--prev" type="button" data-carousel-prev aria-label="Précédent">‹</button>
        <button class="carousel__btn carousel__btn--next" type="button" data-carousel-next aria-label="Suivant">›</button>
    </div>
</section>
[% endif %]
```

Commit: `feat(templates): partial carousel (accessible, progressive enhancement)`.

### T11 — Update `front-page.html.tpl`

Insert `[% include 'partials/carousel.html.tpl' %]` at the top of the `[% block main %]`, before the existing logic.

Commit: `feat(templates): front-page inclut le carousel`.

### T12 — `carousel.css`

Vanilla BEM, sans JS la première slide est visible. Avec JS, masquer les autres et basculer via classes/transformations CSS.

```css
.carousel { position: relative; overflow: hidden; }
.carousel__list { list-style: none; margin: 0; padding: 0; display: flex; transition: transform var(--transition-base); }
.carousel__slide { flex: 0 0 100%; min-width: 0; position: relative; }
.carousel__image { width: 100%; height: auto; display: block; }
.carousel__caption { padding: var(--space-4); background: rgba(0, 0, 0, 0.05); }
.carousel__cta { margin: var(--space-4); }
.carousel__controls { position: absolute; inset: 50% 0 auto 0; display: flex; justify-content: space-between; padding: 0 var(--space-3); transform: translateY(-50%); }
.carousel__btn {
    width: 2.5rem; height: 2.5rem; border-radius: 50%;
    background: rgba(255, 255, 255, 0.9); border: 1px solid var(--color-border);
    cursor: pointer; font-size: 1.5rem; line-height: 1;
}
.carousel__btn:hover, .carousel__btn:focus-visible { background: var(--color-bg); }
/* Sans JS: les slides au-delà de la première sont quand même rendus en flex; le scroll horizontal natif fonctionne. */
.carousel:not([data-carousel-active]) .carousel__list { overflow-x: auto; scroll-snap-type: x mandatory; }
.carousel:not([data-carousel-active]) .carousel__slide { scroll-snap-align: start; }
```

Update `main.css` `@import url('./carousel.css');`.

Commit: `feat(assets): styles carousel.css (BEM, progressive enhancement)`.

### T13 — `carousel.js`

ES module with `initCarousel()` exporting:
- Reads `data-autoplay`, `data-interval`, `data-loop` from `[data-carousel]`.
- Adds `data-carousel-active` to the carousel root (CSS switches to JS-controlled mode).
- Reveals the prev/next buttons (`controls.removeAttribute('hidden')`).
- Implements `goToSlide(index)` that translates `.carousel__list` via `transform: translateX(-N*100%)`.
- Autoplay timer with pause on hover/focus/visibilitychange.
- Respects `prefers-reduced-motion: reduce` (disables autoplay).
- Pointer Events for swipe (basic dragstart/dragend distance threshold).
- Keyboard: ArrowLeft/ArrowRight on the carousel container.

Update `main.js` to `import { initCarousel } from './carousel.js'` and call it when `[data-carousel]` exists.

Commit: `feat(assets): carousel.js (autoplay, swipe, clavier, reduced-motion)`.

### T14 — End-to-end smoke

Add `tests/Integration/CarouselFrontPageRenderTest.php` (or similar) that boots the theme, stubs WP for the front page, and asserts the rendered HTML contains the carousel markup when slides exist (and absent otherwise).

Commit: `test(integration): rendu du carousel sur la page d'accueil`.

### T15 — Doc + ADR 0006

`docs/slides.md` :
- Comment créer un slide (admin > Slides > Ajouter)
- Champs : titre, image à la une, extrait, lien (`_oli_slide_link_url`, `_oli_slide_link_label`), expiration (`_oli_slide_expires_at`), ordre via `menu_order`, langue via taxonomie
- Comportement front : carousel sur la page d'accueil seulement, slides expirés masqués

`docs/decisions/0006-slides-cpt.md` :
- Décision : CPT dédié vs blocs Gutenberg vs ACF Repeater
- Rejets : Gutenberg (couplage thème/éditeur, complexe), ACF (dépendance plugin)
- Conséquences

Commit: `docs: guide slides + ADR 0006 (CPT dédié)`.

### T16 — Changelog `1.0.0-alpha.5` + tag + push

```markdown
## [1.0.0-alpha.5] - 2026-05-05

### Added (Plan 5 — Slides & Home Carousel)

- `Slides\SlideEntity`, `Slides\SlideCpt` (`oli_slide`), `Slides\SlideModel`, `Slides\HomeCarouselViewModel`, `Slides\HomeCarouselController`, `Slides\SlidesModule`.
- Interfaces `SlideModelInterface`, `HomeCarouselControllerInterface`.
- Template `partials/carousel.html.tpl` + injection dans `front-page.html.tpl`.
- `assets/css/carousel.css` + `assets/js/carousel.js` (autoplay, swipe, clavier, reduced-motion).
- `PageController` détecte la page d'accueil et injecte `carousel` dans le view-model.
- Guide `docs/slides.md` + ADR 0006.
```

```bash
git tag -a v1.0.0-alpha.5-slides -m "Release 1.0.0-alpha.5 — Plan 5 (Slides & Home Carousel)"
git push origin main
git push origin v1.0.0-alpha.5-slides
```

---

## Definition of Done — Plan 5

1. ✅ `composer ci` returns 0
2. ✅ All 16 tasks committed
3. ✅ Tag `v1.0.0-alpha.5-slides` posed and pushed
4. ✅ ADR 0006 + `docs/slides.md` present
5. ✅ Theme renders the carousel on the front page when slides exist
6. ✅ Carousel works without JS (scroll snap fallback) and with JS (autoplay, prev/next, swipe, keyboard)

When all 6 boxes are ticked, **Plan 6 (Events CPT)** can start.

---

## Self-Review

### Spec coverage

| Spec section | Couvert ? | Tâche |
|---|---|---|
| 2.4 CPT `oli_slide` | ✅ | T3 |
| 2.4 SlideEntity immuable | ✅ | T2 |
| 2.4 SlideModel::findActive(Language) | ✅ | T4 |
| 4.5 HomeCarouselController | ✅ | T6 |
| 4.5 Autoplay, swipe, prefers-reduced-motion | ✅ | T13 |
| 3.6 ES module sans build | ✅ | T13 |
| 3.8 Lazy images natif | ✅ | T10 (`loading="lazy"`) |

### Placeholder scan
Aucun TODO en code.

### Type consistency
- `SlideEntity` props utilisées identiquement T2/T4/T10.
- `HomeCarouselViewModel` props (slides, autoplay, intervalMs, loop) cohérent T5/T6/T10.

### Scope
Plan 5 = slides pure. Aucun chevauchement avec Events / SEO / Settings.
