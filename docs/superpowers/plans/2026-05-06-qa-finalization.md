# QA & Finalization Implementation Plan (oli-theme — Cycle 1, Plan 10/10)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development.

**Goal:** Conclure le cycle 1 avec une release `1.0.0-rc.1` : doc utilisateur finale unifiée (`docs/user-guide/`), checklist QA (`docs/qa-cycle1.md`) référencant les audits Lighthouse / axe-core / W3C / JSON-LD / responsive, mise à jour des docs racine (README, architecture) pour refléter les 9 plans livrés.

**Architecture:** Pas de nouveau code de production. Phase consolidation, doc et release.

**Tech Stack:** Identique aux Plans 1-9.

**Reference spec:** `docs/superpowers/specs/2026-05-05-oli-theme-design.md` § 5.11-5.12 (livrables + critères d'acceptation cycle 1).

---

## Tasks (8)

### T1 — Doc utilisateur unifiée

Créer `docs/user-guide/README.md` avec sommaire des 6 guides existants (`multilingue`, `navigation`, `slides`, `events`, `seo`, `contact`, `settings`) + un nouveau guide pédagogique `docs/user-guide/getting-started.md` (10 minutes pour publier sa première page multilingue).

Commit: `docs(user-guide): index + getting-started (cycle 1)`.

### T2 — Checklist QA cycle 1

Créer `docs/qa-cycle1.md` couvrant :

- ✅ `composer ci` vert sur PHP 8.3 / 8.4 / 8.5 (CI GitHub Actions)
- 🔘 Lighthouse ≥ 90 sur Performance / A11y / SEO / Best Practices (à exécuter sur instance staging)
- 🔘 W3C HTML validation (à exécuter)
- 🔘 axe-core sans violations critiques (à exécuter)
- 🔘 JSON-LD validé sur https://validator.schema.org (à exécuter)
- 🔘 Test responsive 375 / 768 / 1280 px (à exécuter)
- 🔘 Test multilingue FR↔EN sans perte de contexte (à exécuter)
- 🔘 Test formulaire contact : envoi + mail reçu + auto-reply + log (à exécuter)
- 🔘 Sitemap.xml accessible via Search Console (à exécuter)

Cases automatisées cochées ; cases manuelles à exécuter sur staging documentées avec commandes/URLs.

Commit: `docs(qa): checklist cycle 1 + critères d'acceptation`.

### T3 — Mise à jour `architecture.md` (statut final)

Marquer Plans 8 et 9 comme livrés ✅ dans la section « Plans d'implémentation ». Ajouter section « Modules livrés » mise à jour avec Settings + Contact.

Commit: `docs(architecture): marque les Plans 8 et 9 comme livrés`.

### T4 — Mise à jour `README.md` (table des releases)

Ajouter les lignes pour `v1.0.0-alpha.8-contact` et `v1.0.0-alpha.9-settings`. Bumper l'état du projet à « Cycle 1 livré, RC 1.0.0-rc.1 ».

Commit: `docs(readme): table des 9 releases livrées + statut RC`.

### T5 — Mise à jour `installation.md`

Étendre la section « versions livrées » avec les 9 tags + mention de la `1.0.0-rc.1`. Ajouter une note sur la page Settings (apparait après activation sous `Apparence > Identité du site`).

Commit: `docs(installation): étend la section versions + page Settings`.

### T6 — Bump `style.css` vers `1.0.0-rc.1`

Modifier le header WP de `style.css` : `Version: 1.0.0-alpha → 1.0.0-rc.1`.

Commit: `chore: bump version 1.0.0-rc.1 dans style.css`.

### T7 — Changelog `1.0.0-rc.1` + tag + push

Entrée changelog explicitant la consolidation cycle 1. Tag `v1.0.0-rc.1`.

```bash
git tag -a v1.0.0-rc.1 -m "Release 1.0.0-rc.1 — Cycle 1 livré (Plans 1-9)"
git push origin main
git push origin v1.0.0-rc.1
```

Commit: `docs: changelog 1.0.0-rc.1 (clôture cycle 1)`.

### T8 — README cycle 1 récapitulatif (optionnel)

Créer `docs/CYCLE1.md` synthétisant ce qui est livré dans le cycle 1 (288+ classes, ~300 tests, 9 modules, 10 ADR, 7 guides utilisateur). Servira de point de référence pour le cycle 2.

Commit: `docs: CYCLE1.md récapitulatif des livrables cycle 1`.

---

## DoD

1. `composer ci` green.
2. Tag `v1.0.0-rc.1` pushé.
3. Doc utilisateur unifiée dans `docs/user-guide/`.
4. Checklist QA présente dans `docs/qa-cycle1.md`.
5. README + architecture + installation à jour.
6. `style.css` Version: 1.0.0-rc.1.

When done, le cycle 1 est officiellement clôturé. Cycle 2 (UI builder admin, watermark PDF, agenda interactif, réservation, etc.) peut être planifié.
