# Design — Thème WordPress `oli-theme`

**Date :** 2026-05-05
**Auteur :** yrbane (`yrbane@nethttp.net`) — assistance Claude Code
**Statut :** validé en brainstorming, en attente de relecture finale avant écriture du plan d'implémentation
**Cible :** Priorité 1 de la spec d'origine (`docs/specs-theme-wordpress-sites.md`) + gestion des événements (Priorité 2 partielle)

**Versions cibles :**

- **PHP** : `^8.3` minimum (contrainte Lunar Template), testé jusqu'à 8.5 (matrice CI : 8.3, 8.4, 8.5). Environnement de référence du commanditaire = PHP 8.5.4.
- **WordPress** : 6.9+ (testé sur 6.9.4).

---

## 0. Contexte et périmètre

### 0.1 Sites concernés

Thème **réutilisable multi-sites** pour :

- `olikalari.com`
- `satsangham.com`
- `olivier.durillon.com`
- éventuellement `margeye.com`

Le thème est **identique** sur tous les sites ; les différences (logo, couleurs, contenus, langues activées, menus) sont portées par les options WP et les contenus de chaque instance.

### 0.2 Périmètre du présent design

Cycle 1 = **Priorité 1 spec + Événements** :

- Thème custom stable (PHP `^8.3`, WP 6.9+ — voir « Versions cibles » ci-dessus)
- Header permanent avec bannière responsive
- Menu principal avec sous-menus (desktop hover, mobile drawer)
- Responsive complet (desktop / tablette / mobile)
- Pages administrables
- Multilingue propre (URLs `/fr/`, `/en/`, `/it/`...) — système custom léger
- Galerie d'accueil défilante (carrousel custom)
- Formulaire de contact
- SEO de base ambitieux (objectif "mieux que Yoast")
- Gestion des événements (CPT `oli_event`)

**Hors périmètre** (cycles ultérieurs) :

- Galerie photo avancée et galerie vidéo dédiées
- Agenda interactif / réservation / paiement en ligne
- Watermark PDF, protection avancée des contenus
- Publication automatique vers réseaux sociaux

### 0.3 Décisions architecturales arbitrées (cf. spec section 7)

