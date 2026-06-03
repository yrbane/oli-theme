# Bannière responsive

Deux bannières coexistent dans le thème :

1. **Le bandeau des pages internes** (strip étroit sous l'en-tête, sur toutes les pages sauf la home). Pilote par variation CSS via la custom-property `--oli-internal-banner-url`.
2. **Le carousel plein écran de la home** (pas une image fixe — voir le guide *Slides du carousel*).

Ce guide concerne **le bandeau des pages internes**.

## Où renseigner l'image

`Admin → Apparence du thème → Identité & Marque → Bannière` propose **deux champs** :

- **Bannière desktop** (médiathèque) : utilisée à partir de **768 px** de largeur d'écran.
- **Bannière mobile** (médiathèque) : utilisée en-dessous de **768 px**.

Si vous renseignez **les deux**, le thème bascule automatiquement entre les deux selon la taille de l'écran via une media query CSS. Si vous n'en renseignez qu'**une seule**, elle s'applique partout (l'autre slot reste vide).

## Dimensions recommandées

| Cible | Largeur | Hauteur | Ratio | Poids max |
|---|---|---|---|---|
| **Desktop** | 1920 px | 220 px | ~9:1 | < 200 ko |
| **Tablette** | (utilise la version desktop) | | | |
| **Mobile** | 750 px | 200 px | ~4:1 | < 80 ko |

## Comment la responsivité fonctionne

Le bandeau est un pseudo-élément `body::before` placé en haut des pages internes :

```css
body:not(.home)::before {
    height: clamp(120px, 14vw, 220px);
    background-image: var(--oli-internal-banner-url, ...);
    background-size: contain;
    background-position: center center;
    background-repeat: no-repeat;
}
```

- **Hauteur** : `clamp(120px, 14vw, 220px)` — entre 120 px (mobile) et 220 px (desktop large), s'adapte fluidement à la viewport.
- **Image** : `background-size: contain` — l'image est **toujours visible en entier** (jamais recadrée), centrée horizontalement et verticalement. Le sujet principal n'est donc jamais rogné, contrairement à `cover`.
- **Bascule desktop/mobile** : media query `(min-width: 768px)` qui change la custom-property `--oli-internal-banner-url`.

## Conseils composition

- Travailler en **format paysage très large** (ratio ~9:1 pour desktop, ~4:1 pour mobile).
- Placer le **sujet principal au centre** (le `background-position: center` ne le recadre pas mais centre).
- Éviter le texte intégré à l'image — utilisez le **slogan** du thème, qui se superpose proprement et reste lisible.
- Exporter en **WebP** ou **JPEG qualité 75** pour rester sous les tailles cibles.

## Fallback historique

Une option héritée `oli_internal_banner_image` (`Admin → Apparence → Variations CSS`) accepte une URL unique d'image. Elle est utilisée uniquement si les deux champs **Bannière desktop** et **Bannière mobile** sont vides.

Source de vérité : `src/Core/AssetManager.php → injectInternalBannerOverride()`.
