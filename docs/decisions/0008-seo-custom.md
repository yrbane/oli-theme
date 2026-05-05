# ADR 0008 — Module SEO custom (vs Yoast / RankMath / SEOPress)

**Statut :** accepté
**Date :** 2026-05-06
**Contexte :** Plan 7 — SEO complet.

## Décision

Implémenter un **module SEO custom** intégré au thème, plutôt qu'utiliser un plugin tiers (Yoast SEO, RankMath, SEOPress, AIOSEO).

## Périmètre couvert

- Stockage des meta SEO par contenu (`_oli_seo_*`).
- Composition du `<head>` : title, meta description, canonical, hreflang, robots, Open Graph, Twitter Card, JSON-LD `@graph`.
- 8 schémas JSON-LD (WebSite, Organization, Person, ImageObject, BreadcrumbList, Article, Event, LocalBusiness).
- Sitemap XML multilingue (index + sous-sitemaps par type × langue).
- Breadcrumbs builder + microdata.
- Score SEO 0-100, configurable via filtre `oli_seo_score_rules`.
- Score lisibilité Flesch-FR (coefficients Kandel & Moles 1958).
- Analyse mot-clé focus (densité, présence dans title/H1/slug).
- Audit images (alt manquant/vide).
- Maillage interne (suggester).
- Redirections 301/410 avec table dédiée et UI admin MVP.
- Métabox per-post avec preview SERP/social et compteurs live.
- Dashboard SEO admin (MVP).

## Alternatives rejetées

### Yoast SEO

- ✅ Mature, communauté large.
- ❌ Multilingue **non natif** (couplage avec WPML/Polylang requis ; ne s'intègre pas avec notre système custom Plan 2).
- ❌ Ajoute du poids (60+ MB) et des UI éditeur que le commanditaire n'a pas demandées.
- ❌ Upsells fréquents et notifications dans l'admin.
- ❌ Score "noir-blanc-rouge" pas configurable finement par site.

### RankMath

- ✅ Plus rapide que Yoast.
- ❌ Couplage fort avec son propre système de paramètres (synchronisation laborieuse avec un système multilingue custom).
- ❌ Dépendance plugin tiers.

### SEOPress

- ✅ Léger, français, conforme RGPD.
- ❌ Multilingue couplé à Polylang/WPML uniquement.
- ❌ Le code source impose la structure de ses meta keys, qu'on ne contrôle pas.

### Aucun module (juste le `<title>` natif WP)

- ❌ Aucun contrôle sur OG, JSON-LD, hreflang. Inacceptable pour un site avec ambition SEO.

## Conséquences

### Avantages

- ✅ **Multilingue natif** : `HreflangBuilder` exploite la `TranslationModel` du Plan 2.
- ✅ **Zéro dépendance plugin**. Le site peut être livré sur n'importe quel hébergement WP standard.
- ✅ **Score configurable par site** via filtre — chaque site Oli peut ajuster la pondération sans toucher au code.
- ✅ **Schémas JSON-LD agrégés sous `@graph`** — meilleur que Yoast qui produit plusieurs `<script>` séparés.
- ✅ **Flesch-FR validé** (Kandel & Moles 1958) plutôt qu'une formule générique anglaise mal adaptée.
- ✅ **Tests unitaires** sur tous les composants SEO (37 tests dédiés au module).
- ✅ **DI propre** : tous les builders / analyzers sont injectables et mockables.
- ✅ **Pas d'upsell, pas de pubs** dans l'admin.

### Inconvénients

- ❌ **Surface code à maintenir** : ~30 classes, ~3000 lignes dont environ 1500 de tests. Charge de maintenance non négligeable.
- ❌ **Fonctionnalités manquantes vs Yoast Premium** : analyse de cocon sémantique avancée, intégration Wincher, redirections par regex avec wildcards. Ces fonctionnalités peuvent être ajoutées en cycle 2 si demandées.
- ❌ **Dashboard MVP** : pas encore d'export CSV ni de filtres avancés (reportés à un Plan 7bis).

## Choix techniques internes

- **Pattern Builder** par concern (Canonical, Hreflang, Robots, OpenGraph, TwitterCard) — chaque builder est une fonction pure testable.
- **Pattern Schema + Context** : un `SchemaInterface` par schéma, agrégés via `SchemaContext::toJsonLd()`. Permet d'ajouter de nouveaux schémas sans toucher aux existants (OCP).
- **Score** : rubrique de poids configurable côté site via filtre WP (DRY ; pas de duplication entre sites).
- **Métabox UI** : Lunar template + ES module vanilla `seo-metabox.js`. Pas de React/jQuery dans la métabox (cohérent avec la direction "vanilla, sans build" du thème).
- **Redirections** : table SQL dédiée `oli_redirects` (création via `dbDelta` à l'activation du thème). `RedirectController` hooké sur `template_redirect`.

## Liens

- Spec : `docs/superpowers/specs/2026-05-05-oli-theme-design.md` § 4.1.
- Implémentation : `src/Seo/` (~30 classes), `templates/partials/seo-head.html.tpl`, `templates/admin/seo-*.html.tpl`.
- Tests : `tests/Unit/Seo/` (37 tests dédiés) + `tests/Integration/SeoE2ETest.php`.