| # | Décision | Choix |
|---|----------|-------|
| 7.1 | Système multilingue | **Custom léger dans le thème** (taxonomie `language` + meta `_oli_translation_group` + rewrite rules custom) |
| 7.2 | Rôle des posts | **Conservés** pour actualités/blog/SEO |
| 7.3 | Gestion des événements | **CPT `oli_event` dédié** |
| Moteur de templates | **Lunar Template Engine** (`yrbane/lunar-template`) | |
| CSS/JS | **Vanilla, sans build** (modulaire, BEM, ES modules) | |
| Formulaire de contact | **Custom OOP TDD** (zéro plugin) | |
| Module SEO | **Custom intégré** (objectif dépasser Yoast) | |
| Carrousel | **Custom vanilla JS** + CPT `oli_slide` | |
| Stack tests | **PHPUnit 11 + Brain Monkey** | |
| Quality | **PHPStan niveau 8** + **PHP-CS-Fixer (PSR-12)** | |
| Slug / namespace | **`oli-theme`** / **`OliTheme\`** / text-domain `oli-theme` | |
| Bannière | **Page Settings custom MVC** | |
| Footer | 3 sections paramétrables (mentions légales / réseaux / mini-menu) | |

### 0.4 Principes de codage (rappel)

- **TDD** strict (Red → Green → Refactor)
- **SOLID** appliqué à tous les modules
- **DRY** : pas de duplication, modèles génériques
- **KISS** : pas d'abstraction prématurée, classes courtes
- **Code en anglais**, **commentaires et commits en français**

---

## 1. Architecture globale

### 1.1 Pattern MVC adapté à WordPress

WordPress n'est pas un framework MVC. Le pattern est imposé par discipline :

- **Models** : encapsulent la donnée (post types, meta, options, requêtes WP_Query). Aucun `echo`, aucun HTML.
- **Controllers** : orchestrent (récupèrent données via Models, préparent un `ViewModel`, appellent le Renderer). Aucun HTML.
- **Views** : templates Lunar `.html.tpl`. Aucun appel WP, aucune logique métier — uniquement affichage.
- **Modules** : chaque domaine fonctionnel (SEO, Contact, Slide, Event, Settings...) a une classe `*Module` qui enregistre ses hooks WP et instancie ses controllers à la demande.

### 1.2 Arborescence du thème

```
oli-theme/
├── style.css                       # Header WP obligatoire
├── functions.php                   # Bootstrap minimal: autoload + Theme::boot()
├── composer.json                   # Lunar, PHPUnit, Brain Monkey, PHPStan, CS-Fixer
├── composer.lock
├── phpunit.xml.dist
├── phpstan.neon
├── .php-cs-fixer.dist.php
├── README.md
│
├── src/                            # Code OOP, namespace OliTheme\
│   ├── Theme.php                   # Singleton bootstrap (registre des modules)
│   ├── Container.php               # Mini DI container (PSR-11 like)
│   │
│   ├── Core/                       # Socle réutilisable
│   │   ├── ViewRenderer.php        # Wrapper Lunar (escape, helpers WP)
│   │   ├── AssetManager.php        # Enqueue CSS/JS (versioning auto via filemtime)
│   │   ├── HookRegistrar.php       # Helper pour bind hooks proprement
│   │   ├── RequestContext.php      # Wrapper de la requête HTTP courante (testable)
│   │   ├── ModuleInterface.php
│   │   └── PostTypeInterface.php
│   │
│   ├── I18n/                       # Multilingue custom
│   │   ├── Language.php            # Value Object immuable
│   │   ├── LanguageRegistry.php
│   │   ├── LanguageResolver.php    # Détection langue depuis URL/cookie/header
│   │   ├── TranslationModel.php    # Groupes de traduction
│   │   ├── RewriteRules.php
│   │   ├── LanguageUrlFilter.php   # Filtre home_url, get_permalink
│   │   ├── LanguageSwitcherController.php
│   │   ├── LanguageTaxonomy.php
│   │   ├── LanguageMetabox.php     # UI admin "Traductions"
│   │   └── I18nModule.php
│   │
│   ├── Posts/
│   │   ├── PostEntity.php          # DTO immuable
│   │   ├── PostModel.php           # Modèle générique pages/posts
│   │   ├── PageController.php
│   │   ├── PostController.php
│   │   └── PostsModule.php
│   │
│   ├── Events/
│   │   ├── EventEntity.php
│   │   ├── EventCpt.php            # Enregistrement CPT
│   │   ├── EventModel.php
│   │   ├── EventController.php
│   │   ├── EventArchiveController.php
│   │   ├── EventMetabox.php        # Date début/fin, lieu, prix, URL inscription
│   │   └── EventsModule.php
│   │
│   ├── Slides/
│   │   ├── SlideEntity.php
│   │   ├── SlideCpt.php
│   │   ├── SlideModel.php
│   │   ├── HomeCarouselController.php
│   │   └── SlidesModule.php
│   │
│   ├── Navigation/
│   │   ├── MenuItemEntity.php
│   │   ├── MenuModel.php           # Construit l'arborescence DTO immuable
│   │   ├── MenuWalker.php          # Walker custom (sous-menus, classes BEM)
│   │   ├── MenuController.php
│   │   └── NavigationModule.php
│   │
│   ├── Seo/
│   │   ├── SeoMetaModel.php
│   │   ├── SeoController.php       # <head> : title, meta, OG, JSON-LD
│   │   ├── SitemapController.php
│   │   ├── BreadcrumbsController.php
│   │   ├── HreflangBuilder.php
│   │   ├── CanonicalBuilder.php
│   │   ├── ReadabilityAnalyzer.php # Flesch-FR
│   │   ├── ScoreCalculator.php
│   │   ├── KeywordAnalyzer.php
│   │   ├── InternalLinkSuggester.php
│   │   ├── ImageAuditor.php
│   │   ├── RedirectModel.php
│   │   ├── RedirectController.php
│   │   ├── Admin/
│   │   │   ├── SeoMetabox.php
│   │   │   ├── SeoOverviewPage.php
│   │   │   └── RedirectsPage.php
│   │   ├── Schema/                 # JSON-LD builders SOLID
│   │   │   ├── SchemaInterface.php
│   │   │   ├── SchemaContext.php   # Aggrégateur multi-schemas (@graph)
│   │   │   ├── ArticleSchema.php
│   │   │   ├── EventSchema.php
│   │   │   ├── PersonSchema.php
│   │   │   ├── OrganizationSchema.php
│   │   │   ├── WebSiteSchema.php
│   │   │   ├── BreadcrumbListSchema.php
│   │   │   ├── LocalBusinessSchema.php
│   │   │   └── ImageObjectSchema.php
│   │   └── SeoModule.php
│   │
│   ├── Contact/
│   │   ├── ContactFormModel.php    # Validation, sanitization
│   │   ├── ContactFormController.php
│   │   ├── ContactMailer.php
│   │   ├── ContactRateLimiter.php
│   │   ├── ContactLogModel.php     # Optionnel: CPT oli_contact_log
│   │   └── ContactModule.php
│   │
│   └── Settings/
│       ├── ThemeSettingsModel.php
│       ├── SettingsBag.php         # DTO immuable
│       ├── BannerSettings.php
│       ├── FooterSettings.php
│       ├── SocialSettings.php
│       ├── LanguagesSettings.php
│       ├── ThemeSettingsPage.php   # Admin menu OOP
│       └── SettingsModule.php
│
├── templates/                      # Lunar .html.tpl — VUES PURES
│   ├── layouts/
│   │   ├── base.html.tpl
│   │   └── empty.html.tpl
│   ├── partials/
│   │   ├── header.html.tpl
│   │   ├── banner.html.tpl
│   │   ├── nav-desktop.html.tpl
│   │   ├── nav-mobile.html.tpl
│   │   ├── language-switcher.html.tpl
│   │   ├── footer.html.tpl
│   │   ├── breadcrumbs.html.tpl
│   │   ├── carousel.html.tpl
│   │   ├── event-card.html.tpl
│   │   ├── seo-head.html.tpl
│   │   └── contact-form.html.tpl
│   ├── pages/
│   │   ├── front-page.html.tpl
│   │   ├── page.html.tpl
│   │   ├── single-post.html.tpl
│   │   ├── single-event.html.tpl
│   │   ├── archive-event.html.tpl
│   │   ├── archive-post.html.tpl
│   │   ├── search.html.tpl
│   │   └── 404.html.tpl
│   └── admin/
│       ├── settings-page.html.tpl
│       ├── seo-metabox.html.tpl
│       ├── event-metabox.html.tpl
│       └── language-metabox.html.tpl
│
├── assets/                         # Vanilla, sans build
│   ├── css/
│   │   ├── reset.css
│   │   ├── tokens.css              # Variables CSS (couleurs, espacements, breakpoints)
│   │   ├── base.css
│   │   ├── header.css
│   │   ├── menu.css
│   │   ├── carousel.css
│   │   ├── event.css
│   │   ├── contact.css
│   │   ├── footer.css
│   │   ├── admin.css
│   │   └── main.css                # @import des modules
│   ├── js/
│   │   ├── carousel.js             # ES module
│   │   ├── menu-mobile.js
│   │   ├── contact-form.js
│   │   ├── lazy-images.js
│   │   ├── seo-metabox.js          # UI temps réel admin SEO
│   │   └── main.js
│   ├── images/
│   └── fonts/
│
├── tests/                          # PHPUnit + Brain Monkey
│   ├── bootstrap.php
│   ├── helpers/
│   ├── Unit/
│   └── Integration/
│
├── languages/                      # .po / .mo (text domain oli-theme)
│   ├── oli-theme.pot
│   ├── oli-theme-fr_FR.po
│   └── oli-theme-en_US.po
│
├── docs/                           # Documentation projet
│   ├── architecture.md
│   ├── installation.md
│   ├── multilingue.md
│   ├── content-types.md
│   ├── seo.md
│   ├── theme-options.md
│   ├── developer.md
│   ├── testing.md
│   ├── decisions/                  # ADRs
│   └── user-guide/                 # Guides utilisateur final
│
└── theme-bridge/                   # Templates WP de pontage minimaux
    ├── index.php
    ├── single.php
    ├── single-oli_event.php
    ├── page.php
    ├── front-page.php
    ├── archive.php
    ├── archive-oli_event.php
    ├── search.php
    └── 404.php
