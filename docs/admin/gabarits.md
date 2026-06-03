# Gabarits — styles de présentation interchangeables

`Admin → Apparence du thème → Apparence → Gabarits` affiche la galerie des **12 gabarits** disponibles. Un gabarit est un **style de présentation** (mise en page, typographie, effets) que tu peux appliquer **post par post**.

Deux familles :
- **Skins CSS** (10 gabarits) — un changement de look sans modifier la structure de tes contenus. Tu écris ton article normalement, le gabarit applique sa mise en page.
- **Gabarits zonaux** (2 gabarits, marqués ◆ dans le sélecteur) — le gabarit déclare des **zones** (texte, image, galerie) que tu remplis depuis l'éditeur ; le rendu front est entièrement piloté par le template du gabarit.

## Comment appliquer un gabarit

1. Édite un article, une page ou un événement.
2. Dans la **metabox « Gabarit & zones »** (colonne de droite ou en bas), choisis un gabarit dans le menu déroulant.
3. **Si le gabarit est zonal (◆)** : des champs spécifiques apparaissent (texte enrichi, sélecteur d'image, sélecteur multi-images pour galerie). Remplis ce qui te plaît — les zones vides ne s'affichent pas.
4. Sauvegarde. Le post est désormais rendu avec le style choisi.
5. Pour revenir au défaut du thème : choisir **« — Défaut du thème — »**.

## Les 12 gabarits livrés

### Skins CSS (s'appliquent au contenu standard du post)

| ID | Nom | Description |
|---|---|---|
| `classic` | **Classique** | Sobre, lecture longue, 65 caractères de large. |
| `magazine` | **Magazine** | Deux colonnes desktop, lettrine, séparateurs élégants. |
| `minimal` | **Minimal** | Beaucoup d'espace blanc, typographie fine, contraste réduit. |
| `editorial` | **Éditorial** | Pull quotes prononcées, images hors-gabarit, titres italique. |
| `photo-story` | **Photo Story** | Image mise en avant en hero plein écran avec **parallaxe**. |
| `brutalist` | **Brutalist** | Typo monospace, bordures noires épaisses, esthétique radicale. |
| `soft-pastel` | **Soft Pastel** | Coins arrondis, dégradés doux, palette pastel. |
| `newsletter` | **Newsletter** | Colonne étroite serif centrée, lettrine, ambiance lettre. |
| `cinema` | **Cinéma** | Mode sombre, titres très grands, fade-in au scroll. |
| `zen` | **Zen** | Maximum d'espace, hauteur de ligne généreuse, séparateurs floraux. |

### Gabarits zonaux (◆ — structure définie par le gabarit, contenu rempli zone par zone)

| ID | Nom | Zones |
|---|---|---|
| `triptyque` | **Triptyque** | Intro (texte) → Image héros → Développement (texte) |
| `story` | **Story photo** | Intro (texte) → Galerie photos → Citation → Conclusion (texte) |

## Architecture zonale (gabarits ◆)

Un gabarit zonal déclare ses zones dans son `manifest.json` :

```json
{
  "name": "Story photo",
  "description": "...",
  "supports": ["post", "page"],
  "zones": [
    {"id": "intro",      "type": "text",    "label": "Introduction"},
    {"id": "gallery",    "type": "gallery", "label": "Galerie photo"},
    {"id": "quote",      "type": "text",    "label": "Citation"},
    {"id": "conclusion", "type": "text",    "label": "Conclusion"}
  ]
}
```

**3 types de zones disponibles** :
- `text` — texte enrichi (HTML autorisé via `wp_kses_post`).
- `image` — image unique (sélecteur `wp.media`, retourne un ID d'attachment).
- `gallery` — multiple images (sélecteur `wp.media` multi).

Chaque type a son contrôle dédié dans la metabox de l'éditeur :
- text → `textarea` simple.
- image → bouton « Choisir une image » + aperçu + bouton « Retirer ».
- gallery → bouton « Choisir / modifier la galerie » avec prévisualisation des vignettes.

Le rendu HTML est piloté par le fichier `template.html.tpl` du gabarit, qui reçoit un tableau `$zones` (zoneId → HTML pré-rendu et sûr).

## Comment fonctionne un gabarit techniquement

Chaque gabarit vit dans son propre dossier sous `assets/gabarits/{id}/` :

```
assets/gabarits/story/
├── manifest.json         ← métadonnées (name, description, supports, parallax, zones)
├── style.css             ← OBLIGATOIRE : feuille de style propre au gabarit
├── template.html.tpl     ← OPTIONNEL : pour les gabarits zonaux uniquement
└── script.js             ← OPTIONNEL : JS pour parallaxe, fade-in, etc.
```

Le thème scanne automatiquement ce dossier au démarrage. Aucun code PHP à modifier pour ajouter un nouveau gabarit.

## Ajouter un gabarit custom

### Skin CSS (sans zones)

1. Créer un dossier `assets/gabarits/mon-skin/`.
2. Créer le `manifest.json` :
   ```json
   {
     "name": "Mon skin",
     "description": "Description courte du style.",
     "supports": ["post", "page"],
     "parallax": false,
     "previewColor": "#1e3a8a"
   }
   ```
3. Créer le `style.css` avec des règles préfixées par `.gabarit-mon-skin` :
   ```css
   .gabarit-mon-skin { background: #f0f0f0; }
   .gabarit-mon-skin h1 { font-size: 3rem; }
   ```
4. (Optionnel) Ajouter un `script.js` (ES module).
5. Pousser sur Git — le gabarit apparaît dans le sélecteur de la metabox.

### Gabarit zonal (avec zones)

Comme ci-dessus, plus :
- Déclarer le tableau `zones` dans le manifest (ids stables, type, label, help optionnel).
- Créer `template.html.tpl` (fichier PHP malgré l'extension) :
   ```php
   <?php /** @var array<string, string> $zones */ ?>
   <article class="mon-gabarit">
     <?php if (!empty($zones['intro'])): ?>
       <header><?= $zones['intro'] ?></header>
     <?php endif; ?>
     <?php if (!empty($zones['hero'])): ?>
       <figure><?= $zones['hero'] ?></figure>
     <?php endif; ?>
   </article>
   ```
- Le tableau `$zones` est déjà escapé (image = balise `<img>` avec srcset, gallery = HTML grille, text = `wp_kses_post`).

## Comportement front

- Au rendu d'un post avec un gabarit, le thème :
  - Ajoute la classe CSS `gabarit-{id}` sur le `<body>` (via filtre `body_class`).
  - Enqueue automatiquement `style.css` (avec `oli-theme` comme dépendance).
  - Enqueue `script.js` comme module ES si présent.
  - **Pour les gabarits zonaux** : remplace `bodyHtml` du template page par le rendu du `template.html.tpl` du gabarit, avec les zones remplies.
- Sans gabarit : rendu standard du thème.

## Ajuster la taille des polices et des titres

Chaque gabarit définit ses propres tailles via CSS. Pour fine-tuner globalement la typographie indépendamment du gabarit : `Admin → Apparence du thème → Identité & Marque → Typographie` (4 sliders : taille de base, ratio des titres, taille du menu, taille du footer). Les variables CSS injectées sont consommées par les gabarits qui les supportent (`var(--font-size-h1, ...)`, etc.).
