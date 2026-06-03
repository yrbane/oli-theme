# Calendrier de réservation

`Admin → Apparence du thème → Calendrier & réservations` regroupe **4 sous-onglets** : Planning, Services, Réservations, Réglages.

## Réglages globaux

Sous-onglet **Réglages** (`?tab=calendrier&sub=reglages`) :

| Réglage | Effet | Plage / Valeurs |
|---|---|---|
| **Durée d'un créneau** | Pas de temps de la grille hebdo (admin + front). | 30 à 240 min. Typique : **60** ou **120**. |
| **Jours ouvrés** | Cases à cocher dim → sam. | Défaut : Lun–Ven. |
| **Heure d'ouverture / fermeture** | Plage horaire quotidienne. | `HH:MM` 24 h. |
| **État par défaut** | Comportement d'un créneau « non touché ». | Ouvert OU Bloqué. |
| **E-mail de notification** | Reçoit les nouvelles réservations. | Si vide : `admin_email` WP. |
| **Confirmation automatique** | `pending` ou `confirmed` à la création. | Case à cocher. |

## Planning hebdomadaire

Sous-onglet **Planning** : grille jour × heure pour la semaine en cours.

- Navigation : `← Semaine précédente / Semaine du JJ/MM-JJ/MM / Semaine suivante →` + bouton **Cette semaine**.
- Couleurs par état :
  - **Vert** : libre (réservable depuis le front).
  - **Gris** : bloqué.
  - **Orange** : réservation en attente.
  - **Bleu** : réservation confirmée.
- Actions par ligne :
  - Libre → **Bloquer**.
  - Bloqué → **Libérer** (sauf si source = synchro externe iCal, marqué non éditable).
  - En attente → **Confirmer** / **Annuler**.
  - Confirmée → **Annuler**.

## Services réservables

Sous-onglet **Services** : CRUD des prestations.

- Libellés FR/EN, durée (15–480 min), prix optionnel (en centimes), descriptions FR/EN HTML.
- ID auto-généré depuis le libellé si laissé vide.

## Réservations

Sous-onglet **Réservations** : 50 réservations les plus récentes, filtrables par statut (Toutes / En attente / Confirmées / Annulées). E-mail cliquable, téléphone affiché si fourni.

## Widget frontend

Pour insérer le widget de réservation dans une page WP :

- **Bloc Gutenberg** : `oli/booking-calendar` (cherche « Calendrier de réservation »).
- **Shortcode** : `[oli_booking_calendar service="massage-1h"]`.

Le visiteur sélectionne un service, une semaine, clique sur un créneau libre, remplit nom + e-mail (+ téléphone / message optionnels), valide. Modale `<dialog>` native, JS sans dépendance, accessibilité clavier complète.

## Sécurité du flux de réservation

- **Nonces WP** sur tous les endpoints REST.
- **Honeypot** invisible (champ `website`) qui simule un succès silencieux pour les bots.
- **Délai mini** 2 s entre rendu du formulaire et soumission.
- **Rate limit IP** : 5 réservations / heure / IP (IP hashée, non stockée en clair).
- **Validation stricte** : service connu, email valide, créneau futur, pas de conflit.

## Notifications e-mail

À chaque création de réservation, 2 e-mails sont envoyés :

- **Au client** : récap localisé FR/EN selon la langue du visiteur, service, date, statut.
- **À l'admin** : sujet préfixé `[À CONFIRMER]` ou `[CONFIRMÉE]`, coordonnées client + message.

Action WP exposée : `do_action('oli_booking_created', $booking)` — pour brancher d'autres handlers personnalisés.

## Synchronisation iCal (Gmail, Apple, Outlook…)

### Export (sortant)

Le thème expose un flux `.ics` public mais protégé par token secret dans le sous-onglet Réglages.

URL : `https://olikalari.com/?oli_ics=<token>`

Coller cette URL dans Google Calendar → « + Autres agendas → À partir de l'URL ». Google rafraîchit toutes les 8–24 h. Les en-têtes `X-Robots-Tag: noindex` et `Cache-Control: private, no-store` évitent toute indexation.

### Import (entrant)

Renseigner une ou plusieurs URLs iCal externes (HTTPS uniquement, max 1 MB par feed) dans les réglages.

Un cron WP `oli_calendar_ics_pull` (horaire, demi-journalier ou quotidien) télécharge et parse chaque feed et crée des indisponibilités locales taggées `source: ics:<hash>`. Ces créneaux sont visibles dans la grille comme « Bloqué (synchro externe) » et **ne sont pas éditables** (toute modification serait écrasée au prochain pull).
