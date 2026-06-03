# Menus

`Admin → Apparence → Menus` (interface WordPress standard).

## Configurer le menu principal

1. Créer un menu nommé (ex. « Principal »).
2. Ajouter des pages, articles, liens personnalisés, taxonomies.
3. Cocher l'**emplacement** « Menu principal » dans la section « Réglages du menu ».

## Si le menu déborde sur deux lignes

Le thème applique trois leviers pour éviter le retour à la ligne :

1. **`white-space: nowrap`** sur chaque item : un libellé multi-mots (ex. « À propos », « Cours particulier ») ne se coupe jamais en plein milieu.
2. **`clamp()` sur la taille de typo desktop** : entre 992 px et 1440 px+, la police s'adapte fluidement (de 0.78rem à 1rem) pour absorber des libellés un peu plus longs sans casser la ligne.
3. **Bascule en menu burger à 992 px** (au lieu de 768 px précédemment) : sur tablette paysage et petit laptop, le burger remplace le menu horizontal — finie la zone intermédiaire où des items rentrent péniblement sur 2 lignes.

Si malgré tout 2 lignes apparaissent au-delà de 992 px, c'est que la somme des libellés est vraiment trop longue. Raccourcir un ou deux items (ex. « À propos » plutôt que « En savoir plus sur l'auteur ») règle le problème.

## Menus par langue

Chaque langue peut avoir son propre menu. Créer deux menus distincts (« Principal FR », « Main EN ») et les **assigner** à l'emplacement correspondant via le sélecteur de langue de l'écran Menus.

## Item courant

Le thème met en surbrillance l'item correspondant à la page consultée (y compris sur les archives event/article).
