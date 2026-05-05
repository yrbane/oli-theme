# Changelog

Format basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), versions selon [Semantic Versioning](https://semver.org/lang/fr/).

## [Unreleased]

### Fixed

- **#1** — `mkdir() Permission denied` sur l'activation. Nouveau `Core\CacheDirectoryEnsurer` (TDD, 5 tests) qui crée le dossier et le valide sans lever d'exception ; fallback sur `sys_get_temp_dir()` si `.cache/templates` n'est pas writable, avec admin_notice (`oli_theme_cache_error`) au lieu d'un fatal Lunar. `.cache/templates/.gitkeep` versionné + contenu ignoré dans `.gitignore`.
- **#4** — `template_include` manquant. Tous les fichiers `theme-bridge/*.php` étaient ignorés par WP (qui ne descend pas dans les sous-dossiers) → seul `index.php` à la racine était utilisé, donnant l'archive des posts pour toutes les URL. Nouveau `Core\TemplateRouter` (TDD, 10 tests) hooké via `template_include` qui aiguille vers le bon shim selon `is_front_page`, `is_singular('oli_event')`, `is_post_type_archive`, `is_page`, `is_single`, `is_search`, `is_archive`, `is_404`.
- **#5** — `MenuController::buildFor()` passait une `theme_location` (string) à `wp_get_nav_menu_items()`, qui attend un menu ID, ce qui faisait retourner `false` et laissait les menus vides en permanence. Résolution préalable via `get_nav_menu_locations()[$location]` + 2 nouveaux tests de régression.

### Added

- `composer.json` : scripts `normalize-perms` + `post-install-cmd` / `post-update-cmd` qui forcent les permissions `a+r` / `a+rx` sur `vendor/` après chaque install/update (issue #2 — workaround pour les setups Docker avec `umask 027`).

### Changed

- `yrbane/lunar-template` : `2bb1925` → `0963257`.

## [1.0.0-rc.1] - 2026-05-06

### Plan 10 — QA & finalisation cycle 1

Release candidate du cycle 1. Aucune nouvelle fonctionnalité — consolidation, doc et préparation aux audits.

### Added
- `docs/user-guide/README.md` — index unifié des guides éditeur.
- `docs/user-guide/getting-started.md` — guide pédagogique (10 minutes pour publier sa première page multilingue + événement + SEO + contact).
- `docs/qa-cycle1.md` — checklist QA cycle 1 (Lighthouse, axe-core, W3C, JSON-LD, responsive, multilingue, contact, sitemap).
- `docs/superpowers/plans/2026-05-06-qa-finalization.md` — plan 10 documenté.

### Changed
- `style.css` Version : `1.0.0-alpha` → `1.0.0-rc.1`.
- `README.md` : table des 9 releases livrées + statut RC.
- `docs/architecture.md` : Plans 8 / 9 / 10 marqués livrés ✅.

### Cycle 1 — bilan

- 10 plans livrés : Foundation, I18n, Templates & Posts/Pages, Navigation, Slides & Carousel, Events, SEO complet, Contact, Settings, QA & finalisation.
- 9 modules fonctionnels : `I18n`, `Posts`, `Navigation`, `Slides`, `Events`, `Seo`, `Contact`, `Settings` + `Core`.
- ~298 tests, ~1038 assertions, PHPStan niveau 8 clean, PHP-CS-Fixer clean.
- 10 ADR documentés.
- 8 guides utilisateur (multilingue, navigation, slides, events, seo, contact, settings + getting-started).
- Spec d'origine couverte intégralement.

## [1.0.0-alpha.9] - 2026-05-06

### Added (Plan 9 — Settings admin)

- 6 DTOs immuables : `Settings\BannerSettings`, `FooterSettings`, `SocialSettings`, `LanguagesSettings` (avec constantes `FALLBACK_*`), `ContactSettings`, `SeoSettings`.
- `Settings\SettingsBag` — agrégateur immuable avec `::default()` retournant un bag neutre (langues `['fr']`, fallback `home`, toggles à `true`).
- `Settings\ThemeSettingsModel` (+ Interface) — `get/set/all` sur l'option WP `oli_theme_settings` (stockage atomique).
- `Settings\ThemeSettingsPage` — page admin sous **Apparence > Identité du site**, 6 onglets (Identité, Langues, Réseaux, Footer, Contact, SEO), Settings API native + `register_setting` / `add_settings_section` / `do_settings_sections` capturé en buffer.
- `Settings\SettingsModule` — orchestrateur, branché en tête de `Theme::registerCoreHooks()`.
- Template `templates/admin/settings-page.html.tpl` (wrapper Lunar avec onglets).
- `assets/css/admin.css` — styles admin minimaux.
- Test d'intégration `SettingsResolutionTest` (boot complet + résolution + `all()` retourne SettingsBag).
- Guide `docs/settings.md` + ADR 0010 (Settings API native vs Customizer / ACF / Carbon Fields).

## [1.0.0-alpha.8] - 2026-05-06

### Added (Plan 8 — Contact form)

- `Contact\ContactSubmission` + `Contact\ContactValidationResult` — DTOs immuables.
- `Contact\ContactFormModel` (+ Interface) — validation 8 règles + sanitization (text/email/textarea), clock injectable pour le time-trap.
- `Contact\ContactRateLimiter` (+ Interface) — 3 envois par IP / 15 min via transients WP.
- `Contact\ContactMailer` (+ Interface) — `wp_mail` avec `Reply-To` correct + auto-réponse optionnelle.
- `Contact\ContactLogCpt` — CPT `oli_contact_log` non-public, archivage des soumissions.
- `Contact\ContactLogModel` (+ Interface) — `wp_insert_post` + meta `_oli_contact_*`.
- `Contact\ContactFormController` (+ Interface) — pipeline sécurisé : nonce → honeypot → time-trap → rate-limit → validate → sanitize → send → autoReply (opt) → log (opt) → redirect.
- `Contact\ContactShortcode` — `[oli_contact_form]` rend le partial Lunar.
- `Contact\ContactModule` — services + hooks `init` (CPT + shortcode) + `admin_post_oli_contact` (priv & nopriv).
- Wire dans `Theme::boot()` (entre `EventsModule` et `PostsModule`).
- `I18n\LanguageTaxonomy` étendue au CPT `oli_contact_log`.
- Template `templates/partials/contact-form.html.tpl` (accessible, honeypot caché, errors par champ).
- `assets/css/contact.css` — styles BEM (focus visible, états error/success).
- `assets/js/contact-form.js` — progressive enhancement (auto-focus erreur, désactivation submit).
- Auto-init dans `assets/js/main.js`.
- Guide `docs/contact.md` + ADR 0009 (custom vs CF7 / Gravity Forms / WPForms / Ninja Forms).

## [1.0.0-alpha.7] - 2026-05-06

### Added (Plan 7 — SEO complet)

- `Seo\SeoMeta` — DTO immuable des meta SEO (13 champs).
- `Seo\SeoMetaModel` (+ Interface) — accès aux meta `_oli_seo_*`.
- `Seo\CanonicalBuilder`, `RobotsBuilder`, `OpenGraphBuilder`, `TwitterCardBuilder` — builders du `<head>`.
- `Seo\HreflangBuilder` — alternates multilingues exploitant `TranslationModel`.
- `Seo\Schema\SchemaInterface` + `SchemaContext` (agrégateur `@graph`) + 8 schémas : `WebSiteSchema`, `OrganizationSchema`, `PersonSchema`, `ImageObjectSchema`, `BreadcrumbListSchema`, `ArticleSchema`, `EventSchema`, `LocalBusinessSchema`.
- `Seo\BreadcrumbsController` (+ Interface) + `BreadcrumbItemEntity` — fil d'Ariane multilingue.
- `Seo\SitemapController` (+ Interface) + `SitemapEntryBuilder` + `SitemapIndexBuilder` — sitemap XML multilingue (index + sous-sitemaps par CPT × langue).
- `Seo\ReadabilityAnalyzer` — score Flesch adapté au français (coefficients Kandel & Moles 1958).
- `Seo\KeywordAnalyzer` — densité, présence dans title/H1/slug/premier paragraphe.
- `Seo\InternalLinkSuggester` — suggestions de maillage interne.
- `Seo\ImageAuditor` — détection des `alt` manquants/vides.
- `Seo\ScoreCalculator` — score SEO 0-100, pondération configurable via filtre `oli_seo_score_rules`.
- `Seo\RedirectEntity` + `RedirectModel` (+ Interface) + `RedirectController` — gestion des 301/410 avec table `oli_redirects`.
- `Seo\SeoController` (+ Interface) + `SeoHeadViewModel` — orchestrateur central (`buildForPost/Event/Archive/Search/404`).
- `Seo\Admin\SeoMetabox` — métabox per-post (post/page/oli_event) avec nonce + sanitization + preview SERP live.
- `Seo\Admin\SeoOverviewPage` — dashboard MVP sous `Outils > SEO Dashboard`.
- `Seo\Admin\RedirectsPage` — UI MVP des redirections sous `Outils > Redirections`.
- `Seo\SeoModule` — orchestrateur services + hooks WP (template_redirect, save_post, admin_menu, add_meta_boxes).
- Wire dans `Theme::boot()` + injection `SeoControllerInterface` + `BreadcrumbsControllerInterface` dans 5 controllers (Posts + Events).
- Templates `partials/seo-head.html.tpl` (intégré dans `layouts/base.html.tpl`), `admin/seo-metabox.html.tpl`, `admin/seo-overview.html.tpl`, `admin/redirects.html.tpl`.
- `assets/css/seo-admin.css` + `assets/js/seo-metabox.js` (compteurs live, preview SERP, gauge approx.).
- `AssetManager::enqueueAdmin($hookSuffix)` câble les assets SEO sur `post.php`/`post-new.php`/pages SEO.
- Migration `oli_redirects` via `dbDelta` dans `Theme::onActivation` (hook `after_switch_theme`).
- `TranslationModelInterface` extrait pour le mocking PHPUnit.
- Test d'intégration `SeoE2ETest` (boot + assertions JSON-LD `@graph`).
- Guide `docs/seo.md` + ADR 0008 (SEO custom vs plugins tiers).

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
