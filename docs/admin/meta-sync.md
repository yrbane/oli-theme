# Synchronisation Facebook + Instagram

`Admin → Apparence du thème → Contact → Synchro Meta` configure et pilote la publication automatique des posts/pages/events vers Facebook (Page) et Instagram (compte Business lié).

> **Toutes les phases livrées** (P1 à P6). Voir l'issue [#15](https://github.com/yrbane/oli-theme/issues/15) pour l'historique.

## Première installation (autonome)

Olivier doit suivre les guides dédiés (visibles dans cet onglet via les liens du bandeau d'aide jaune) :

- **Première installation** → `meta-sync-setup.md`
- **Mon token a expiré** → `meta-sync-token-expired.md`
- **Mon App est désactivée** → `meta-sync-app-disabled.md`

En résumé :

1. Créer une App Meta sur `developers.facebook.com → Mes apps → Créer une application → Business`.
2. Ajouter les produits **Connexion Facebook** et **Instagram**.
3. Récupérer **App ID** + **Clé secrète** dans `Paramètres → Général`.
4. Configurer l'URI de redirection : `https://olikalari.com/wp-admin/admin-post.php?action=oli_meta_oauth_callback`.
5. Demander les permissions `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`, `instagram_basic`, `instagram_content_publish` (App Review nécessaire en mode Live).
6. Lier le compte Instagram Business à la Page Facebook (Meta Business Suite).
7. Coller App ID + Secret dans le sous-onglet **Synchro Meta** + cliquer **Connecter à Facebook**.

## Synchronisation par post

Sur chaque éditeur d'article/page/event, une **metabox latérale** « Synchro Facebook / Instagram » expose :

- **Activer la sync sur ce post** (case à cocher).
- **Cibles** (Facebook / Instagram, cases à cocher).
- **Statut courant** : Non synchronisé / Synchronisé / Synchronisé partiellement / Erreur / En attente.
- **IDs externes** (FB post-id, IG media-id) si déjà publiés.
- **Dernière erreur** affichée en rouge si applicable.

## Cycle de vie automatique

Olivier ne fait rien — le thème écoute les hooks WordPress :

| Hook WP | Action côté Meta |
|---|---|
| `publish_post` / `publish_page` / `publish_oli_event` | **Create** sur les cibles activées (et qui n'ont pas déjà d'ID externe). |
| `post_updated` (statut `publish`) | **Edit** si le hash de contenu a changé. Sinon ne fait rien (évite les PATCH inutiles). |
| `before_delete_post` / `wp_trash_post` | **Delete** sur Meta pour chaque cible avec ID externe. |

### Stratégie d'édition Instagram

L'API Instagram **ne permet pas** d'éditer la caption d'un média existant. Olivier choisit :

- **`skip`** (défaut) : ignore l'édition côté IG. Le post IG reste avec son contenu d'origine.
- **`delete_recreate`** : supprime puis recrée le post IG (perd l'URL et les likes accumulés).

### Stratégie d'édition Facebook Events

L'API Events Facebook est **restreinte par Meta**. Le thème :

1. Tente la création native via `/{page-id}/events`.
2. Si erreur de permission (#200) ou HTTP 400 → fallback automatique vers une publication standard `/{page-id}/feed` avec un message enrichi (titre, 🗓 date, 📍 lieu, extrait, lien).

## Réconciliation quotidienne

Cron `oli_meta_sync_reconcile` (daily) : pour chaque post avec un FB ou IG ID stocké, ping Graph API. Si Meta renvoie 404 / code 803 (post supprimé manuellement hors WP), nettoie la meta locale pour permettre une nouvelle création propre à la prochaine édition.

## Renouvellement du token

Cron `oli_meta_sync_refresh_token` (daily) : si le long-lived token expire dans <7 jours, renouvelle automatiquement via `fb_exchange_token`. Notice orange dans l'admin si l'expiration approche et que le refresh échoue.

## Sécurité

- **App Secret** + **Access tokens** stockés chiffrés **AES-256-GCM** (authentifié), clé dérivée d'`AUTH_KEY` via HMAC-SHA256. Tout payload altéré est rejeté.
- **OAuth state** dans transient 10 min, comparé via `hash_equals`.
- **Nonces** sur toutes les actions admin-post.
- **Pas de log** des tokens (`[REDACTED]`).
- **Rate limit Graph** : retry automatique sur HTTP 500 (1 fois, 200 ms backoff).

## Limitations connues

- **Instagram** : image obligatoire. Posts sans featured image → skip avec notice.
- **Instagram** : caption non éditable → option `skip | delete_recreate`.
- **Facebook Events** : API restreinte → fallback automatique post standard.
- **Rate limit Graph** : 200 calls / heure / token (Meta limite côté serveur).

## Métadonnées stockées par post

`_oli_meta_sync_enabled`, `_oli_meta_sync_targets`, `_oli_meta_fb_post_id`, `_oli_meta_ig_media_id`, `_oli_meta_fb_url`, `_oli_meta_ig_url`, `_oli_meta_last_sync_at`, `_oli_meta_last_sync_status` (synced / partial / error / pending), `_oli_meta_last_sync_error`, `_oli_meta_content_hash`.
