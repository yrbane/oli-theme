# Bannière responsive

La bannière s'affiche automatiquement en haut de la page d'accueil et s'adapte à 3 contextes : **smartphone**, **tablette** et **ordinateur**.

## Dimensions recommandées

- **Largeur** : 1920 px minimum (idéalement 2400 px pour les écrans Retina).
- **Hauteur** : 600 à 800 px.
- **Ratio** : ~16:9 ou plus large (3:1 fonctionne bien).
- **Poids** : viser moins de 200 ko après compression (WebP ou JPEG qualité 80).

## Comment la responsivité fonctionne

Le thème utilise `object-fit: cover` : l'image conserve son ratio et **recadre automatiquement** selon la zone disponible. Le centre de l'image est privilégié.

- **Smartphone (≤ 480 px)** : bandeau étroit, seul le centre vertical est visible.
- **Tablette (480–1024 px)** : bandeau intermédiaire.
- **Desktop (> 1024 px)** : bandeau pleine largeur, image visible quasi en totalité.

## Astuce composition

Placer le **sujet principal au centre** de l'image. Éviter le texte intégré à l'image (utilise plutôt le **slogan** du thème, qui se superpose proprement et reste lisible sur tous les écrans).

## Où la modifier

`Admin → Apparence du thème → Identité & Marque → Bannière` : sélecteur de médiathèque. Une **prévisualisation** s'affiche immédiatement après upload.
