# Slides du carousel

Le carousel plein écran de la page d'accueil affiche les **slides** publiées (custom post type `oli_slide`).

## Créer une slide

1. `Admin → Slides → Ajouter une slide`.
2. Saisir le **titre** (sera affiché en sous-titre dans le carousel).
3. **Choisir l'image** : cliquer sur **« Image mise en avant »** dans la colonne de droite, puis sélectionner ou téléverser une image dans la médiathèque.
4. Optionnel : rédiger un contenu enrichi (non affiché dans le carousel, conservé pour archive).
5. Publier.

## Pourquoi une image s'affiche automatiquement

Si **aucune image mise en avant** n'est définie, le carousel utilise une image de **secours générique** (service `picsum.photos`). Pour avoir une image cohérente avec la marque, **toujours définir l'image mise en avant**.

## Dimensions recommandées

- **Largeur** : 1920 px.
- **Hauteur** : 1080 px (16:9).
- **Poids** : < 250 ko.

## Carousel par langue

Chaque slide appartient à une langue (taxonomy `language`). Le carousel de `/` affiche les slides FR, celui de `/en/` les slides EN.
