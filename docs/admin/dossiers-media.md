# Dossiers dans la médiathèque

Le thème ajoute la **taxonomie hiérarchique `oli_media_folder`** sur les attachments WordPress. Concrètement : tu peux organiser tes médias en **dossiers et sous-dossiers** comme dans un système de fichiers classique.

## Créer un dossier

`Admin → Médias → Dossiers` :
1. Saisir un **nom** (ex. `Stages 2026`).
2. Choisir un **dossier parent** si applicable (pour créer un sous-dossier).
3. Cliquer **Ajouter un nouveau dossier**.

Les dossiers sont éditables / supprimables comme n'importe quelle catégorie WordPress.

## Ranger un média dans un dossier

### Une à la fois

1. Ouvrir un média (`Admin → Médias → Bibliothèque → cliquer sur une image`).
2. Dans la colonne de droite, encadré **Dossiers** : cocher les dossiers.
3. Sauvegarder.

### En masse

`Admin → Médias → Bibliothèque` (vue **Liste**, pas Mosaïque) :
1. Cocher plusieurs médias.
2. En haut, sélecteur **Actions groupées → Modifier** → cliquer **Appliquer**.
3. Dans le bloc qui apparaît, choisir les dossiers à appliquer.
4. **Mettre à jour**.

## Filtrer la médiathèque par dossier

`Admin → Médias → Bibliothèque` (vue **Liste**) : un sélecteur **Tous les dossiers** apparaît en haut → filtrer la liste.

Le filtre **inclut les sous-dossiers** automatiquement : choisir `Stages 2026` montre aussi les médias rangés dans `Stages 2026 → Été` et `Stages 2026 → Hiver`.

## Côté front

Le système est **purement organisationnel** : il n'affecte pas le rendu front du thème. Tu peux ranger sans crainte.

## Note technique

- Taxonomie : `oli_media_folder` (hiérarchique).
- Visible dans l'API REST → utilisable dans des intégrations externes ou un bloc Gutenberg custom.
- Filtre AJAX également actif dans la **modale wp.media** : si un sélecteur d'image embarque le query var `oli_media_folder`, il restreint les résultats.
