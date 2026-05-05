# ADR 0005 — Locations de menus par langue

**Statut :** accepté
**Date :** 2026-05-05
**Contexte :** Plan 4 — Navigation.

## Décision

Pour chaque langue activée, **enregistrer deux locations distinctes** : `primary_<code>` et `footer_<code>`. Le rédacteur compose un menu par langue dans l'admin WP standard et l'attache à la location correspondante.

## Alternatives rejetées

- **Un seul menu translatable** (un menu central, items traduits via post meta) : aurait couplé la traduction des menus au système multilingue custom et nécessité une UI custom pour gérer les overrides par langue. Plus de code, moins lisible côté admin.
- **Menus auto-générés à partir des pages** : pratique mais inflexible (le rédacteur veut souvent un ordre, des libellés ou des groupes différents du titre brut des pages).

## Conséquences

- ✅ Compatible 100 % avec l'UI WordPress standard (Apparence > Menus).
- ✅ Indépendance totale entre menus FR / EN / IT / ES.
- ✅ Locations dynamiques : ajouter une langue dans `Settings > Langues` (plan ultérieur) crée automatiquement deux nouvelles locations au prochain `after_setup_theme`.
- ❌ Le rédacteur doit recréer la structure pour chaque langue (pas de duplication automatique en cycle 1).
- ❌ Si une langue est désactivée, son menu reste persisté en base (acceptable : pas de perte de données).

## Liens

- Spec : `docs/superpowers/specs/2026-05-05-oli-theme-design.md` § 4.4
- Implémentation : `src/Navigation/MenuLocations.php`, hook `after_setup_theme` dans `NavigationModule`.