```

### 1.3 Bootstrap (`functions.php`)

```php
<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

\OliTheme\Theme::boot(__DIR__);
```

`Theme::boot()` :

1. Crée le `Container` (DI minimaliste)
2. Enregistre les services partagés (`ViewRenderer`, `AssetManager`, `LanguageResolver`, `RequestContext`)
3. Charge tous les `*Module`
4. Chaque module fait son `register()` (hooks WP)

### 1.4 Pattern de chaque module

```php
final class EventsModule implements ModuleInterface
{
    public function __construct(private Container $container) {}

    public function register(): void {
        add_action('init', [$this, 'registerCpt']);
        add_action('init', [$this, 'registerMetaboxes']);
        add_filter('template_include', [$this, 'resolveTemplate']);
    }

    public function registerCpt(): void {
        $this->container->get(EventCpt::class)->register();
    }
    // ...
}
```

### 1.5 Documentation et commentaires

**PHPDoc systématique en français :**

- Toutes classes, propriétés et méthodes publiques/protégées : description, `@param`, `@return`, `@throws`, `@since`, `@example`
- PHPDoc minimal sur méthodes privées
- Header de fichier sur chaque `.php` : description, `@package OliTheme`
- Header sur chaque template `.tpl` : variables attendues, blocs disponibles
- Header sur chaque CSS/JS : description, dépendances, classes BEM exposées

**Commentaires inline (français) :**

- Le **pourquoi** (intentions, contraintes WP, contournements)
- Sections logiques pour la transmissibilité
- Logique non-évidente (rewrite rules, hooks avec priorités)

**Documentation projet (`docs/`) :**

- `architecture.md`, `installation.md`, `multilingue.md`, `content-types.md`, `seo.md`, `theme-options.md`, `developer.md`, `testing.md`
- `decisions/` : ADRs (un fichier par décision architecturale majeure)
- `user-guide/` : guides utilisateur final (livrables spec section 9.2)
- `api/` : documentation auto-générée par phpDocumentor (`composer docs`)

---

## 2. Modèles, CPT et système multilingue

### 2.1 Multilingue custom (`OliTheme\I18n\`)

**Stockage :**

- **Taxonomie `language`** (non hiérarchique) attachée à `page`, `post`, `oli_event`, `oli_slide`. Termes : `fr`, `en`, `it`, `es`...
- **Post meta `_oli_translation_group`** = UUID partagé entre toutes les traductions d'un même contenu
- **Option `oli_languages`** = liste des langues activées + langue par défaut (réglée dans Settings)

**URL et routing :**

- Rewrite rules custom : `^(fr|en|it|es)/(.+)?` injecte `?oli_lang=fr` dans la query
- Hook `request` filter : pas de préfixe → langue par défaut
- `home_url()` / `get_permalink()` filtrés via `LanguageUrlFilter`
- Pages d'accueil par langue : `/fr/`, `/en/`, `/it/`

**Classes principales :**

```php
final readonly class Language {
    public function __construct(
        public string $code,
        public string $label,
        public string $nativeLabel,
        public string $flag,
        public string $locale,
        public string $direction,
    ) {}
}

final class LanguageRegistry {
    /** @return Language[] */
    public function all(): array;
    public function default(): Language;
    public function get(string $code): ?Language;
    public function isEnabled(string $code): bool;
}

final class LanguageResolver {
    public function __construct(
        private LanguageRegistry $registry,
        private RequestContext $request,
    ) {}
    public function resolve(): Language;     // URL > cookie > Accept-Language > default
    public function current(): Language;     // mémoïsé
}

