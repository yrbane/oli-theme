# Gabarits — styles de présentation interchangeables

`Admin → Apparence du thème → Apparence → Gabarits` affiche la galerie des **10 gabarits** disponibles. Un gabarit est un **style de présentation** (mise en page, typographie, effets) que tu peux appliquer **post par post**.

## Comment appliquer un gabarit

1. Édite un article, une page ou un événement.
2. Dans la **metabox « Gabarit (style de présentation) »** (colonne de droite), choisis un gabarit dans le menu déroulant.
3. Sauvegarde. Le post est désormais rendu avec le style choisi.
4. Pour revenir au défaut du thème : choisir **« — Défaut du thème — »**.

## Les 10 gabarits livrés

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

## Comment fonctionne un gabarit techniquement

Chaque gabarit vit dans son propre dossier sous `assets/gabarits/{id}/` :

```
assets/gabarits/magazine/
├── manifest.json     ← métadonnées (name, description, supports, parallax)
├── style.css         ← OBLIGATOIRE : feuille de style propre au gabarit
└── script.js         ← OPTIONNEL : JS pour parallaxe, fade-in, etc.
```

Le thème scanne automatiquement ce dossier au démarrage. Aucun code PHP à modifier pour ajouter un nouveau gabarit.

## Ajouter un gabarit custom

1. Créer un dossier `assets/gabarits/mon-gabarit/`.
2. Créer le `manifest.json` :
   ```json
   {
     "name": "Mon gabarit",
     "description": "Description courte du style.",
     "supports": ["post", "page"],
     "parallax": false,
     "previewColor": "#1e3a8a"
   }
   ```
3. Créer le `style.css` avec des règles préfixées par `.gabarit-mon-gabarit` :
   ```css
   .gabarit-mon-gabarit { background: #f0f0f0; }
   .gabarit-mon-gabarit h1 { font-size: 3rem; }
   ```
4. (Optionnel) Ajouter un `script.js` (ES module).
5. Pousser sur Git — le gabarit apparaît dans le sélecteur de la metabox.

## Comportement front

- Au rendu d'un post avec un gabarit, le thème :
  - Ajoute la classe CSS `gabarit-{id}` sur le `<body>` (via filtre `body_class`).
  - Enqueue automatiquement `style.css` (avec `oli-theme` comme dépendance).
  - Enqueue `script.js` comme module ES si présent.
- Sans gabarit : rendu standard du thème.

## Ajuster la taille des polices et des titres

Chaque gabarit définit ses propres tailles via CSS. Pour fine-tuner globalement la typographie indépendamment du gabarit : `Admin → Apparence du thème → Identité & Marque → Typographie` (4 sliders : taille de base, ratio des titres, taille du menu, taille du footer). Les variables CSS injectées sont consommées par les gabarits qui les supportent.
