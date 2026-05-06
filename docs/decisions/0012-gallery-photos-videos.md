# ADR 0012 — Galerie Photos + Vidéos YouTube auto-fetch

**Statut :** Accepté — Cycle 2

## Contexte

Le site Olikalari a deux pages spéciales (« Galerie / Photos »,
« Galerie / Vidéos ») avec un layout identique : vignettes d'un côté,
élément principal de l'autre. La page Photos doit permettre l'upload
multiple depuis l'admin. La page Vidéos doit afficher les dernières
vidéos publiées de la chaîne YouTube de l'utilisateur, **sans clé API**
(hors scope, et inutile pour 95 % des cas).

Les pages doivent se brancher dans le routing WP standard (`get_queried_object`)
sans nécessiter de Page Templates manuels (une URL = un slug, point).

## Décision

Créer un module `Gallery` avec :

1. **`GalleryRepository`** : 3 options WP autonomes :
   - `oli_gallery_photos` (JSON) — tableau `[{attachment_id, caption}]`
   - `oli_gallery_youtube_channel` (string) — URL chaîne YT (default
     `https://www.youtube.com/@OliKalari`)
   - `oli_gallery_videos` (JSON) — vidéos manuelles (override) ou vide

2. **`YoutubeChannelFetcher`** : récupère les 15 dernières vidéos via
   le **flux RSS public** de YouTube
   (`https://www.youtube.com/feeds/videos.xml?channel_id=…`).
   Le `channel_id` (UCxxx) est extrait du HTML public de la chaîne avec
   bypass de la page consent RGPD via cookies `CONSENT=YES+cb` + `SOCS=…`
   et user-agent navigateur. Cache transient 7 jours pour le channel_id,
   1 heure pour les vidéos.

3. **Mode mixte** : si l'admin a saisi des vidéos manuelles → override ;
   sinon auto-fetch.

4. **Routing dans `PageController`** : détection slug-based — `photos`,
   `photos-en`, `videos`, `videos-en` → templates dédiés.

5. **Templates** `gallery-photos.html.tpl` et `gallery-videos.html.tpl` :
   layout 2-colonnes, sticky sur l'élément principal, JS module ES pour
   le swap au clic + lightbox plein écran sur les photos.

## Conséquences

**Positives :**
- Aucune clé API requise (fetch via RSS, public)
- Un fichier JSON option (cache transient) → pas de table custom
- Slugs dans la liste blanche → 4 URL pré-câblées EN/FR
- Fallback gracieux : si fetch échoue (réseau down, chaîne renommée), retour vide
- Lightbox photo entièrement vanilla (75 lignes JS)
- Admin riche : media uploader WP pour photos, auto-thumbnail YouTube côté JS au cours de la frappe

**Négatives / compromis :**
- Limite officielle YouTube : 15 dernières vidéos. Pour plus, il faudrait
  une clé API Data v3 (hors scope, complexe à expliquer aux utilisateurs)
- Cookies CONSENT bypass = comportement non documenté de YouTube. Si
  Google change le format, le fetcher peut casser. Cache transient atténue
  l'impact + fallback vide
- Slugs FR/EN hardcodés (`photos`, `photos-en`, etc.) : suffisant pour
  les 4 langues actuelles, à étendre si on ajoute IT/ES dans le module

## Sécurité

- `wp_get_attachment_image_url` pour résoudre les URLs (URL publique signée WP)
- `sanitizeVideoId()` accepte ID brut, `watch?v=`, `youtu.be/`, `embed/`,
  `shorts/`. Regex `[A-Za-z0-9_-]{11}` exact en validation
- Iframe en `youtube-nocookie.com/embed` (pas de tracking)
- `target="_blank" rel="noopener noreferrer"` sur les liens externes
- Capability `manage_options` + nonce sur le formulaire admin

## Alternatives écartées

- **Page Templates WP classiques** (`Template Name: Gallery`) : nécessite
  des fichiers `.php` dans `theme-bridge/` + sélection manuelle dans
  l'éditeur de page. Plus de friction pour l'utilisateur, alors que le
  slug-based marche tout seul.
- **CPT `oli_photo` + CPT `oli_video`** : surdimensionné — un site
  Olikalari a une **seule** galerie photos et une seule galerie vidéos.
  Pas besoin de tableau d'admin avec colonnes/filtres/etc. Une page
  d'édition unique suffit.
- **YouTube API Data v3** : nécessite une clé API stockée côté serveur,
  quota de requêtes, complexe à documenter. RSS public couvre 95 % des
  besoins.
