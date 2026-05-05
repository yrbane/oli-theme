# ADR 0006 — CPT dédié `oli_slide` pour le carrousel d'accueil

**Statut :** accepté
**Date :** 2026-05-05
**Contexte :** Plan 5 — Slides & Home Carousel.

## Décision

Modéliser les slides du carrousel d'accueil comme un **Custom Post Type dédié** `oli_slide`, avec :

- supports : `title`, `thumbnail` (image à la une), `excerpt` (légende), `page-attributes` (ordre).
- taxonomie partagée `language` (déjà utilisée pour pages/posts).
- meta keys typées : `_oli_slide_link_url`, `_oli_slide_link_label`, `_oli_slide_expires_at`.
- non-public en front (`public => false, show_ui => true`) — visible uniquement via le carousel.

## Alternatives rejetées

- **Blocs Gutenberg dédiés** (un bloc « Slide » dans un bloc parent « Carousel ») : couplerait étroitement le thème à l'éditeur Gutenberg. Le rédacteur paye en complexité et perd la simplicité d'une liste plate dans l'admin. Reporté pour cycle 2 si l'expérience admin le réclame.
- **ACF Repeater** (un champ Repeater « slides » sur la page d'accueil) : nécessite Advanced Custom Fields (plugin tiers, version Pro pour Repeater). Le thème oli-theme exige zéro dépendance plugin (cf. spec section 0.4). Rejeté.
- **Posts standards filtrés par catégorie** : recyclerait `post` mais polluerait l'archive d'actualités. Difficile à filtrer par expiration. Rejeté.
- **Options sérialisées (`oli_slides`)** : simple mais non-éditables via l'admin standard. Pas de versioning ni de révisions. Rejeté.

## Conséquences

- ✅ UI admin standard WordPress, familière aux rédacteurs.
- ✅ Système de révisions et corbeille natifs.
- ✅ Compatible avec la taxonomie `language` du système multilingue custom (Plan 2).
- ✅ Évolutif : ajouter une métabox custom (link URL/label, expiration) dans un plan ultérieur sans changer le contrat externe.
- ❌ Une entrée de menu admin supplémentaire (« Slides »).
- ❌ Les meta keys `_oli_slide_*` doivent être éditées « à la main » (admin > Champs personnalisés) tant que la métabox dédiée n'est pas livrée.

## Liens

- Spec : `docs/superpowers/specs/2026-05-05-oli-theme-design.md` § 2.4, § 4.5.
- Implémentation : `src/Slides/SlideCpt.php`, `src/Slides/SlideModel.php`.
