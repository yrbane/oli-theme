# Galerie photos & vidéos

`Admin → Apparence du thème → Contenu → Galerie` gère deux galeries : **photos** (médiathèque + légendes) et **vidéos** (YouTube).

## Photos

1. **Téléverser** les images via le sélecteur médiathèque.
2. **Renseigner la légende** dans le champ ad hoc à côté de chaque photo.
3. Sauvegarder.

Les images bénéficient d'un `srcset` responsive (taille adaptée à l'écran) et d'une **lightbox** (clic = vue plein écran avec navigation ←/→).

## Présentation côté front (page Photos)

Layout vertical type smartphone :

1. **Image principale** en grand au centre (max-width 960 px, ratio préservé).
2. **Légende** sous l'image.
3. **Bande horizontale de miniatures** scrollable (`scroll-snap`) sous l'image.

Cliquer sur une miniature swappe l'image principale et sa légende (sans rechargement). La miniature active porte un cadre noir.

## Vidéos YouTube

1. Renseigner l'**URL de la chaîne YouTube** : les vidéos publiques sont récupérées automatiquement.
2. Optionnel : ajouter manuellement des **IDs / URLs** de vidéos spécifiques avec légendes.

## Pages frontend

Les galeries s'affichent sur les pages WordPress nommées `photos`, `videos` (FR) et `photos-en`, `videos-en` (EN). Le bouton **« Créer les pages manquantes »** dans l'onglet Galerie les génère automatiquement si elles n'existent pas.

