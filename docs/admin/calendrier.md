# Calendrier de réservation

> **Statut : Phase 1 livrée** (fondations) — voir l'issue [#14](https://github.com/yrbane/oli-theme/issues/14) pour le suivi des phases suivantes (admin hebdomadaire, widget frontend, notifications, synchro Gmail/iCal).

Le module Calendrier permettra à terme :

- À Olivier de **bloquer des créneaux** d'indisponibilité dans une vue admin hebdomadaire.
- Aux visiteurs de **réserver** des cours particuliers ou des séances de massage via un widget frontend.
- De **synchroniser** avec Gmail/Apple/Outlook via iCal (export + import).

## Réglages disponibles dès maintenant (`oli_calendar_settings`)

| Réglage | Description | Défaut |
|---|---|---|
| `slotDurationMinutes` | Durée d'un créneau (30–240 min). Typiquement **60** ou **120**. | 60 |
| `workingDays` | Jours ouvrés (0 = dim, 6 = sam). | Lun–Ven |
| `workingHoursStart` | Heure d'ouverture (`HH:MM`). | 09:00 |
| `workingHoursEnd` | Heure de fermeture (`HH:MM`). | 19:00 |
| `defaultState` | `available` ou `blocked` — état par défaut d'un créneau. | available |
| `notificationEmail` | E-mail qui recevra les nouvelles réservations. | (vide) |
| `autoConfirm` | Réservation directement confirmée (`true`) ou en attente (`false`). | false |

Pour l'instant ces valeurs ne sont pas exposées dans l'admin (P2). Tu peux y accéder en code via `Theme::container()->get(CalendarSettings::class)`.

## Phases prévues

- **P2 — Admin** : vue grille hebdomadaire (← précédent / semaine du JJ/MM / →), édition de créneau (bloquer / réserver / libérer), CRUD services réservables, liste des réservations.
- **P3 — Frontend** : bloc Gutenberg `oli/booking-calendar`, modale de réservation, endpoints REST `GET /slots` et `POST /bookings`.
- **P4 — Notifications & sécurité** : e-mails FR/EN, rate limiting IP, honeypot.
- **P5 — Synchro iCal** : export `.ics` (token secret) + import (cron horaire) — voir le commentaire dédié de [#14](https://github.com/yrbane/oli-theme/issues/14).