final class TranslationModel {
    public function getGroupId(int $postId): ?string;
    public function setGroupId(int $postId, string $groupId): void;
    /** @return array<string,int> code langue → post ID */
    public function getTranslations(int $postId): array;
    public function link(int $sourcePostId, int $targetPostId): void;
    public function unlink(int $postId): void;
}
```

**Switcher de langue — comportement :**

1. Sur `/fr/cours-hebdomadaires`, clic sur `EN`
2. `LanguageSwitcherController` lit le `translation_group_id`
3. Traduction EN existe → redirige vers `/en/weekly-classes`
4. Pas de traduction → comportement de fallback configurable :
   - redirection home EN (par défaut)
   - affichage du contenu source avec bannière "version EN à venir"
   - message d'absence de traduction

**Metabox admin "Traductions" :**

- Sur l'écran d'édition de chaque page/post/event/slide
- Liste les langues activées
- Pour chaque langue ≠ courante : "Voir / Créer la version XX"
- Possibilité de **lier manuellement** un post existant à un groupe de traduction

### 2.2 Modèles Posts/Pages (`OliTheme\Posts\`)

`PostModel` est **générique** pour pages et posts standards (pas de duplication) :

```php
final class PostModel {
    public function __construct(
        private TranslationModel $translation,
        private LanguageResolver $lang,
    ) {}

    public function find(int $id): ?PostEntity;
    public function findBySlug(string $slug, Language $lang): ?PostEntity;
    /** @return PostEntity[] */
    public function findByLanguage(Language $lang, int $limit = 10): array;
    public function getMeta(int $id, string $key, mixed $default = null): mixed;
}
```

`PostEntity` = DTO immuable (`id`, `title`, `content`, `excerpt`, `slug`, `language`, `featuredImageUrl`, `permalink`, `publishedAt`, `author`, `seoMeta`).

### 2.3 CPT `oli_event`

```php
final class EventCpt implements PostTypeInterface {
    public function register(): void;
    // post_type: 'oli_event'
    // slug: configurable par langue (/fr/evenements/, /en/events/)
    // supports: title, editor, thumbnail, excerpt, custom-fields
    // taxonomies: language, oli_event_category (optionnelle)
    // hierarchical: false, has_archive: true, show_in_rest: true
}

final readonly class EventEntity {
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public ?string $excerpt,
        public DateTimeImmutable $startDate,
        public ?DateTimeImmutable $endDate,
        public ?string $location,
        public ?string $address,
        public ?string $flyerUrl,
        public ?string $registrationUrl,
        public ?string $price,
        public Language $language,
        public string $permalink,
        public bool $isPast,
        public bool $isOngoing,
    ) {}
}

final class EventModel {
    public function find(int $id): ?EventEntity;
    /** @return EventEntity[] */
    public function findUpcoming(Language $lang, int $limit = 10): array;
    /** @return EventEntity[] */
    public function findPast(Language $lang, int $limit = 10): array;
    public function findBySlug(string $slug, Language $lang): ?EventEntity;
}
```

**Métaboxe admin** custom (pas d'ACF) : `EventMetabox` rend `admin/event-metabox.html.tpl` et sauve via hook `save_post`.

### 2.4 CPT `oli_slide`

```php
final class SlideCpt implements PostTypeInterface {
    // post_type: 'oli_slide'
    // supports: title, thumbnail, excerpt
    // taxonomies: language
    // public: false, show_ui: true → invisible côté front sauf via le carrousel
}

final readonly class SlideEntity {
    public function __construct(
        public int $id,
        public string $title,
        public ?string $caption,
        public string $imageUrl,
        public ?string $imageAlt,
        public ?string $linkUrl,
        public ?string $linkLabel,
        public int $order,
        public ?DateTimeImmutable $expiresAt,
        public Language $language,
    ) {}
}

final class SlideModel {
    public function find(int $id): ?SlideEntity;
    /** @return SlideEntity[] */
    public function findActive(Language $lang): array;  // exclut expirés, ordonnés par menu_order
}
```

### 2.5 Diagramme du flow

```
HTTP GET /fr/evenements/stage-printemps
        │
        ▼
WordPress (.htaccess → index.php)
        │
        ▼
RewriteRules → ?oli_lang=fr&post_type=oli_event&name=stage-printemps
        │
        ▼
WP_Query → résolution post
        │
        ▼
single-oli_event.php (theme-bridge)
        │
        ▼
EventController::renderSingle()
        ├── LanguageResolver::current() → fr
        ├── EventModel::find($post->ID) → EventEntity
        ├── SeoController::buildHead(EventEntity)
        ├── BreadcrumbsController::build()
        ├── LanguageSwitcherController::build()
        └── compose ViewModel
        ▼
ViewRenderer::render('pages/single-event', $viewModel)
        ▼
Lunar compile single-event.html.tpl (extends layouts/base)
        ▼
