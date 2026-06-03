# Dossiers dans la médiathèque

Le thème ajoute la **taxonomie hiérarchique `oli_media_folder`** sur les attachments WordPress. Concrètement : tu peux organiser tes médias en **dossiers et sous-dossiers** comme dans un système de fichiers classique.

## Créer un dossier

`Admin → Médias → Dossiers` :
1. Saisir un **nom** (ex. `Stages 2026`).
2. Choisir un **dossier parent** si applicable (pour créer un sous-dossier).
3. Cliquer **Ajouter un nouveau dossier**.

Les dossiers sont éditables / supprimables comme n'importe quelle catégorie WordPress.

## Ranger un média dans un dossier

### Une seule image (et multi-dossiers)

1. Ouvrir un média (`Admin → Médias → Bibliothèque → cliquer sur une image`).
2. Dans la colonne de droite, encadré **Dossiers** : **cocher un OU plusieurs dossiers** (un même média peut appartenir à plusieurs dossiers en même temps).
3. Sauvegarder.

### Bouger d'un dossier à un autre

Même endroit qu'au-dessus : décocher l'ancien dossier, cocher le nouveau, sauvegarder. (Ou utiliser **« Déplacer vers le dossier… »** en bulk pour les actions de masse — voir ci-dessous.)

### Plusieurs images en une fois (bulk actions)

`Admin → Médias → Bibliothèque` (vue **Liste** — bascule en haut à gauche) :

1. **Cocher** les médias à traiter (case à gauche de chaque ligne, ou « Tout sélectionner »).
2. Choisir l'action dans le sélecteur **Actions groupées** :
   - **Déplacer vers le dossier…** — *remplace* les dossiers actuels par celui choisi.
   - **Ajouter au dossier…** — *ajoute* le dossier choisi sans toucher aux dossiers existants (parfait pour mettre une image dans plusieurs dossiers).
   - **Retirer du dossier…** — enlève le dossier choisi de chaque média sélectionné, sans toucher aux autres.
3. **Appliquer** → page de confirmation avec dropdown du dossier cible.
4. **Appliquer** → notice verte confirme combien de médias ont été traités.

### Upload : assigner automatiquement les nouveaux fichiers

Au-dessus de la médiathèque, une notice bleue **« Dossier par défaut pour mes uploads »** propose un sélecteur. Une fois un dossier choisi :
- **Tous tes uploads à partir de maintenant** (drag & drop, bouton Ajouter, depuis l'éditeur, depuis la modale wp.media…) sont **automatiquement** rangés dans ce dossier.
- C'est par utilisateur (chaque admin a son propre défaut).
- Pour désactiver : choisir **« — Aucun (pas d'assignation auto) — »** et cliquer **Définir**.

→ Pratique pour un import en masse : crée le dossier, définis-le par défaut, glisse tes 200 photos dans la médiathèque, tout est rangé automatiquement.

## Filtrer la médiathèque par dossier

`Admin → Médias → Bibliothèque` (vue **Liste**) : un sélecteur **Tous les dossiers** apparaît en haut → filtrer la liste.

Le filtre **inclut les sous-dossiers** automatiquement : choisir `Stages 2026` montre aussi les médias rangés dans `Stages 2026 → Été` et `Stages 2026 → Hiver`.

## Côté front : dossiers = galeries

Chaque dossier peut être affiché comme **galerie photo** côté visiteur.

### Shortcode

Tu peux insérer une galerie n'importe où dans un article, une page ou un événement :

```
[oli_folder_gallery folder="stages-2026"]
[oli_folder_gallery folder="stages-2026" children="false"]
[oli_folder_gallery folder="stages-2026" limit="20" title="Highlights 2026"]
```

Attributs :
- **`folder`** *(obligatoire)* : slug du dossier à afficher.
- **`children`** : `true` (défaut) — inclut récursivement les sous-dossiers ; `false` — uniquement le dossier exact.
- **`limit`** : nombre max de photos, `-1` (défaut) = toutes.
- **`title`** : titre affiché au-dessus de la grille (optionnel).

### Bloc Gutenberg

Le bloc `oli/folder-gallery` (chercher « galerie de dossier » dans l'inserter) expose les mêmes attributs depuis l'éditeur.

### Page Photos (agrégation)

La page WP de slug **`photos`** (ou `photos-en`) affiche **automatiquement toutes les galeries** :
- Chaque **dossier racine** (sans parent) devient une **section** avec son nom en titre `<h2>`.
- Chaque section inclut **récursivement** les photos de ses sous-dossiers.
- L'ordre des sections est alphabétique.
- La galerie « legacy » (option `oli_gallery_photos` saisie dans `Apparence → Galerie`) reste affichée au-dessus si elle est renseignée.

Pour exclure un dossier de la page Photos : le mettre comme sous-dossier d'un autre dossier (il ne sera plus traité comme « section racine » mais ses photos seront incluses dans la section parente).

## Note technique

- Taxonomie : `oli_media_folder` (hiérarchique).
- Visible dans l'API REST → utilisable dans des intégrations externes ou un bloc Gutenberg custom.
- Filtre AJAX également actif dans la **modale wp.media** : si un sélecteur d'image embarque le query var `oli_media_folder`, il restreint les résultats.
