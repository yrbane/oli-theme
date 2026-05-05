# Checklist QA — Cycle 1

État : `1.0.0-rc.1` (cycle 1 livré, 9 plans + finalisation).

## Critères automatisés

| Critère | Statut |
|---------|--------|
| `composer ci` vert sur PHP 8.3 | ✅ vérifié en CI GitHub Actions |
| `composer ci` vert sur PHP 8.4 | ✅ vérifié en CI GitHub Actions |
| `composer ci` vert sur PHP 8.5 | ✅ vérifié en CI GitHub Actions |
| PHPStan niveau 8 sans erreur | ✅ |
| PHP-CS-Fixer (PSR-12 + PHP 8.3 migration) | ✅ |
| Tests unitaires + intégration | ✅ ~298 tests / ~1038 assertions |
| Couverture globale | 🔘 à mesurer (cible ≥ 90 %) — `composer test:coverage` |

## Critères manuels (à exécuter sur staging)

### A11y

- 🔘 **axe-core** : aucune violation critique sur `/`, `/fr/`, `/fr/evenements/`, `/contact/`.
  - Outil : extension Chrome / Firefox **axe DevTools**.
- 🔘 **Navigation clavier** : tabuler à travers le header → menu → switcher langue → main → footer sans piège à focus.
  - Vérifier `Esc` ferme le drawer mobile.
  - Vérifier `Arrow Left/Right` navigue dans le carousel.
- 🔘 **Focus visible** sur tous les contrôles interactifs.
- 🔘 **Skip-link** présent en haut de page (`Aller au contenu`).
- 🔘 `prefers-reduced-motion: reduce` désactive autoplay carousel + transitions.

### Performance

- 🔘 **Lighthouse** ≥ 90 sur Performance / A11y / SEO / Best Practices, mesuré sur :
  - Page d'accueil (`/fr/`)
  - Page événement single (`/fr/evenements/<slug>/`)
  - Archive événements (`/fr/evenements/`)
  - Page de contact

### Validation

- 🔘 **W3C HTML validation** : 0 erreur sur 4 pages échantillonnées.
  - URL : `https://validator.w3.org/nu/`
- 🔘 **JSON-LD `@graph`** validé sur https://validator.schema.org/
  - Vérifier que `WebSite`, `Organization`, `Article`, `BreadcrumbList` apparaissent.
  - Pour `/fr/evenements/<slug>/`, vérifier `Event` + `Place` + `Offer`.

### Multilingue

- 🔘 Navigation **FR ↔ EN** sans perte de contexte.
- 🔘 `<link rel="alternate" hreflang>` présent dans le `<head>` pour chaque page traduite + `x-default`.
- 🔘 Fallback `home` activé : si l'EN d'une page n'existe pas, le switcher redirige vers `/en/`.

### Responsive

- 🔘 **375 px** (mobile) : drawer mobile s'ouvre, menu lisible, carousel défilable au doigt.
- 🔘 **768 px** (tablette) : layout intermédiaire, sous-menus accessibles.
- 🔘 **1280 px** (desktop) : sous-menus au hover/focus, carousel avec contrôles prev/next visibles.

### SEO

- 🔘 **Sitemap.xml** accessible : `/sitemap.xml` retourne un index XML pointant vers les sous-sitemaps `/sitemap-{type}-{lang}.xml`.
- 🔘 **Search Console** : sitemap soumis et lu sans erreur.
- 🔘 **Métabox SEO** : compteurs title/description live, score affiché en gauge.
- 🔘 **Redirections 301/410** : créer une redirection dans `Outils > Redirections`, vérifier qu'elle déclenche.

### Formulaire contact

- 🔘 **Envoi avec données valides** → email reçu côté admin.
- 🔘 **Honeypot rempli** → rejeté silencieusement (pas d'email).
- 🔘 **Soumission < 3s** → erreur `too_fast`.
- 🔘 **3 envois en 15 min** → 4e bloqué.
- 🔘 **Auto-réponse activée** → email reçu côté expéditeur.
- 🔘 **Logging activé** → entrée créée dans `Outils > Logs Contact`.

### Activation / migration

- 🔘 **Fresh install** : activer le thème → table `oli_redirects` créée, options par défaut initialisées.
- 🔘 **Upgrade `git pull`** : `oli_theme_db_version` détecte la nouvelle version → migration jouée au premier `init`.

## Documentation

- ✅ `README.md` à jour (table des 9 releases + statut RC).
- ✅ `docs/architecture.md` (modules livrés, interfaces extraites, flow SEO).
- ✅ `docs/installation.md` (prérequis, activation, troubleshooting).
- ✅ `docs/testing.md` (TDD, Brain Monkey, mocking final).
- ✅ 7 guides utilisateur (`multilingue`, `navigation`, `slides`, `events`, `seo`, `contact`, `settings`).
- ✅ Index `docs/user-guide/README.md` + getting-started.
- ✅ 10 ADR dans `docs/decisions/`.
- ✅ `CHANGELOG.md` Keep a Changelog.

## Spec coverage

Spec utilisateur d'origine ([`docs/specs-theme-wordpress-sites.md`](specs-theme-wordpress-sites.md)) :

- ✅ Header permanent + bannière responsive
- ✅ Menu principal avec sous-menus (desktop hover, mobile drawer)
- ✅ Responsive desktop / tablette / mobile
- ✅ Pages administrables
- ✅ Multilingue propre (URLs `/fr/`, `/en/`, `/it/`)
- ✅ Galerie d'accueil défilante (carousel)
- ✅ Formulaire de contact
- ✅ SEO de base ambitieux (objectif « mieux que Yoast »)
- ✅ Gestion des événements (CPT `oli_event`)

**Hors périmètre cycle 1 (reportés cycle 2)** :

- 🔘 Galerie photo / vidéo dédiées
- 🔘 Agenda interactif / réservation / paiement en ligne
- 🔘 Watermark PDF
- 🔘 Publication automatique vers réseaux sociaux
- 🔘 UI riche admin Settings (uploader média, drag-drop langues)
- 🔘 SEO Dashboard avancé (filtres, export CSV)
- 🔘 SEO redirections par regex / wildcards

## Verdict

Si **tous** les critères automatisés sont verts ET les critères manuels exécutés sur staging, on peut tagger `v1.0.0-stable` et clôturer officiellement le cycle 1.

Tant que les critères manuels ne sont pas exécutés, on reste en `v1.0.0-rc.1`.