HTML envoyé
```

### 2.6 SOLID

- **SRP** : `LanguageResolver` ne fait que résoudre, `RewriteRules` ne fait qu'enregistrer, `TranslationModel` ne fait que gérer les groupes
- **OCP** : ajouter une langue = ajouter une entrée dans `oli_languages` (zéro code)
- **LSP** : tous les `*Module` implémentent `ModuleInterface`
- **ISP** : `PostTypeInterface` (registerCpt) et `ModuleInterface` (register) distincts
- **DIP** : controllers dépendent des models, jamais de WP_Post directement

---

## 3. Templates Lunar, vues et assets vanilla

### 3.1 Hiérarchie d'héritage Lunar

`templates/layouts/base.html.tpl` définit le squelette HTML avec blocs surchargeables : `head_extra`, `banner`, `before_main`, `main`, `after_main`, `footer_extra`.

Chaque page `templates/pages/*.html.tpl` étend `layouts/base.html.tpl` et remplit le bloc `main`.

**Squelette `layouts/base.html.tpl`** :

```html
[# Layout racine du thème oli-theme.
   Variables attendues:
     - lang (Language)
     - seo (SeoHeadViewModel)
     - bodyClasses (string)
   Blocs surchargeables: head_extra, banner, before_main, main, after_main, footer_extra
#]
<!DOCTYPE html>
<html lang="[[ lang.code ]]" dir="[[ lang.direction ]]">
<head>
    <meta charset="[[ charset ]]">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    [% include 'partials/seo-head.html.tpl' %]
    [[! wpHead !]]
    [% block head_extra %][% endblock %]
</head>
<body class="[[ bodyClasses ]]">
    [% block banner %]
        [% include 'partials/header.html.tpl' %]
    [% endblock %]
    [% block before_main %][% endblock %]
    <main id="main" class="site-main">
        [% block main %][% endblock %]
    </main>
    [% block after_main %][% endblock %]
    [% include 'partials/footer.html.tpl' %]
    [[! wpFooter !]]
    [% block footer_extra %][% endblock %]
</body>
</html>
```

**Exemple de page (extrait `pages/single-event.html.tpl`)** :

```html
[% extends 'layouts/base.html.tpl' %]
[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <article class="event event--single" itemscope itemtype="https://schema.org/Event">
        <header class="event__header">
            <h1 class="event__title" itemprop="name">[[ event.title ]]</h1>
            <p class="event__date">
                <time datetime="[[ event.startIso ]]" itemprop="startDate">
                    [[ event.startFormatted ]]
                </time>
            </p>
        </header>
        <div class="event__content" itemprop="description">[[! event.description !]]</div>
        [% if event.registrationUrl %]
            <a class="btn btn--primary event__cta" href="[[ event.registrationUrl ]]">
                [[ i18n.event_register ]]
            </a>
        [% endif %]
    </article>
[% endblock %]
```

### 3.2 Contrat de vue

**Règle stricte** : un template ne reçoit que des **scalaires**, **arrays**, ou **DTO read-only**. Jamais d'objets WordPress.

`ViewRenderer` injecte automatiquement des variables globales : `wpHead`, `wpFooter`, `lang`, `i18n`, `siteName`, `siteUrl`, `homeUrl`, `currentYear`, `themeUri`, `bodyClasses`.

**Helpers Lunar (macros) :**

- `##asset(path)##` → URL d'asset
- `##translateUrl(postId, lang)##` → URL de traduction
- `##formatDate(iso, format)##` → date localisée
- `##excerpt(text, words)##` → extrait

### 3.3 Template Resolver (pontage WP → Lunar)

Chaque fichier `theme-bridge/*.php` fait **1 ligne d'appel** au controller correspondant — toute la logique vit dans `src/`.

| Pont WP | Lunar template | Controller |
|---------|----------------|------------|
| `front-page.php` | `pages/front-page.html.tpl` | `HomeController` |
| `page.php` | `pages/page.html.tpl` | `PageController` |
| `single.php` | `pages/single-post.html.tpl` | `PostController` |
| `single-oli_event.php` | `pages/single-event.html.tpl` | `EventController` |
| `archive-oli_event.php` | `pages/archive-event.html.tpl` | `EventArchiveController` |
| `archive.php` | `pages/archive-post.html.tpl` | `PostController` |
| `search.php` | `pages/search.html.tpl` | `SearchController` |
| `404.php` | `pages/404.html.tpl` | `NotFoundController` |

### 3.4 Partials et composants

| Partial | Variables | Utilisé par |
|---------|-----------|-------------|
| `header.html.tpl` | `banner`, `nav`, `languageSwitcher` | `layouts/base` |
| `banner.html.tpl` | `banner.imageUrl`, `banner.imageAlt`, `banner.homeUrl` | `header` |
| `nav-desktop.html.tpl` | `menu.items[]` | `header` |
| `nav-mobile.html.tpl` | `menu.items[]` | `header` |
| `language-switcher.html.tpl` | `languages[]`, `current` | `header` |
| `footer.html.tpl` | `footer.legal`, `footer.social`, `footer.menu` | `layouts/base` |
| `breadcrumbs.html.tpl` | `crumbs[]` | pages internes |
| `carousel.html.tpl` | `slides[]` | `front-page` |
| `event-card.html.tpl` | `event` | `archive-event`, listings |
| `seo-head.html.tpl` | `seo.title`, `seo.description`, `seo.openGraph`, `seo.jsonLd`, `seo.hreflangs` | `layouts/base` |
| `contact-form.html.tpl` | `form.fields[]`, `form.errors`, `form.success`, `form.nonce` | page contact |

### 3.5 CSS (vanilla, BEM)

**`tokens.css`** définit les design tokens (variables CSS) :

- Couleurs (`--color-primary`, `--color-secondary`, `--color-text`...)
- Typographies (`--font-sans`, `--font-serif`, `--font-size-base`)
- Espacements (`--space-1` à `--space-7`, échelle 4px)
- Layout (`--container-max`, `--container-narrow`)
- Transitions (`--transition-fast`, `--transition-base`)

**`main.css`** charge tous les modules via `@import`.

**Convention BEM** :

```css
.event {}                /* Block */
.event__title {}         /* Element */
.event--single {}        /* Modifier */
.event--past {}
```

**Responsive** : media queries dans chaque module CSS (proximité au composant). Breakpoints de référence : `480px`, `768px`, `1024px`, `1280px`.

**A11y** : `prefers-reduced-motion` respecté, focus visible (`:focus-visible`), contrastes WCAG AA (≥ 4.5:1).

### 3.6 JavaScript (vanilla, ES modules)

`main.js` est le point d'entrée. Auto-init basé sur la présence d'attributs `data-*` :

```js
import { initCarousel } from './carousel.js';
import { initMobileMenu } from './menu-mobile.js';
import { initContactForm } from './contact-form.js';
import { initLazyImages } from './lazy-images.js';

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-carousel]')) initCarousel();
    if (document.querySelector('[data-mobile-menu]')) initMobileMenu();
    if (document.querySelector('[data-contact-form]')) initContactForm();
    initLazyImages();
});
```

**Progressive enhancement** :

- Formulaire de contact fonctionne **sans JS** (POST classique → redirect)
- Carrousel sans JS → première slide statique visible
- Menu mobile sans JS → fallback `<details>`/`<summary>` natifs

### 3.7 Enqueue des assets (`AssetManager`)

```php
final class AssetManager {
    public function enqueueFront(): void {
        wp_enqueue_style(
            'oli-theme',
            $this->themeUri . '/assets/css/main.css',
            [],
            $this->version('assets/css/main.css'),
        );
        wp_enqueue_script_module(
            'oli-theme',
            $this->themeUri . '/assets/js/main.js',
            [],
            $this->version('assets/js/main.js'),
        );
    }

