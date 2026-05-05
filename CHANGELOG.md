# Changelog

Format basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), versions selon [Semantic Versioning](https://semver.org/lang/fr/).

## [Unreleased]

## [1.0.0-alpha.6] - 2026-05-05

### Added (Plan 6 — Events CPT)

- `Events\EventEntity` — DTO immuable d'un événement (16 propriétés dont `isPast`/`isOngoing` calculés).
- `Events\EventCpt` — CPT `oli_event` (public, archive `evenements`, supports complet).
- `Events\EventModel` (+ `EventModelInterface`) — `findUpcoming`, `findPast`, `findById`, `findBySlug`.
- `Events\EventController` (+ interface) — `renderSingle`.
- `Events\EventArchiveController` (+ interface) — `renderArchive` séparant à venir / passés.
- `Events\EventMetabox` — métabox admin avec nonce + sanitization des 7 champs custom.
- `Events\EventsModule` — orchestrateur, branché dans `Theme::boot()`.
- Templates `pages/single-event.html.tpl`, `pages/archive-event.html.tpl`, `partials/event-card.html.tpl`, `admin/event-metabox.html.tpl`.
- Theme-bridge `theme-bridge/single-oli_event.php` + `archive-oli_event.php`.
- `assets/css/event.css` — styles BEM avec états `--past`/`--ongoing`/`--card`/`--single`, archive en grille responsive.
- `I18n\LanguageTaxonomy` étendue au CPT `oli_event`.
- Microdonnées `schema.org/Event` natives dans `single-event.html.tpl` (itemprop name/startDate/endDate/location/url/description).
- Test d'intégration `EventResolutionTest` (résolution des controllers via container après boot).
- Guide `docs/events.md` + ADR 0007 (CPT dédié vs posts taggés vs plugins tiers).

## [1.0.0-alpha.5] - 2026-05-05

### Added (Plan 5 — Slides & Home Carousel)

- `Slides\SlideEntity` — DTO immuable d'un slide.
- `Slides\SlideCpt` — CPT `oli_slide` (titre, image à la une, extrait, langue, ordre).
- `Slides\SlideModel` (+ `SlideModelInterface`) — `findActive(Language)` et `findById(int)`.
- `Slides\HomeCarouselViewModel` — DTO du view-model carousel (slides + autoplay + intervalMs + loop).
- `Slides\HomeCarouselController` (+ `HomeCarouselControllerInterface`) — `build(): HomeCarouselViewModel`.
- `Slides\SlidesModule` — orchestrateur, branché dans `Theme::boot()`.
- `templates/partials/carousel.html.tpl` — partial accessible (aria-roledescription, aria-label, lazy-loading).
- `templates/pages/front-page.html.tpl` inclut le carousel.
- `assets/css/carousel.css` — styles BEM, scroll-snap natif sans JS, transform avec JS.
- `assets/js/carousel.js` — autoplay (5s), swipe, clavier, prefers-reduced-motion, pause hover/focus/visibility.
- `assets/js/main.js` charge `initCarousel` quand `[data-carousel]` est présent.
- `Posts\PageController` détecte `is_front_page()` et injecte le carousel uniquement dans le view-model d'accueil.
- `I18n\LanguageTaxonomy` étendue au CPT `oli_slide`.
- Test d'intégration `CarouselFrontPageTest` (résolution via container après boot).
- Guide `docs/slides.md` + ADR 0006 (CPT dédié vs blocs/ACF/options).

## [1.0.0-alpha.4] - 2026-05-05

### Added (Plan 4 — Navigation)

- `Navigation\MenuItemEntity` — DTO immuable d'item de menu (avec arbre `children`).
- `Navigation\MenuModel` — convertit la liste plate WP en arbre, résout `current` et `ancestor`.
- `Navigation\MenuLocations` — enregistre `primary_<code>` et `footer_<code>` pour chaque langue activée.
- `Navigation\MenuController` — `buildPrimary(Language)` / `buildFooter(Language)`.
- `Navigation\NavigationModule` — orchestrateur, branché dans `Theme::boot()` (avant `PostsModule`).
- Interfaces extraites pour le mocking PHPUnit : `MenuModelInterface`, `MenuControllerInterface`.
- Templates `partials/nav-desktop.html.tpl` + `partials/nav-mobile.html.tpl`.
- Header (`partials/header.html.tpl`) inclut les deux navs.
- Footer (`partials/footer.html.tpl`) rend `footerMenu` quand présent.
- `assets/css/menu.css` — styles BEM desktop/hover + mobile drawer responsive (importé depuis `main.css`).
- `assets/js/main.js` (entry ES module) + `assets/js/menu-mobile.js` (drawer accessible Escape/Tab).
- Posts controllers (`PageController`, `PostController`, `NotFoundController`) injectent `MenuControllerInterface` et exposent `primaryMenu` / `footerMenu` aux view-models.
- Guide utilisateur `docs/navigation.md` + ADR 0005 (locations par langue).

## [1.0.0-alpha.3] - 2026-05-05

### Added (Plan 3 — Templates & Posts/Pages)

- `Posts\PostEntity` — DTO immuable du contenu WP.
- `Posts\PostModel` — modèle générique (`find`, `findBySlug`, `findByLanguage`, `getMeta`).
- `Posts\PageController` — rendu singulier des pages.
- `Posts\PostController` — rendu single, archive et recherche des posts.
- `Posts\NotFoundController` — rendu 404.
- `Posts\PostsModule` — enregistrement des services posts dans le container, branché dans `Theme::boot()`.
- Layout racine Lunar `templates/layouts/base.html.tpl` + partials (`header`, `banner`, `footer`, `breadcrumbs` placeholder).
- Templates de pages : `page`, `single-post`, `archive-post`, `search`, `404`, `front-page`.
- Pontages WordPress : `theme-bridge/{page,single,archive,search,404,front-page,index}.php`.
- `Theme::container()` exposé pour les pontages.
- `Theme::bootstrapViewRenderer()` câble les variables globales (`siteName`, `siteUrl`, `homeUrl`, `themeUri`, `charset`, `currentYear`) et les macros lazy `wpHead` / `wpFooter`.
- `AssetManager::enqueueFront` câble `main.css` avec versioning `filemtime` (test ajouté).
- Couche CSS de base : `tokens`, `reset`, `base`, `main` (vanilla, BEM, sans build).
- Interfaces extraites pour le mocking PHPUnit 11 : `PostModelInterface`, `LanguageRegistryInterface`, `LanguageResolverInterface`, `LanguageSwitcherControllerInterface`.
- Test d'intégration end-to-end (`tests/Integration/RenderEndToEndTest.php`) — boot complet + rendu HTML d'une page.
- ADR 0004 — layout Lunar unique, bridge minimal, macros lazy.
- Guide développeur `docs/templates.md`.

### Changed

- Mise à jour `yrbane/lunar-template` à `2de89f0` — accès hybride array/objet via `Runtime\Access::get` (issue [#14](https://github.com/yrbane/lunar-template/issues/14)). Permet de passer directement les DTO immuables aux templates sans conversion.
- Métadonnées thème (`style.css`) et package (`composer.json`) — bascule `sinceandco/oli-theme` → `yrbane/oli-theme`, auteur `yrbane <yrbane@nethttp.net>`. Historique git réécrit pour cohérence.

## [1.0.0-alpha.2] - 2026-05-05

### Added (Plan 2 — I18n)

- Value object `Language` immuable.
- `LanguageRegistry` — catalogue + langues activées + langue par défaut.
- `LanguageTaxonomy` — taxonomie `language` enregistrée sur posts/pages.
- `LanguageResolver` — détection URL > cookie > Accept-Language > défaut.
- `TranslationModel` — groupes de traduction UUID, link/unlink.
- `RewriteRules` — URLs `/fr/`, `/en/`, `/it/`, `/es/`.
- `LanguageUrlFilter` — préfixage auto de `home_url`.
- `LanguageSwitcherController` + `LanguageSwitcherViewModel` — switcher de langue.
- `LanguageMetabox` — UI admin "Traductions" (template Lunar à venir).
- `I18nModule` — orchestrateur, branché dans `Theme::boot()`.
- `Core\RendererInterface` — extrait pour permettre le mock du moteur de templates.
- ADR 0003 — choix custom vs plugin.
- Guide utilisateur `docs/multilingue.md`.

## [1.0.0-alpha] - 2026-05-05

### Added

- Bootstrap du thème via `OliTheme\Theme::boot()`.
- Conteneur de dépendances minimaliste (`OliTheme\Container`).
- Services Core : `ViewRenderer` (Lunar), `AssetManager`, `RequestContext`, `HookRegistrar`.
- Contrats `ModuleInterface` et `PostTypeInterface`.
- Layout minimal `templates/layouts/empty.html.tpl` + pont `theme-bridge/index.php`.
- Pipeline qualité : PHPUnit 11, Brain Monkey, PHPStan niveau 8, PHP-CS-Fixer (PSR-12), captainhook.
- Workflow GitHub Actions (matrice PHP 8.3 / 8.4 / 8.5).
- Documentation : architecture, installation, tests, ADR 0001 (MVC), ADR 0002 (Lunar).

### Notes

- `phpdocumentor/phpdocumentor` retiré temporairement de `require-dev` (incompatible PHP 8.5 via sa dépendance `phpdocumentor/json-path 0.2.1`). À réintroduire quand l'amont supportera PHP 8.5. Le script `composer docs` est désactivé en attendant.
- `yrbane/lunar-template` installé via dépôt VCS GitHub (non publié sur Packagist) en `dev-main` faute de tag stable.
