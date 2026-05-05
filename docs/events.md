# Événements

Le thème expose un Custom Post Type `oli_event` permettant de publier des événements multilingues avec dates, lieu, prix et URL d'inscription.

## Créer un événement

1. Admin WordPress > **Événements** > **Ajouter un événement**.
2. Renseigner :
   - **Titre** + **Contenu** (description riche, éditeur Gutenberg).
   - **Image à la une** (optionnelle).
   - **Extrait** (optionnel — utilisé sur les listings).
   - **Langue** (taxonomie) — pour quelle langue cet événement.
   - **Détails de l'événement** (métabox dédiée) :
     - Date de début (`datetime-local`)
     - Date de fin (optionnelle)
     - Lieu (string)
     - Adresse (textarea)
     - URL du flyer (PDF/image)
     - URL d'inscription (lien externe)
     - Prix (string libre — « Gratuit », « 25 € », « Sur don »…)
3. Publier.

## URL et structure

- Archive multilingue : `/<langue>/evenements/`
- Fiche événement : `/<langue>/evenements/<slug>/`
- Le slug racine est `evenements` (FR par défaut). Pour traduire l'URL en EN/IT, il faudra ajouter une logique de slug par langue dans `EventCpt::register()` (reporté à un cycle ultérieur).

## États affichés

Chaque événement reçoit deux booléens calculés à la volée par `EventModel` :

- `isPast` — la date de fin (ou de début si pas de fin) est passée.
- `isOngoing` — l'événement a commencé et n'est pas terminé.

Ces états sont reflétés dans les classes CSS : `.event--past`, `.event--ongoing`. L'archive sépare visuellement les événements à venir et passés.

## Vues front

- `templates/pages/single-event.html.tpl` — fiche détaillée avec microdonnées `schema.org/Event`.
- `templates/pages/archive-event.html.tpl` — archive séparée en deux sections (à venir / passés).
- `templates/partials/event-card.html.tpl` — carte réutilisable.

## Pour les développeurs

- `OliTheme\Events\EventEntity` — DTO immuable.
- `OliTheme\Events\EventModelInterface` — `findUpcoming(Language, int)`, `findPast(Language, int)`, `findById(int)`, `findBySlug(string, Language)`.
- `OliTheme\Events\EventControllerInterface::renderSingle(): string`
- `OliTheme\Events\EventArchiveControllerInterface::renderArchive(int $limit = 10): string`
- Hooks WP : `init` (CPT), `add_meta_boxes` (métabox), `save_post_oli_event` (sauvegarde).