    private function version(string $relativePath): string {
        $absolute = $this->themePath . '/' . $relativePath;
        return file_exists($absolute) ? (string) filemtime($absolute) : '1.0.0';
    }
}
```

### 3.8 Performance et accessibilité

- Images lazy via `loading="lazy"` natif
- Pas de Google Fonts par défaut (privacy + perf)
- Pas de jQuery
- HTML sémantique strict (`<main>`, `<nav>`, `<article>`)
- Skip links
- ARIA uniquement quand nécessaire
- Navigation clavier complète

---

## 4. Modules fonctionnels

### 4.1 Module SEO (objectif "mieux que Yoast")

**Données par contenu (post meta `_oli_seo_*`)** : `title`, `description`, `focus_keyword`, `additional_keywords[]`, `og_image_id`, `twitter_card_type`, `noindex`, `nofollow`, `canonical`, `priority`, `changefreq`, `readability_score`, `seo_score`.

**Architecture** : voir arborescence `src/Seo/` ci-dessus.

**Composition du `<head>`** :

- `<title>` (override possible per-post)
- `<meta name="description">`
- `<link rel="canonical">`
- `<link rel="alternate" hreflang>` pour toutes les langues + `x-default`
- `<meta name="robots">`
- Open Graph complet (`og:type`, `og:locale`, `og:title`, `og:description`, `og:url`, `og:site_name`, `og:image[:width|:height]`, `article:published_time`, `article:modified_time`, `article:author`)
- Twitter Card (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`)
- **JSON-LD agrégé** dans un seul `<script type="application/ld+json">` avec `@graph` multi-schemas

**Sitemap.xml multilingue** : index pointant vers `/sitemap-{type}-{lang}.xml`, avec `xhtml:link rel="alternate" hreflang>` dans chaque entrée. Cache disque, régénéré sur `save_post`/`delete_post`.

**Score SEO 0-100** calculé par `ScoreCalculator` selon checklist pondérée — **configurable** via `apply_filters('oli_seo_score_rules', $rules)`.

| Critère | Poids | Vérification |
|---------|-------|--------------|
| Title : 30-65 caractères | 5 | `mb_strlen` |
| Title contient le focus keyword | 8 | `stripos` |
| Description : 120-158 caractères | 5 | `mb_strlen` |
| Description contient le focus keyword | 6 | `stripos` |
| URL slug : ≤ 5 mots, contient keyword | 5 | parse |
| Au moins un H1 unique | 4 | DOM parse |
| H2/H3 contiennent keyword au moins une fois | 5 | DOM parse |
| Densité keyword 0.5%-2.5% | 6 | calcul |
| Présence keyword dans premier paragraphe | 4 | DOM parse |
| Toutes images ont un `alt` | 6 | DOM parse |
| Au moins une image avec keyword dans `alt` | 4 | DOM parse |
| Au moins un lien interne | 5 | parse `<a>` |
| Au moins un lien externe (autorité) | 3 | parse `<a>` |
| Pas de lien cassé interne | 5 | requête HEAD asynchrone |
| Score Flesch ≥ 60 (lisible grand public FR) | 8 | `ReadabilityAnalyzer` |
| Longueur contenu ≥ 300 mots | 6 | `str_word_count` |
| OG image définie et ≥ 1200×630 | 5 | check meta + dimensions |
| Canonical défini | 3 | check |
| Meta robots cohérent (pas de noindex involontaire) | 3 | check |
| JSON-LD valide pour le type de contenu | 5 | self-validate |
| Pas de duplication exacte de title/description ailleurs | 4 | requête DB |
| **Total** | **100** | |

**UI admin (metabox SEO per-post)** :

- Compteurs caractères temps réel (vert/jaune/rouge)
- Aperçu Google SERP simulé
- Aperçu social card (OG)
- Score SEO en gauge + critères passés/échoués cliquables
- Score lisibilité Flesch-FR + suggestions concrètes
- Suggestions de maillage interne
- Audit images (liste sans alt)

**Page admin "SEO Dashboard"** : tableau de tous les contenus avec scores, filtres, export CSV.

**Avantages sur Yoast** : multilingue natif intégré, JSON-LD `@graph` complet, Flesch-FR avec coefficients linguistiques validés (Kandel & Moles), maillage interne intelligent, audit images, gestionnaire de redirections 301/410 intégré, pas de pubs/upsell.

### 4.2 Module Contact

