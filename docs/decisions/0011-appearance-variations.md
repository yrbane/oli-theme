# ADR 0011 — Apparence : variations CSS, bandeau et polices configurables

**Statut :** Accepté — Cycle 2

## Contexte

Le thème doit être réutilisable sur plusieurs sites (Olikalari, Satsangham,
Margeye…). Chaque site a son identité visuelle : palette, typographie, image
de bandeau. Forker le thème ou maintenir des branches par site est non
soutenable. Il faut un mécanisme de personnalisation **non destructif** qui
n'altère pas le code partagé.

## Décision

Créer un module `Appearance` qui expose **trois axes** de personnalisation
côté admin, chacun rangé dans une option WP autonome :

1. **Variations CSS** : un fichier `*.css` par variation dans
   `assets/css/variations/`. Découverte automatique via scan + en-tête
   `/* Theme Variation: ... */` pour le label. Choix dans
   `Apparence > Variations CSS`. Chargée après `main.css` avec dépendance WP.

2. **Bandeau pages internes** : URL d'image stockée dans `oli_internal_banner_image`,
   choisie via le media uploader WP natif. Override dynamique via une CSS
   custom-property (`--oli-internal-banner-url`) injectée par
   `wp_add_inline_style`. Fallback sur `assets/img/banner.jpg` en variable
   CSS.

3. **Police des titres** : catalogue complet Google Fonts (~1 900 familles)
   bundlé en local (`assets/data/google-fonts.json`). Picker en combobox
   HTML5 (input + datalist) avec preview live. Au front, enqueue de la
   stylesheet Google + override `!important` sur `h1-h6, .banner__title,
   .carousel-fullscreen__title`.

## Conséquences

**Positives :**
- Une seule base de code partageable par tous les sites
- Pas de fork nécessaire pour personnaliser le visuel
- L'admin peut tester en live sans toucher au code (variation = upload de fichier .css)
- Catalogue Google Fonts hors-ligne (pas de dépendance réseau au runtime)

**Négatives / compromis :**
- Une variation peut casser l'UX si elle override mal le base CSS (responsabilité de l'auteur de variation)
- L'override `!important` sur la police titre fait obstacle aux variations qui voudraient la surcharger — acceptable car la police est explicitement choisie par l'admin
- Le catalogue Google Fonts (~150 ko JSON) ajoute du poids au repo, mais reste léger comparé aux SVG embarqués

**Sécurité :**
- `sanitize_key()` côté admin sur le slug de variation (path-traversal impossible)
- Validation contre la liste blanche des fichiers `.css` détectés à l'enqueue
- Nom de police filtré par regex `[A-Za-z0-9 ]` au front (immune injection CSS)
- URL de bandeau via `esc_url_raw` côté admin, `esc_url` côté front

## Alternatives écartées

- **Customizer WP** : trop verbeux pour 3 axes, UX mobile médiocre, JS lourd (Backbone)
- **Page de settings classique avec onglets** : aurait gonflé `ThemeSettingsPage` (déjà 6 onglets) et mélangé identité de marque (banner/social) avec apparence pure
- **Charger Google Fonts API à la volée côté admin** : nécessite une clé API + dépendance réseau ; le catalogue local est mis à jour manuellement quand nécessaire
