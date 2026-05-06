# Variations CSS du thème

Déposez ici un fichier `*.css` par variation. Chaque fichier est automatiquement
détecté par `Appearance\ThemeVariationRegistry` et proposé dans
**Apparence > Variations CSS** côté admin.

La variation sélectionnée est enqueuée **après** `assets/css/main.css`, donc tous
les sélecteurs définis y prennent le pas (cascade CSS classique). Une dépendance
WordPress est également déclarée pour garantir l'ordre d'inclusion.

## Format

Optionnel : ajoutez en première ligne un commentaire d'en-tête pour
personnaliser le label affiché dans le sélecteur. Sans cet en-tête, le label
est dérivé du nom de fichier (`dark-mode.css` → « Dark mode »).

```css
/* Theme Variation: Été ensoleillé */

:root {
    --color-primary: #f59e0b;
    --color-bg: #fef3c7;
}
```

## Sécurité

L'identifiant de variation transite via `sanitize_key()` côté admin et côté
enqueue. L'enqueue ne charge que les fichiers présents dans ce dossier ; un
identifiant inconnu est ignoré silencieusement.
