# oli-theme

Thème WordPress custom OOP / MVC, multilingue, réutilisable sur plusieurs sites (`olikalari.com`, `satsangham.com`, `olivier.durillon.com`, `margeye.com`).

## Caractéristiques

- Architecture **MVC stricte**, principes **SOLID** / **DRY** / **KISS**
- Moteur de templates [Lunar Template Engine](https://github.com/yrbane/lunar-template) (auteur : yrbane)
- Système **multilingue custom** (URLs `/fr/`, `/en/`, `/it/`...) sans dépendance plugin
- Custom Post Types dédiés : `oli_event`, `oli_slide`
- Module **SEO complet** intégré (JSON-LD `@graph`, sitemap multilingue, Flesch-FR, score 0-100, redirections 301/410, métabox live)
- CSS / JavaScript **vanilla** (pas de pipeline de build)
- Tests **TDD** via PHPUnit 11 + Brain Monkey
- Qualité : **PHPStan niveau 8**, **PHP-CS-Fixer** (PSR-12 + PHP 8.3 migration)
- **PHP `^8.3`**, **WordPress 6.9+**

## État du projet — **Cycle 1 livré** ✅

Tag courant : **`v1.0.0-rc.1`** (Cycle 1 complet, en attente des audits manuels).

| Plan | Tag | Tests | Périmètre |
|------|-----|-------|-----------|
| 1 — Foundation | `v1.0.0-alpha.1-foundation` | 73 | Bootstrap, Container, Core, CI, ADR 0001-0002 |
| 2 — I18n | `v1.0.0-alpha.2-i18n` | ~95 | Taxonomie `language`, rewrite rules, switcher, ADR 0003 |
| 3 — Templates & Posts | `v1.0.0-alpha.3-templates` | 97 | Layout Lunar, partials, pages, theme-bridge, ADR 0004 |
| 4 — Navigation | `v1.0.0-alpha.4-navigation` | 109 | Menus par langue, drawer mobile, ADR 0005 |
| 5 — Slides & Carousel | `v1.0.0-alpha.5-slides` | 125 | CPT `oli_slide`, carousel accessible, ADR 0006 |
| 6 — Events | `v1.0.0-alpha.6-events` | 144 | CPT `oli_event`, archive, métabox, ADR 0007 |
| 7 — SEO complet | `v1.0.0-alpha.7-seo` | 242 | 8 schemas JSON-LD, sitemap multilingue, redirects, score 0-100, ADR 0008 |
| 8 — Contact | `v1.0.0-alpha.8-contact` | 271 | Formulaire OOP/TDD sécurisé (CSRF + honeypot + time-trap + rate-limit), ADR 0009 |
| 9 — Settings | `v1.0.0-alpha.9-settings` | 298 | Page admin `Apparence > Identité du site` (6 onglets), ADR 0010 |
| 10 — QA & finalisation | `v1.0.0-rc.1` | 298 | Doc unifiée, checklist QA, prêt pour audits |

Le cycle 1 livre **9 modules fonctionnels**, **~298 tests** (~1038 assertions), **10 ADR**, **8 guides utilisateur**, et couvre intégralement la spec d'origine. Voir [`docs/qa-cycle1.md`](docs/qa-cycle1.md) pour la checklist d'audit.

## Installation

```bash
composer install
```

Puis activer le thème dans `Apparence > Thèmes`. Détails : [`docs/installation.md`](docs/installation.md).

## Scripts Composer

| Commande | Description |
|----------|-------------|
| `composer test` | Tests unitaires |
| `composer test:integration` | Tests d'intégration |
| `composer test:all` | Tous les tests |
| `composer test:coverage` | Rapport de couverture HTML (`coverage/html`) |
| `composer analyse` | Analyse statique PHPStan niveau 8 |
| `composer cs` | Vérifie le formatage (dry-run) |
| `composer cs:fix` | Corrige le formatage |
| `composer qa` | `cs` + `analyse` + `test` |
| `composer ci` | `cs` + `analyse` + `test:all` (cible CI) |
| `composer docs` | Désactivé temporairement (incompatibilité PHP 8.5 — voir CHANGELOG) |

## Documentation

### Guides utilisateur

- [`docs/installation.md`](docs/installation.md) — installation et mise à jour
- [`docs/multilingue.md`](docs/multilingue.md) — créer une langue, lier des traductions
- [`docs/navigation.md`](docs/navigation.md) — gérer les menus par langue
- [`docs/slides.md`](docs/slides.md) — créer un slide (CPT `oli_slide`)
- [`docs/events.md`](docs/events.md) — publier un événement (CPT `oli_event`)
- [`docs/seo.md`](docs/seo.md) — métabox SEO, score, redirections

### Guides développeur

- [`docs/architecture.md`](docs/architecture.md) — vue d'ensemble du pattern et des modules
- [`docs/templates.md`](docs/templates.md) — moteur Lunar, contrat de vue, conventions
- [`docs/testing.md`](docs/testing.md) — TDD, Brain Monkey, mocking des classes finales

### Décisions architecturales (ADR)

Liste complète dans [`docs/decisions/`](docs/decisions/) :

- 0001 — MVC strict appliqué à WordPress
- 0002 — Lunar Template Engine
- 0003 — Système multilingue custom (vs Polylang)
- 0004 — Layout Lunar unique + bridge minimal + macros lazy
- 0005 — Locations de menus par langue
- 0006 — CPT dédié `oli_slide`
- 0007 — CPT dédié `oli_event`
- 0008 — Module SEO custom (vs Yoast / RankMath / SEOPress)

### Spec produit

- [`docs/specs-theme-wordpress-sites.md`](docs/specs-theme-wordpress-sites.md) — spec utilisateur d'origine
- [`docs/superpowers/specs/2026-05-05-oli-theme-design.md`](docs/superpowers/specs/2026-05-05-oli-theme-design.md) — design technique validé
- [`docs/superpowers/plans/`](docs/superpowers/plans/) — plans d'implémentation détaillés

## Repo

- Code source : <https://github.com/yrbane/oli-theme>
- Auteur : yrbane <yrbane@nethttp.net> — <https://nethttp.net>
- Licence : [MIT](LICENSE)
