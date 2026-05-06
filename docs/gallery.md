# Galerie : photos & vidéos YouTube

Le module `Gallery` ajoute deux pages spéciales pré-câblées :

- **Photos** (`/photos/` ou `/gallery/photos-en/`) : layout vignettes-gauche / image-droite avec lightbox au clic
- **Vidéos** (`/videos/` ou `/gallery/videos-en/`) : player YouTube à gauche, liste des titres à droite

Configuration : **Apparence > Galerie**.

---

## Activation des pages

Le contrôleur détecte automatiquement les slugs suivants et applique le
layout galerie :

| Slug WP | Page rendue |
|---------|-------------|
| `photos` | Galerie photos FR |
| `photos-en` | Galerie photos EN |
| `videos` | Galerie vidéos FR |
| `videos-en` | Galerie vidéos EN |

Si ces pages n'existent pas encore :

1. **Pages > Ajouter** dans WordPress
2. Renseigner le titre (ex. « Photos »)
3. Forcer le slug `photos` (champ permalien)
4. Publier
5. Ajouter au menu via **Apparence > Menus**

Le contenu de la page est libre — le thème injecte automatiquement le layout
galerie au-dessus du `post.content`.

---

## Photos

### Configuration

Dans **Apparence > Galerie**, section **Photos** :

- **Ajouter des photos** : ouvre la médiathèque WP en mode multi-sélection
- Sélectionner plusieurs images d'un coup
- Saisir une **légende** facultative pour chacune
- **Tout vider** : retire toutes les photos d'un coup
- **× Retirer** : retire une photo individuellement

Stockage : option WP `oli_gallery_photos` (JSON `[{attachment_id, caption}, ...]`).

### Rendu front

```
┌──────────────┬────────────────────────┐
│ vignettes    │                        │
│ ┌──┬──┐      │      IMAGE PRINCIPALE  │
│ │  │  │      │      (zoom-in cursor)  │
│ ├──┼──┤      │                        │
│ │  │  │      │      [légende]         │
│ └──┴──┘      │                        │
└──────────────┴────────────────────────┘
```

- Clic sur une vignette → swap de l'image principale
- Clic sur l'image principale → **lightbox plein écran** (fond noir 92 %, fade-in)
- Lightbox : ferme à `Escape`, au clic hors-image, ou via le bouton ×

### Sources des données

`Gallery\GalleryRepository::getPhotos()` hydrate les attachment IDs :
- `wp_get_attachment_image_url($id, 'large')` → image principale
- `wp_get_attachment_image_url($id, 'medium')` → vignette
- `get_post_meta($id, '_wp_attachment_image_alt', true)` → alt accessibilité

---

## Vidéos

### Configuration

Dans **Apparence > Galerie**, section **Vidéos** :

- **URL chaîne YouTube** : par défaut `https://www.youtube.com/@OliKalari`,
  modifiable. Accepte les formats `/@handle`, `/channel/UC…`, `/c/Custom`,
  `/user/Name`.
- **Ajouter une vidéo** (optionnel) : pour saisir manuellement des vidéos
  individuelles. Champ accepte URL ou ID brut. Une thumbnail YouTube est
  affichée automatiquement au cours de la frappe.

### Mode mixte

Le repository utilise une logique en deux temps :

1. **Si l'admin a saisi des vidéos manuelles** (option non vide) → on les utilise
   (override, captions custom, ordre custom).
2. **Sinon** → fetch automatique des **15 dernières vidéos publiées** de la chaîne
   YouTube via le RSS public (cache 1 h), avec extraction du `channel_id` depuis
   le HTML public (cache 7 jours).

Pas de clé API requise. Limitation officielle YouTube : RSS = 15 vidéos max.

### Bypass de la page consent RGPD

YouTube renvoie une page de consent aux requêtes server-side. Le fetcher
ajoute automatiquement les cookies `CONSENT=YES+cb` + `SOCS=…` et un
user-agent navigateur (Chrome récent) pour récupérer la vraie page de la
chaîne.

### Rendu front

```
┌────────────────────────────────┬──────────────┐
│                                │ ▶ Titre 1    │
│      PLAYER YOUTUBE            │ ▶ Titre 2    │
│   (autoplay au clic)           │ ▶ Titre 3    │
│                                │ ▶ Titre 4    │
│      [titre vidéo en cours]    │ ▶ ...        │
└────────────────────────────────┴──────────────┘
```

- Player iframe `youtube-nocookie.com/embed/…?rel=0` (sans cookies, suggestions désactivées)
- Liste de titres à droite : chaque entrée = picto ▶ + titre tronqué 2 lignes
- Clic sur un titre → swap iframe (autoplay) + active highlight

Les vignettes images des vidéos sont volontairement masquées (interface plus sobre).

---

## Cache

| Donnée | TTL | Clé transient |
|--------|-----|---------------|
| `channel_id` (extrait du HTML chaîne) | 7 jours | `oli_yt_channel_id_{md5(url)}` |
| Liste des 15 vidéos (RSS XML) | 1 heure | `oli_yt_videos_{md5(url)}` |

Pour forcer un re-fetch :

```bash
wp transient delete --all
```

ou via Apparence > Galerie en re-sauvegardant.
