# Templates

Le thème oli-theme utilise [Lunar Template](https://github.com/yrbane/lunar-template) comme moteur de vues. Les templates vivent dans `templates/` et sont strictement passifs : ils consomment des DTO et des scalaires, jamais des objets WordPress.

## Arborescence

- `templates/layouts/base.html.tpl` — layout racine, blocs surchargeables :
  `head_extra`, `banner`, `before_main`, `main`, `after_main`, `footer_extra`.
- `templates/partials/` — fragments réutilisables (`header`, `banner`, `footer`,
  `breadcrumbs`).
- `templates/pages/` — un fichier par type de rendu (`page`, `single-post`,
  `archive-post`, `search`, `404`, `front-page`). Chaque fichier `extends`
  `layouts/base.html.tpl` et remplit le bloc `main`.

## Variables globales injectées au boot

`Theme::bootstrapViewRenderer()` câble au démarrage du thème :

| Variable        | Type     | Source                               |
|-----------------|----------|--------------------------------------|
| `siteName`      | string   | `get_bloginfo('name')`               |
| `siteUrl`       | string   | `home_url()`                         |
| `homeUrl`       | string   | `home_url()` (alias)                 |
| `themeUri`      | string   | `get_template_directory_uri()`       |
| `charset`       | string   | `get_bloginfo('charset')`            |
| `currentYear`   | string   | `date('Y')`                          |

`wp_head()` et `wp_footer()` sont exposés en **macros lazy** :

```html
<head>
    ##wpHead()##
</head>
<body>
    ...
    ##wpFooter()##
</body>
```

Les macros capturent la sortie via `ob_start()`/`ob_get_clean()` au moment du rendu. Cela permet aux plugins WP d'injecter leur HTML normalement.

## Variables locales par vue

Chaque controller construit un view-model spécifique transmis à `ViewRenderer::render()` :

| Vue                 | Variables principales                                     |
|---------------------|-----------------------------------------------------------|
| `pages/page`        | `post (PostEntity)`, `lang`, `languageSwitcher`, `bodyClasses` |
| `pages/single-post` | `post`, `lang`, `languageSwitcher`, `bodyClasses`         |
| `pages/archive-post`| `posts (PostEntity[])`, `archiveTitle`, `lang`, `languageSwitcher`, `bodyClasses` |
| `pages/search`      | `query (string)`, `posts`, `lang`, `languageSwitcher`, `bodyClasses` |
| `pages/404`         | `lang`, `languageSwitcher`, `bodyClasses`                 |
| `pages/front-page`  | `post`/`posts` (selon configuration)                       |

## Contrat de vue

Une vue ne reçoit jamais de `WP_Post`. Elle reçoit :

- des **scalaires** (string, int, bool),
- des **arrays** typés via PHPDoc,
- des **DTO immuables** (`PostEntity`, `LanguageSwitcherViewModel`, `Language`).

Si une vue a besoin d'une donnée non disponible, c'est le **controller** qui doit l'ajouter au view-model — jamais la vue qui appelle WordPress.

### Notation pointée hybride

Depuis `yrbane/lunar-template` 2de89f0 (issue [#14](https://github.com/yrbane/lunar-template/issues/14)), la notation `[[ obj.prop ]]` fonctionne aussi bien sur un tableau que sur un objet, via `Lunar\Template\Runtime\Access::get`. On peut donc passer directement un DTO :

```html
<html lang="[[ lang.code ]]" dir="[[ lang.direction ]]">
```

avec `lang` qui peut être un objet `Language` (final readonly) ou un array `['code' => 'fr', 'direction' => 'ltr']`.

Pour les appels de méthode :

```html
<time datetime="[[ post.publishedAt.format('c') ]]">
```

`publishedAt` est un `DateTimeImmutable` ; Lunar génère `$post->publishedAt->format('c')` (méthodes objet) ou `$post['publishedAt']->format('c')` (mix array+méthode).

## Convention BEM

Toutes les classes CSS suivent BEM : `.block`, `.block__element`, `.block--modifier`. Voir `assets/css/base.css` pour des exemples.

## Helpers Lunar

- `[[ var ]]` — interpolation échappée (HTML safe).
- `[[! var !]]` — interpolation brute (HTML déjà filtré par WordPress).
- `[% if cond %][% endif %]`, `[% for x in xs %][% endfor %]` — contrôle.
- `[% extends 'layouts/base.html.tpl' %]` + `[% block main %][% endblock %]` — héritage.
- `[% include 'partials/header.html.tpl' %]` — inclusion.
- `##macro(args)##` — macros enregistrées (ex. `##wpHead()##`).

## Pontage WordPress → controllers

Chaque template de la hiérarchie WP a un fichier de pontage minimal dans `theme-bridge/` :

| Pont WP              | Template Lunar             | Controller                    |
|----------------------|----------------------------|-------------------------------|
| `front-page.php`     | `pages/front-page`         | `PageController` ou `PostController::renderArchive` |
| `page.php`           | `pages/page`               | `PageController::renderSingular` |
| `single.php`         | `pages/single-post`        | `PostController::renderSingle` |
| `archive.php`        | `pages/archive-post`       | `PostController::renderArchive` |
| `search.php`         | `pages/search`             | `PostController::renderSearch` |
| `404.php`            | `pages/404`                | `NotFoundController::render`  |
| `index.php`          | (fallback)                 | `PostController::renderArchive` |

Chaque pontage tient en une ligne logique : `echo Theme::container()->get(<Controller>::class)->render*();`.

## Ajouter un nouveau template

1. Créer `templates/pages/mon-template.html.tpl` qui `extends` `layouts/base.html.tpl`.
2. Créer un controller dans `src/<Module>/MyController.php` qui construit le view-model.
3. Enregistrer la factory dans le `Module` correspondant (`MyModule::register()`).
4. Créer un fichier `theme-bridge/<wp-template>.php` qui invoque le controller.
5. Écrire un test PHPUnit (rendu + assertions sur le HTML produit).