**Champs par défaut** (configurables) : `name` (2-100), `email` (RFC), `subject` (max 150, optionnel), `message` (10-5000), `honeypot` (caché), `_oli_nonce`, `_oli_timestamp`.

**Pipeline** :

1. Réception (`admin_post_oli_contact`)
2. Vérification nonce (CSRF)
3. Honeypot vide ?
4. Timestamp ≥ 3s ?
5. Rate limit IP (transient, 3 / 15 min)
6. Validation
7. Sanitization
8. Envoi via `ContactMailer` (`wp_mail`, reply-to = expéditeur)
9. Auto-réponse optionnelle
10. Logging optionnel (CPT `oli_contact_log`, expirable)
11. Redirection (ou JSON si fetch)

**Sécurité** : CSRF + honeypot + time-trap + rate limit + sanitization stricte.

**Multilingue** : labels et erreurs via `__()` text-domain `oli-theme`.

### 4.3 Module Settings

**Page admin** : `Apparence > Identité du site` (slug `oli-theme-settings`).

**Sections (onglets)** :

1. **Identité visuelle** : logo/bannière desktop, bannière mobile, alt par langue, favicon, palette de couleurs
2. **Langues** : liste activées (drag-drop ordre), langue par défaut, comportement fallback
3. **Réseaux sociaux** : Facebook, Instagram, YouTube, + extensible
4. **Footer** : mentions légales (par langue), copyright avec `{year}`, activation des 3 sections
5. **Contact** : email destinataire (par langue), email d'expédition, auto-réponse, page de remerciement, logging
6. **SEO global** : image OG par défaut, Twitter handle, Organisation Schema.org, sitemap on/off, robots.txt custom
7. **Performance** (optionnel futur) : concaténation CSS

**Stockage** : option WP unique `oli_theme_settings` (array sérialisé).

```php
final class ThemeSettingsModel {
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): bool;
    public function all(): SettingsBag;
}

final readonly class SettingsBag {
    public function banner(): BannerSettings;
    public function footer(): FooterSettings;
    public function social(): SocialSettings;
    public function languages(): LanguagesSettings;
}
```

### 4.4 Module Navigation

**Menus WordPress natifs** : un menu primaire et un menu footer **par langue activée** (`primary_fr`, `primary_en`, `footer_fr`...).

`MenuController::buildPrimary(Language $lang)` :

1. `wp_get_nav_menu_object` pour la langue
2. `wp_get_nav_menu_items()` → liste plate
3. `MenuModel::toTree($items)` → arborescence DTO immuable
4. Construit `NavigationViewModel` (current item, expandable, depth)

```php
final readonly class MenuItemEntity {
    public function __construct(
        public int $id,
        public string $label,
        public string $url,
        public string $target,
        public bool $isCurrent,
        public bool $isAncestor,
        public int $depth,
        /** @var MenuItemEntity[] */
        public array $children,
    ) {}
}
```

**Comportement** :

- Desktop : sous-menus au hover ET au focus clavier (CSS `:hover, :focus-within`)
- Mobile : drawer plein écran, accordéons, transitions JS
- Clavier : Tab/Escape/Arrows (ARIA menubar)

### 4.5 Module HomeCarousel

`HomeCarouselController::build()` :

1. Langue courante via `LanguageResolver`
2. `SlideModel::findActive($lang)` (non expirés, ordonnés)
3. Construit `CarouselViewModel` avec `imageSrcset`, `imageSizes` responsive

**Comportement front (`carousel.js`)** :

- Autoplay 5s configurable (`data-autoplay`)
- Pause au hover, focus, onglet caché
- Respect `prefers-reduced-motion`
- Boutons prev/next + dots
- Swipe tactile (Pointer Events)
- Loop ou non configurable

### 4.6 Stratégie multi-sites

- **Code identique** sur les 4 sites
- Différences = options WP + contenus + médias + menus + langues activées
- Une instance WP par site (pas multisite WP)
- Mise à jour = `git pull` ou déploiement scripté
- Personnalisation visuelle sans code via `Settings > Identité visuelle` (couleurs, polices uploadables)

---

## 5. Tests, qualité et livraison

### 5.1 Organisation

```
tests/
├── bootstrap.php
├── helpers/
│   ├── PostFactory.php
│   ├── LanguageFixtures.php
│   ├── EventFixtures.php
│   └── HtmlAssertions.php
├── Unit/
│   ├── Core/
│   ├── I18n/
│   ├── Posts/
│   ├── Events/
│   ├── Slides/
│   ├── Navigation/
│   ├── Seo/
│   ├── Contact/
│   └── Settings/
└── Integration/
    ├── ActivationTest.php
    ├── EndToEndRequestTest.php
    └── SitemapGenerationTest.php
```

### 5.2 Stratégie TDD

Red → Green → Refactor strict. Chaque classe est précédée de son test. Conventions :

- Méthodes nommées `it_should_*` (lisible)
- Sections **Arrange / Act / Assert**
- Variables explicites
- `@dataProvider` pour cas multiples
- Mocks Brain Monkey pour fonctions WP
- Pas d'assertions sur messages traduits (utiliser des clés)

### 5.3 PHPStan (`phpstan.neon`)

```neon
parameters:
    level: 8
    paths: [src, tests]
    bootstrapFiles: [tests/bootstrap-phpstan.php]
    excludePaths: [vendor]
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
    treatPhpDocTypesAsCertain: true
```

`php-stubs/wordpress-stubs` en `require-dev` pour les types WP.

### 5.4 PHP-CS-Fixer

