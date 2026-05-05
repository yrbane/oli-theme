# ADR 0002 — Choix de Lunar Template Engine

**Date :** 2026-05-05
**Statut :** Accepté

## Contexte

Le thème impose une séparation stricte vue / logique (cf. ADR 0001). Trois
moteurs de templates étaient candidats :

1. **Timber + Twig** — standard MVC pour WordPress, dépendance Composer
2. **Moteur custom léger** maison — zéro dépendance, à coder/tester
3. **Templates PHP WP natifs** — rejeté par ADR 0001

## Décision

Adopter **Lunar Template Engine** (`yrbane/lunar-template`) :

- Standalone, aucune dépendance hors PHP 8.3+
- 100 % testé, PHPStan niveau 7
- Syntaxe propre :
  - `[[ var ]]` (variable échappée),
  - `[[! var !]]` ou `| raw` (sans échappement),
  - `[% extends 'base.tpl' %]` / `[% block content %]` (héritage multi-niveaux),
  - `##macroName(args)##` (macros réutilisables),
  - `[% include 'partial.tpl' %]`, `[% set foo = ... %]`.
- Cache compilé sur disque, prewarming supporté.
- Échappement XSS automatique, validation des chemins de templates.
- Architecture modulaire (Parser / Compiler / Renderer / Cache) — propre pour
  injection de dépendances.
- Maintenu par l'auteur (yrbane), licence MIT.

## Conséquences

### Positives

- Cohérence avec la philosophie "zéro dépendance lourde" du commanditaire.
- Performance : cache compilé, pas de jQuery côté front.
- Sécurité : échappement XSS par défaut.
- Aligné sur les compétences PHP du futur prestataire (pas de Twig à apprendre).

### Négatives

- Communauté plus restreinte que Twig.
- Documentation moins fournie ; certaines fonctionnalités à découvrir au cas par cas.
- Pas publié sur Packagist au moment de l'écriture de cet ADR : installation via dépôt VCS GitHub.
- Pas de tag stable au moment de l'écriture : utilisation de `dev-main`. À mettre à jour dès qu'un tag v1.x est publié.

## Alternatives écartées

- **Timber/Twig** : ajout d'une dépendance significative et d'une syntaxe
  Twig à apprendre pour un thème custom à long terme.
- **Moteur custom maison** : reproduire un sous-ensemble de Lunar serait
  inutile et chronophage.
- **PHP natif `<?php ?>`** : violation de la séparation vue/logique.
