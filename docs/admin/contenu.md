# Contenu — articles & pages

## Articles

`Admin → Articles → Ajouter` : créer un article comme dans n'importe quel WordPress. Le thème affiche automatiquement :

- L'**image mise en avant** en grand sous le titre.
- Le **titre** centré.
- Le **fil d'Ariane** (Accueil → Article).
- La **largeur de lecture** optimisée (~65 caractères par ligne).

## Page d'accueil & cartes d'article

La home liste les articles récents sous forme de **cartes** avec :

- **Vignette** à gauche (image mise en avant, taille `large` recadrée en `object-fit: cover` 160×120 px) ;
- **Titre** de taille moyenne (h3, ~1–1.35 rem) au lieu du titre en gros ;
- **Date** de publication ;
- **Extrait** (si renseigné).

La carte entière est cliquable. Sans image mise en avant, un dégradé subtil remplace la vignette. En mobile (< 560 px), la vignette passe au-dessus du titre sur toute la largeur.

Pour qu'une **traduction** apparaisse sur la home anglaise, il faut **lier** les deux articles (FR + EN) via le groupe de traduction — voir le guide **Langues & traductions**. Visiter `/en/` rend automatiquement la traduction de la page d'accueil si elle existe.
