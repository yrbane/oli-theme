# Galerie photos & vidéos

`Admin → Apparence du thème → Contenu → Galerie` gère deux galeries : **photos** (médiathèque + légendes) et **vidéos** (YouTube).

## Photos

1. **Téléverser** les images via le sélecteur médiathèque.
2. **Renseigner la légende** dans le champ ad hoc à côté de chaque photo.
3. Sauvegarder.

Les images bénéficient d'un `srcset` responsive (taille adaptée à l'écran) et d'une **lightbox** (clic = vue plein écran avec navigation ←/→).

## Vidéos YouTube

1. Renseigner l'**URL de la chaîne YouTube** : les vidéos publiques sont récupérées automatiquement.
2. Optionnel : ajouter manuellement des **IDs / URLs** de vidéos spécifiques avec légendes.

## Pages frontend

Les galeries s'affichent sur les pages WordPress nommées `photos`, `videos` (FR) et `photos-en`, `videos-en` (EN). Le bouton **« Créer les pages manquantes »** dans l'onglet Galerie les génère automatiquement si elles n'existent pas.

## Évolution visuelle prévue

Image principale au centre + bande de miniatures dessous — voir [#12](https://github.com/yrbane/oli-theme/issues/12).
