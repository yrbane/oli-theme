# Architecture du thème oli-theme

## Vue d'ensemble

Le thème suit un pattern **MVC strict** appliqué à WordPress :

- **Modèles** (`src/*/...Model.php`) — encapsulation de la donnée. Aucun HTML, aucun `echo`. Conversion des `WP_Post` en DTO.
- **Contrôleurs** (`src/*/...Controller.php`) — orchestrent récupération des données et préparation du `ViewModel`. Aucun HTML.
- **Vues** (`templates/**/*.html.tpl`) — templates Lunar uniquement. Aucun appel WP, aucune logique métier.
- **Modules** (`src/*/...Module.php`) — un par domaine fonctionnel (I18n, Posts, Navigation, Slides, Events, SEO, …). Enregistrent les hooks WordPress et les services dans le `Container`.

## Composants Core

| Composant | Rôle |
|-----------|------|
| `OliTheme\Theme` | Bootstrap singleton, accès au conteneur, branche les hooks fondateurs et les modules |
| `OliTheme\Container` | Conteneur de dépendances minimaliste (`set` / `factory` / `get` / `has`) |
| `OliTheme\Core\ViewRenderer` | Wrapper de Lunar Template Engine, expose macros + variables globales |
| `OliTheme\Core\AssetManager` | Enqueue CSS / JS avec versioning `filemtime`, conditionnel par hookSuffix admin |
| `OliTheme\Core\RequestContext` | Wrapper immuable de la requête HTTP |
| `OliTheme\Core\HookRegistrar` | Wrapper testable de `add_action` / `add_filter` |
| `OliTheme\Core\ModuleInterface` | Contrat des modules fonctionnels (`register(): void`) |
| `OliTheme\Core\PostTypeInterface` | Contrat des classes enregistrant un CPT |
| `OliTheme\Core\RendererInterface` | Contrat narrow du rendu de templates (mockable) |

## Modules livrés (cycle 1)

Chaque module est un orchestrateur autonome qui enregistre ses services dans le `Container` et hooke les hooks WordPress nécessaires. L'ordre d'enregistrement (dans `Theme::registerCoreHooks`) compte car les modules en aval dépendent des modules en amont.

| Module | Namespace | Périmètre |
|--------|-----------|-----------|
| `I18nModule` | `OliTheme\I18n` | Taxonomie `language`, registre des langues, resolver, switcher, rewrite rules, filter `home_url`, métabox traductions |
| `NavigationModule` | `OliTheme\Navigation` | Menus `primary_<lang>` / `footer_<lang>`, arbre `MenuItemEntity`, partials `nav-desktop` / `nav-mobile` |
| `SlidesModule` | `OliTheme\Slides` | CPT `oli_slide`, modèle, carousel controller, partial `carousel.html.tpl`, JS d'autoplay/swipe |
| `SeoModule` | `OliTheme\Seo` | Voir section dédiée ci-dessous |
| `EventsModule` | `OliTheme\Events` | CPT `oli_event`, modèle (upcoming/past), 2 controllers (single/archive), métabox |
| `PostsModule` | `OliTheme\Posts` | Modèle générique pages/posts, 3 controllers (page/post/404), pontage WP via `theme-bridge/` |

## Module SEO (Plan 7)

Le plus volumineux des modules — environ 30 classes — implémente un SEO complet sans dépendance plugin.

```
src/Seo/
├── SeoMeta.php / SeoMetaModel.php           DTO + accès meta `_oli_seo_*`
├── SeoController.php                        orchestrateur du <head>
├── SeoHeadViewModel.php                     DTO produit par le controller
├── CanonicalBuilder, HreflangBuilder, RobotsBuilder, OpenGraphBuilder, TwitterCardBuilder
├── BreadcrumbsController.php / BreadcrumbItemEntity.php
├── SitemapController.php / SitemapEntryBuilder / SitemapIndexBuilder
├── ReadabilityAnalyzer.php                  Flesch-FR Kandel & Moles 1958
├── KeywordAnalyzer.php / InternalLinkSuggester / ImageAuditor
├── ScoreCalculator.php                      0-100, configurable via `oli_seo_score_rules`
├── RedirectEntity / RedirectModel / RedirectController / RedirectInstaller
├── Schema/                                  8 schemas JSON-LD agrégés sous @graph
│   └── SchemaInterface, SchemaContext, ArticleSchema, EventSchema, …
├── Admin/SeoMetabox.php                     UI per-post avec preview SERP live
├── Admin/SeoOverviewPage.php                dashboard MVP (Outils > SEO Dashboard)
├── Admin/RedirectsPage.php                  CRUD redirections MVP
└── SeoModule.php                            services + hooks
```

Le `SeoController::buildForPost(...)` (et variantes Event / Archive / Search / 404) produit un `SeoHeadViewModel` qui agrège title, description, robots, canonical, hreflangs, Open Graph, Twitter Card et JSON-LD `@graph`. Le partial `templates/partials/seo-head.html.tpl` matérialise ce view-model dans le `<head>` HTML.

Détails : [`docs/seo.md`](seo.md) et [`docs/decisions/0008-seo-custom.md`](decisions/0008-seo-custom.md).

## Pattern Container et interfaces

