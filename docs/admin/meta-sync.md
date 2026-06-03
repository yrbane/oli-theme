# Synchronisation Facebook + Instagram

> **Statut : Phase 1 livrée** (fondations de stockage chiffré) — voir l'issue [#15](https://github.com/yrbane/oli-theme/issues/15) pour le suivi des phases suivantes (OAuth, publication, édition, suppression, réconciliation).

## Ce qui sera disponible à terme

- **Cocher la sync** sur chaque article / page / événement individuellement.
- **Publication automatique** sur Facebook (Page) et Instagram (compte Business lié).
- **Édition propagée** côté Meta quand vous modifiez le post WP.
- **Suppression propagée** côté Meta quand vous supprimez/trashez le post WP.
- **Réconciliation quotidienne** : si un post est supprimé manuellement côté Meta, les références locales sont nettoyées.

## Phase 1 (livrée) — Stockage chiffré

Le module \`MetaSync\` est en place avec :

- \`MetaSyncCredentials\` : DTO immuable des identifiants (App ID, App Secret, Page ID, IG User ID, Access Token, Expires At).
- \`TokenStore\` : persistance chiffrée AES-256-GCM (authentifiée, refuse les payloads altérés). Clé dérivée de \`AUTH_KEY\` (WordPress) via HMAC-SHA256.
- \`MetaSyncModule\` : factories DI.

Aucune interface admin n'est encore exposée — c'est intentionnel, la P1 est la fondation de sécurité sur laquelle s'appuieront les phases suivantes.

## Phases prévues

- **P1.5 — OAuth** : flux OAuth Meta (admin-ajax \`oli_meta_oauth\`) + refresh token quotidien.
- **P2 — Facebook** : \`FacebookPublisher\` create / edit / delete via Graph API + metabox toggle sur l'éditeur.
- **P3 — Instagram** : \`InstagramPublisher\` (workflow 2 étapes) + strategy \`skip | delete_recreate\` pour l'édition.
- **P4 — Cycle de vie** : hooks WP \`publish_post\` / \`post_updated\` / \`before_delete_post\` + ContentHash pour éviter les PATCH inutiles.
- **P5 — Réconciliation & admin** : cron quotidien + tableau récap admin + actions en masse + logs.
- **P6 — Events** : test API Events Facebook + fallback post standard.

## Guides connexes

- \`meta-sync-setup.md\` (à venir) : créer une App Meta, récupérer App ID + Secret, OAuth.
- \`meta-sync-limitations.md\` (à venir) : caption IG non éditable, image IG obligatoire, rate limit, Events API restreinte.
- \`meta-sync-troubleshooting.md\` (à venir) : codes d'erreur Graph 100/190/200/368/803, reconnexion.
- \`meta-sync-app-disabled.md\` (à venir) : App en mode Dev/Live, App Review, suspicious activity.
- \`meta-sync-revoke.md\` (à venir) : couper l'accès côté thème + côté Facebook.
- \`meta-sync-glossaire.md\` (à venir) : App ID, secret, OAuth, scopes, IG Business vs perso.