- `@PSR12`
- `@PHP83Migration:risky`
- `declare_strict_types`: true
- `final_class`: true (sauf tests / `@extensible`)
- `ordered_imports`, `ordered_class_elements`
- `no_unused_imports`
- `array_syntax: short`
- `single_quote`: true
- `trailing_comma_in_multiline`: true

### 5.5 Scripts Composer

```json
{
  "scripts": {
    "test": "phpunit --testsuite=unit",
    "test:integration": "phpunit --testsuite=integration",
    "test:all": "phpunit",
    "test:coverage": "XDEBUG_MODE=coverage phpunit --coverage-html coverage/",
    "analyse": "phpstan analyse --memory-limit=512M",
    "cs": "php-cs-fixer fix --dry-run --diff",
    "cs:fix": "php-cs-fixer fix",
    "docs": "phpdoc -d src -t docs/api",
    "qa": ["@cs", "@analyse", "@test"],
    "ci": ["@cs", "@analyse", "@test:all"]
  }
}
```

### 5.6 Pre-commit hooks

`captainhook/captainhook` (PHP, pas de Node) — exécute `cs:fix --dry-run`, `phpstan`, `phpunit unit`.

### 5.7 CI GitHub Actions

`.github/workflows/ci.yml` matrice PHP 8.3, 8.4, 8.5 — lance `composer ci` + couverture xdebug → Codecov.

### 5.8 Activation et désactivation

`Theme::onActivation()` (`after_switch_theme`) :

1. Crée la table `oli_redirects` si nécessaire (`dbDelta`)
2. Initialise les options par défaut
3. Enregistre CPT et taxonomie language
4. Flush rewrite rules
5. Crée pages par défaut (accueil, contact, mentions) si absentes
6. Logge `oli_theme_activated`

`Theme::onDeactivation()` (`switch_theme`) : flush rewrite, conserve les données.

### 5.9 Couverture cible

| Module | Cible |
|--------|-------|
| `Core/` | ≥ 95% |
| `I18n/` | ≥ 95% |
| `Posts/`, `Events/`, `Slides/` | ≥ 90% |
| `Seo/` | ≥ 95% |
| `Seo/Schema/` | 100% |
| `Contact/` | ≥ 95% |
| `Settings/` | ≥ 90% |
| `Navigation/` | ≥ 85% |
| **Global** | **≥ 90%** (échec CI sous seuil) |

### 5.10 Versioning

- SemVer
- `CHANGELOG.md` (Keep a Changelog)
- Header WP `Version:` synchronisé via script
- Tags Git par release

### 5.11 Livrables

**Techniques :**

- Thème `oli-theme/` complet, prêt à activer
- `composer.json` + `composer.lock` versionnés
- Suite de tests verte (PHPUnit, PHPStan, CS)
- Couverture ≥ 90%
- Doc API auto-générée (`docs/api/`)
- ADRs (`docs/decisions/`)
- Pipeline CI fonctionnel
- `docs/installation.md`

**Utilisateur final (`docs/user-guide/`) :**

- Éditer une page
- Gérer le menu
- Ajouter une langue / créer une traduction
- Publier un événement
- Ajouter / réordonner les slides
- Renseigner le SEO d'une page
- Mettre à jour bannière/footer/réseaux sociaux

### 5.12 Critères d'acceptation

Le cycle 1 est livré quand :

1. Toute la suite de tests passe (`composer ci` vert)
2. Lighthouse ≥ 90 sur Performance/A11y/SEO/Best Practices (home + page événement)
3. Validation W3C HTML sans erreurs
4. axe-core sans violations critiques
5. Test multilingue : navigation FR↔EN↔IT sans perte de contexte
6. Test responsive : 375px / 768px / 1280px
7. Test formulaire contact : envoi + mail reçu + multilingue
8. Sitemap.xml accessible et valide (Google Search Console)
9. JSON-LD validé par https://validator.schema.org
10. Documentation utilisateur complète et relue

---

## 6. Annexes

### 6.1 Dépendances Composer

```json
{
  "require": {
    "php": "^8.3",
    "yrbane/lunar-template": "^1.1"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "brain/monkey": "^2.6",
    "phpstan/phpstan": "^1.11",
    "phpstan/extension-installer": "^1.4",
    "php-stubs/wordpress-stubs": "^6.9",
    "friendsofphp/php-cs-fixer": "^3.50",
    "captainhook/captainhook": "^5.20",
    "phpdocumentor/phpdocumentor": "^3.5"
  }
}
```

### 6.2 ADRs prévues

- `0001-mvc-pattern.md` — Pourquoi un MVC strict dans WordPress
- `0002-lunar-template.md` — Choix de Lunar Template
- `0003-custom-multilingue.md` — Système multilingue custom vs Polylang
- `0004-vanilla-css-js.md` — Pas de build pipeline
- `0005-cpt-event.md` — CPT dédié vs posts standards
- `0006-custom-seo.md` — SEO custom intégré vs Yoast
- `0007-contact-form-custom.md` — Formulaire custom vs CF7
- `0008-oop-settings.md` — Page Settings custom vs Customizer

### 6.3 Points reportés à des cycles ultérieurs

- Galerie photo avancée et galerie vidéo dédiées (Priorité 2 spec)
- Agenda interactif / réservation / paiement (Priorité 3 spec)
- Watermark PDF, protection avancée des contenus (Priorité 3 spec)
- Publication automatique vers réseaux sociaux (Priorité 3 spec)
- Plugin Feed Them Social ou intégration Instagram/YouTube native (à évaluer en cycle 2)

---

**Fin du design.**
