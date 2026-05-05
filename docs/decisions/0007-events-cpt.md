# ADR 0007 — CPT dédié `oli_event` pour les événements

**Statut :** accepté
**Date :** 2026-05-05
**Contexte :** Plan 6 — Events CPT.

## Décision

Modéliser les événements comme un **Custom Post Type dédié** `oli_event` (publique, archive activée, slug `evenements`), avec :

- supports : `title`, `editor`, `excerpt`, `thumbnail`, `page-attributes`.
- taxonomie partagée `language`.
- meta keys typées : `_oli_event_start_date`, `_oli_event_end_date`, `_oli_event_location`, `_oli_event_address`, `_oli_event_flyer_url`, `_oli_event_registration_url`, `_oli_event_price`.
- métabox custom (`EventMetabox`) pour les champs de détails, avec nonce et sanitization.

## Alternatives rejetées

- **Posts standards taggés `event`** : recyclerait `post` mais polluerait l'archive d'actualités. Difficile à filtrer/trier par date d'événement (vs date de publication). Rejeté.
- **ACF Field Group** : nécessite Advanced Custom Fields (plugin tiers). Le thème exige zéro dépendance plugin. Rejeté.
- **The Events Calendar** ou plugin similaire : fonctionnalités riches mais ajoute du poids et des dépendances externes pas désirées. Rejeté.
- **Plugin Tribe Events** : idem.

## Conséquences

- ✅ UI admin standard, familière aux rédacteurs.
- ✅ URL canonique `/<langue>/evenements/<slug>/` cohérente avec le reste du site multilingue.
- ✅ Compatible avec la taxonomie `language` (Plan 2) et le système de menus (Plan 4).
- ✅ Évolutif : ajout futur d'une métabox plus riche (date picker JS, géocodage adresse) sans casser le contrat externe.
- ✅ Microdonnées `schema.org/Event` natives dans `single-event.html.tpl` (SEO + GMB ready).
- ❌ Slug archive (`evenements`) en français uniquement pour l'instant ; localiser le slug par langue est reporté à un plan SEO/i18n ultérieur.
- ❌ Métabox custom à maintenir (vs plugin tiers).

## Liens

- Spec : `docs/superpowers/specs/2026-05-05-oli-theme-design.md` § 2.3.
- Implémentation : `src/Events/EventCpt.php`, `src/Events/EventModel.php`, `src/Events/EventMetabox.php`.
