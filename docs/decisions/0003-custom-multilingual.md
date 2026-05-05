# ADR 0003 — Système multilingue custom (versus plugin)

**Date :** 2026-05-05
**Statut :** Accepté

## Contexte

Le thème doit supporter plusieurs langues (`/fr/`, `/en/`, `/it/`, `/es/`) avec
des arborescences indépendantes par langue, sans répétition automatique entre
langues sur la même page, et sans dépendance à un plugin lourd.

Trois options évaluées :

1. **Plugin Polylang ou WPML** — mature, fonctionnalités riches.
2. **WordPress multisite** (1 site = 1 langue) — isolation parfaite.
3. **Custom léger dans le thème** — contrôle total, zéro plugin.

## Décision

Adopter l'option **3 : système multilingue custom** dans `src/I18n/` :

- Taxonomie `language` (slug = code ISO 639-1).
- Post meta `_oli_translation_group` (UUID) lie les versions linguistiques.
- Rewrite rules custom préfixent les URLs par la langue.
- Filtre `home_url` ajoute automatiquement le préfixe.
- Switcher de langue propre, basé sur les groupes de traduction.

## Conséquences

### Positives

- Aucun plugin tiers : simplicité de maintenance, transmissibilité.
- Code testable en TDD (Brain Monkey) sans charger WordPress.
- Performance : pas de surcoût de plugin (WPML est lourd).
- Parfaitement aligné avec l'architecture MVC du thème.

### Négatives

- Reproduire un sous-ensemble des fonctionnalités d'un plugin éprouvé
  demande discipline (édit côté admin, gestion des médias, glossaires).
- Pas d'écosystème de connecteurs (DeepL, traducteurs humains intégrés).
  À évaluer si besoin futur.

## Alternatives écartées

- **Polylang** : plugin léger mais encore une dépendance ; moins
  intégré à l'architecture custom du thème.
- **WPML** : payant, lourd, opaque côté code ; rejeté.
- **WordPress multisite** : pertinent pour des sites totalement
  indépendants, mais surdimensionné ici et augmente la charge d'admin.

## Périmètre du Plan 2

Ce plan livre :

- Le value object `Language` et son registre.
- La taxonomie `language` et son enregistrement.
- Le résolveur de langue (URL > cookie > Accept-Language > default).
- Le modèle de groupes de traduction.
- Les rewrite rules `/fr/`, `/en/`, `/it/`, `/es/`.
- Le filtre `home_url`.
- Le contrôleur du switcher de langue (ViewModel pur).
- La metabox d'admin "Traductions".
- Le module orchestrateur `I18nModule` branché dans `Theme::boot()`.

L'UI front du switcher sera rendue par les templates (Plan 3).
La gestion des permaliens de posts avec `get_permalink` filtré arrivera
en Plan 3 également (couplée aux templates de page).
