# Apparence : variations CSS, bandeau, polices

Le module `Appearance` regroupe trois réglages visuels accessibles depuis le menu **Apparence** de WordPress :

1. Choix d'une **variation CSS** (skin) qui surcharge `main.css`
2. Choix d'une **image de bandeau** pour les pages internes
3. Choix d'une **police Google Fonts** pour les titres

Tous les réglages vivent sur la même page : **Apparence > Variations CSS**.

---

## 1. Variations CSS

### Principe

Une variation est un fichier CSS posé dans `assets/css/variations/`. Chaque
fichier devient automatiquement une option dans le sélecteur côté admin.

### Créer une variation

```bash
# Dans le dossier du thème :
echo '/* Theme Variation: Hiver glacé */
:root {
    --color-bg: #e0f2fe;
    --color-text: #0c4a6e;
}
body { background: var(--color-bg); color: var(--color-text); }
' > assets/css/variations/hiver-glace.css
```

L'en-tête `/* Theme Variation: ... */` est facultatif :
- s'il est présent, son contenu sert de label dans le `<select>` admin
- sinon, le label est dérivé du nom de fichier (`hiver-glace.css` → « Hiver glace »)

### Comment ça s'enqueue

`Core\AssetManager::enqueueFront()` charge dans cet ordre :

```
oli-theme-css            (assets/css/main.css)        ← base
oli-theme-variation-css  (assets/css/variations/X.css) ← override variation
oli-theme-admin-bar-css  (assets/css/admin-bar.css)    ← compensation barre admin (gagne la cascade)
```

La variation hérite donc de toutes les variables CSS de `main.css` et ne
modifie que ce qu'elle veut redéfinir.

### Variations livrées

| Fichier | Label |
|--------|-------|
| `dark.css` | Sombre |
| `sunset.css` | Coucher de soleil |
| `olikalari.css` | Olikalari (style éditorial minimaliste) |

---

## 2. Image de bandeau (pages internes)

Les variations qui exposent la custom-property CSS `--oli-internal-banner-url`
(comme Olikalari) affichent une image en bandeau en haut des pages internes
(toutes sauf la home avec carousel).

### Configurer

Dans **Apparence > Variations CSS**, section « Bandeau pages internes » :
- Cliquer **Choisir une image** → ouvre la médiathèque WP
- Sélectionner ou téléverser une image (format paysage recommandé, 2:1 ou plus large)
- Cliquer **Utiliser cette image**

L'image custom override l'image par défaut du thème (`assets/img/banner.jpg`).

### Aperçu

La page admin affiche **toujours** une preview :
- Image personnalisée si configurée (badge vert « Image personnalisée »)
- Sinon image par défaut du thème (badge gris « Image par défaut du thème »)

### Comment c'est rendu

`AssetManager::injectInternalBannerOverride()` injecte une CSS inline si
l'option `oli_internal_banner_image` est définie :

```css
html { --oli-internal-banner-url: url('https://…/banner.jpg'); }
```

Le CSS de la variation utilise :

```css
background-image: var(--oli-internal-banner-url, url('../../img/banner.jpg'));
```

→ fallback automatique si rien n'est configuré.

---

## 3. Police des titres (Google Fonts)

Le sélecteur permet de choisir une police pour `h1, h2, h3, h4, h5, h6`,
`.banner__title` et `.carousel-fullscreen__title`.

### Catalogue

`Appearance\GoogleFontsLibrary` charge le **catalogue complet Google Fonts**
(~1900 familles) depuis `assets/data/google-fonts.json`. Toutes les polices
publiques sont disponibles, dont des récentes comme **Bricolage Grotesque**.

### Picker admin

- Champ texte avec **autocomplete HTML5** (datalist) : tape les premières lettres
- Preview live : la police est chargée dynamiquement via un `<link>` injecté dans `<head>`
- Bouton **Effacer** : revient à la police par défaut du thème
- Indicateur ⚠️ « Police inconnue » si tu tapes un nom hors catalogue

### Front

Si une police est sélectionnée :

```php
// AssetManager::injectTitlesFontOverride()
wp_enqueue_style('oli-theme-titles-font', 'https://fonts.googleapis.com/css2?family=…');
wp_add_inline_style('oli-theme-admin-bar', "
    h1,h2,h3,h4,h5,h6,
    .banner__title,
    .carousel-fullscreen__title { font-family: '…', system-ui, sans-serif !important; }
");
```

Sécurité : la sanitize côté admin valide contre la liste blanche du catalogue.
Côté front, regex `[A-Za-z0-9 ]` en filet de sécurité contre l'injection CSS.

### Rafraîchir le catalogue

Pour mettre à jour les polices disponibles avec les nouveaux ajouts Google Fonts :

```bash
curl -s 'https://fonts.google.com/metadata/fonts' \
  | jq '[.familyMetadataList[] | {family, category}]' \
  > assets/data/google-fonts.json
```
