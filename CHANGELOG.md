# Changelog

Format basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), versions selon [Semantic Versioning](https://semver.org/lang/fr/).

## [Unreleased]

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