PHPUnit 11 ne peut pas mocker les classes `final`. Le projet adopte une convention systématique : pour chaque classe `final` consommée par un autre module via `createMock`, une **interface narrow** est extraite et enregistrée dans le `Container` comme alias :

```php
$container->factory(
    LanguageResolverInterface::class,
    static fn (Container $c): LanguageResolverInterface => $c->get(LanguageResolver::class),
);
```

Interfaces actuellement extraites :

- `Core\RendererInterface`
- `I18n\LanguageRegistryInterface`, `LanguageResolverInterface`, `LanguageSwitcherControllerInterface`, `TranslationModelInterface`
- `Posts\PostModelInterface`
- `Navigation\MenuModelInterface`, `MenuControllerInterface`
- `Slides\SlideModelInterface`, `HomeCarouselControllerInterface`
- `Events\EventModelInterface`, `EventControllerInterface`, `EventArchiveControllerInterface`
- `Seo\SeoMetaModelInterface`, `SeoControllerInterface`, `BreadcrumbsControllerInterface`, `SitemapControllerInterface`, `RedirectModelInterface`

Ce pattern préserve le DIP de SOLID tout en permettant un mocking PHPUnit pur (sans Mockery).

## Flow d'une requête front

```
HTTP /fr/yoga-quotidien
        |
        v
.htaccess --> WordPress (index.php)
        |
        v
RewriteRules custom (I18n) --> ?oli_lang=fr&name=yoga-quotidien
        |
        v
WP_Query résout le post
        |
        v
theme-bridge/single.php (1 ligne)
        |
        v
Theme::container()->get(PostController::class)->renderSingle()
        |
        +-- PostModel::find($id) --> PostEntity
        +-- LanguageResolver::current()
        +-- LanguageSwitcherController::build($id)
        +-- MenuController::buildPrimary($lang) / buildFooter($lang)
        +-- SeoController::buildForPost($entity) --> SeoHeadViewModel
        +-- BreadcrumbsController::buildForPost($entity)
        |
        v
ViewRenderer::render('pages/single-post.html', $viewModel)
        |
        v
Lunar compile et exécute single-post.html.tpl
   (extends layouts/base, qui include partials/seo-head, header, footer)
        |
        v
HTML envoyé au client
```

## Bootstrap

```php
// functions.php
require __DIR__ . '/vendor/autoload.php';
\OliTheme\Theme::boot(__DIR__);
```

`Theme::boot()` (idempotent) :

1. Crée le `Container`.
2. Enregistre les services Core (`ViewRenderer`, `AssetManager`, `RequestContext`, `HookRegistrar`).
3. Enregistre les alias d'interfaces (Renderer, Language*).
4. Hooke `wp_enqueue_scripts`, `admin_enqueue_scripts`, `after_switch_theme`, `switch_theme`.
5. Branche `bootstrapViewRenderer()` (variables globales + macros `wpHead` / `wpFooter`).
6. Instancie tous les modules dans l'ordre :
   ```
   I18nModule → NavigationModule → SlidesModule → SeoModule → EventsModule → PostsModule
   ```

`Theme::onActivation()` (hook `after_switch_theme`) :

- Flush rewrite rules.
- Délègue à `RedirectInstaller::install()` + persiste `oli_theme_db_version` (table `{prefix}oli_redirects`).

L'installer est aussi appelé via `init` priorité 5 par `SeoModule`, ce qui garantit la création de la table même sur les déploiements `git pull` où `after_switch_theme` ne se déclenche pas (cf. issue #3).

## Conventions

- Code en **anglais**, PHPDoc et commentaires en **français**.
- `declare(strict_types=1);` dans tous les fichiers PHP.
- Classes `final` par défaut. DTO `final readonly`.
- Tests TDD systématiques (Red → Green → Refactor → Commit).
- WP funcs dans `src/` : pas de leading backslash (CS-Fixer convention du projet).
- Native PHP funcs : `\` prefix sur les `@compiler_optimized` (sprintf, is_string, is_array). Le reste est laissé à CS-Fixer.
- Convention de fichiers : `src/Domain/Class.php` ↔ `OliTheme\Domain\Class`.
- Commits : Conventional Commits en français, **sans** ligne `Co-Authored-By: Claude`.

## Plans d'implémentation

Le développement suit 10 plans séquentiels (cf. [`docs/superpowers/plans/`](superpowers/plans/)). Chaque plan livre un thème fonctionnel et testable :

1. ✅ **Foundation** — socle MVC, Container, Core, CI
2. ✅ **I18n** — système multilingue custom
3. ✅ **Templates & Posts/Pages** — layout, partials, pages, theme-bridge
4. ✅ **Navigation** — menus par langue, drawer mobile
5. ✅ **Slides & Carousel** — CPT `oli_slide` + carousel JS
6. ✅ **Events** — CPT `oli_event` avec archive
7. ✅ **SEO complet** — head, sitemap, schemas, redirects, score
8. ⏳ **Contact** — formulaire OOP TDD sécurisé
9. ⏳ **Settings** — page d'options (bannière, footer, réseaux)
10. ⏳ **QA / finalisation** — Lighthouse, axe-core, validation W3C, doc utilisateur finale
