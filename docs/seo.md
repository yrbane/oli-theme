# SEO

Le thème oli-theme intègre un module SEO custom complet, compatible avec son système multilingue. L'objectif : fournir au minimum la couverture de Yoast, en exploitant nativement la taxonomie `language` (Plan 2) et les pontages WP (Plan 3).

## Vue d'ensemble

| Capacité | Couverture |
|---|---|
| Title et meta description par contenu | ✅ Plan 7 |
| Open Graph + Twitter Card | ✅ |
| `<link rel="canonical">` (avec override) | ✅ |
| `<link rel="alternate" hreflang>` (multilingue + `x-default`) | ✅ |
| `<meta name="robots">` (noindex/nofollow) | ✅ |
| JSON-LD `@graph` complet | ✅ (8 schemas : WebSite, Organization, Person, ImageObject, BreadcrumbList, Article, Event, LocalBusiness) |
| Sitemap XML multilingue | ✅ |
| Breadcrumbs avec microdata | ✅ |
| Score SEO 0-100 (configurable via filtre) | ✅ |
| Score lisibilité Flesch-FR (Kandel & Moles) | ✅ |
| Densité de mot-clé focus | ✅ |
| Audit images (alt manquant/vide) | ✅ |
| Suggestions de maillage interne | ✅ |
| Redirections 301/410 (table dédiée) | ✅ |
| Métabox per-post avec preview SERP / score live | ✅ |
| SEO Dashboard (admin) | ✅ MVP — extensions ultérieures prévues |

## Renseigner le SEO d'une page

Sur l'écran d'édition d'un contenu (post / page / oli_event), une métabox **SEO** apparaît sous l'éditeur principal :

- **Titre SEO** — affiché dans le `<title>` et les social cards. Recommandé : 30-65 caractères.
- **Méta description** — affichée dans `<meta name="description">`. Recommandé : 120-158.
- **Mot-clé focus** — utilisé pour évaluer la pertinence (densité, présence dans titre/H1/slug).
- **Mots-clés secondaires** — séparés par virgule.
- **Image Open Graph (ID)** — ID de média WP. Recommandé : ≥ 1200×630.
- **Type Twitter Card** — `summary` ou `summary_large_image`.
- **noindex / nofollow** — pour exclure de l'index Google.
- **URL canonique** — override si la page a une cible canonique différente.
- **Priorité / changefreq** — pour le sitemap XML.

L'aperçu SERP et la gauge de score se mettent à jour en direct via `assets/js/seo-metabox.js`. Le score définitif est recalculé côté PHP au save.

## Comprendre le score SEO

Le score 0-100 est calculé par `ScoreCalculator` selon une rubrique pondérée (poids 3-8 par critère). Les critères couvrent :

- Longueur title (30-65) et description (120-158)
- Présence du focus dans title / H1 / slug / premier paragraphe
- Densité du focus (0.5%-2.5%)
- Toutes images ont un `alt`
- Score Flesch-FR ≥ 60 (lisibilité grand public)
- Longueur contenu ≥ 300 mots
- Canonical défini
- Image OG définie

Les poids sont **configurables par site** via un filtre WordPress :

```php
add_filter('oli_seo_score_rules', function (array $rules): array {
    $rules['flesch_above_60'] = 12; // augmente le poids de la lisibilité
    return $rules;
});
```

## Sitemap

- Index : `https://exemple.com/sitemap.xml` (liste les sous-sitemaps).
- Sous-sitemaps : `/sitemap-{type}-{lang}.xml` (par CPT × langue).
- Chaque entrée inclut `<xhtml:link rel="alternate" hreflang>` pour ses traductions.

## Redirections

Page admin **Outils > Redirections** (MVP — UI d'ajout étendue prévue).

Modèle de données : table `oli_redirects` créée à l'activation du thème (`Theme::onActivation` → `dbDelta`).

Comportement front : `RedirectController::handle()` est branché sur `template_redirect`, vérifie l'URL demandée contre la table, applique 301/410.

## Pour les développeurs

### Architecture

```
src/Seo/
├── SeoMeta.php                       DTO immuable
├── SeoMetaModel.php (+ Interface)    accès aux meta _oli_seo_*
├── SeoController.php (+ Interface)   orchestrateur du <head>
├── SeoHeadViewModel.php              DTO produit par le controller
├── CanonicalBuilder.php
├── HreflangBuilder.php
├── RobotsBuilder.php
├── OpenGraphBuilder.php
├── TwitterCardBuilder.php
├── BreadcrumbsController.php (+ Interface)
├── BreadcrumbItemEntity.php
├── SitemapController.php (+ Interface)
├── SitemapEntryBuilder.php
├── SitemapIndexBuilder.php
├── ReadabilityAnalyzer.php           Flesch-FR Kandel & Moles
├── KeywordAnalyzer.php
├── InternalLinkSuggester.php
├── ImageAuditor.php
├── ScoreCalculator.php
├── RedirectEntity.php
├── RedirectModel.php (+ Interface)
├── RedirectController.php
├── Schema/
│   ├── SchemaInterface.php
│   ├── SchemaContext.php             agrégateur @graph
│   ├── ArticleSchema.php
│   ├── EventSchema.php
│   ├── PersonSchema.php
│   ├── OrganizationSchema.php
│   ├── WebSiteSchema.php
│   ├── BreadcrumbListSchema.php
│   ├── LocalBusinessSchema.php
│   └── ImageObjectSchema.php
├── Admin/
│   ├── SeoMetabox.php
│   ├── SeoOverviewPage.php
│   └── RedirectsPage.php
└── SeoModule.php                     orchestrateur (Container + hooks)
```

### Filtres exposés

- `oli_seo_score_rules` — surcharge des poids du calcul du score.
- `oli_seo_title_separator` — séparateur entre titre de la page et nom du site (default ` — `).

### Templates

- `templates/partials/seo-head.html.tpl` — sortie complète du `<head>` SEO. Inclus depuis `layouts/base.html.tpl` quand `seo` est défini.
- `templates/admin/seo-metabox.html.tpl` — UI métabox.
- `templates/admin/seo-overview.html.tpl` — dashboard MVP.
- `templates/admin/redirects.html.tpl` — table des redirections.

### View-model injection

Les controllers `Posts/PageController`, `PostController`, `NotFoundController`, `Events/EventController`, `EventArchiveController` injectent :

- `seo` (SeoHeadViewModel) — consommé par le partial `seo-head.html.tpl`.
- `crumbs` (BreadcrumbItemEntity[]) — consommé par `breadcrumbs.html.tpl`.
