# Menus

`Admin → Apparence → Menus` (interface WordPress standard).

## Configurer le menu principal

1. Créer un menu nommé (ex. « Principal »).
2. Ajouter des pages, articles, liens personnalisés, taxonomies.
3. Cocher l'**emplacement** « Menu principal » dans la section « Réglages du menu ».

## Si le menu déborde sur deux lignes

Cela arrive quand la **somme des largeurs** des items dépasse la largeur du conteneur :

- **Solution rapide** : raccourcir les titres des items (ex. « À propos » plutôt que « En savoir plus sur l'auteur »).
- **Solution prévue** : ajustement automatique de la taille de typo + bascule menu burger plus précoce — voir [#10](https://github.com/yrbane/oli-theme/issues/10).

## Menus par langue

Chaque langue peut avoir son propre menu. Créer deux menus distincts (« Principal FR », « Main EN ») et les **assigner** à l'emplacement correspondant via le sélecteur de langue de l'écran Menus.

## Item courant

Le thème met en surbrillance l'item correspondant à la page consultée (y compris sur les archives event/article).
