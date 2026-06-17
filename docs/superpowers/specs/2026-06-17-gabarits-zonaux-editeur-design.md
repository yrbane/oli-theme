# Gabarits zonaux : formulaire d'édition plein écran — Design

**Date :** 2026-06-17
**Statut :** Validé (approche A)

## Objectif

Pour un post/page/événement dont le gabarit est **zonal** (au moins une zone
définie dans son `manifest.json`), remplacer l'éditeur Gutenberg par un
**formulaire dédié occupant la colonne principale**, avec un **rich-editor
basique** par zone texte et les pickers média existants pour les zones
image/galerie. Les 10 gabarits « CSS pur » (sans zone) conservent Gutenberg
inchangé.

## Contexte

État actuel (cf. `src/Gabarits/`) :

- 2 gabarits zonaux : `story`, `triptyque` (zones dans `manifest.json` +
  `template.html.tpl`). 10 gabarits CSS pur sans zone.
- Le contenu des zones est saisi via une **metabox en sidebar** rendue par
  `GabaritMetabox.php`, à côté de Gutenberg :
  - zone **texte** → `<textarea>` brut (`oli_gabarit_zone[{id}][text]`) ;
  - zone **image** → picker `wp.media` (`oli_gabarit_zone[{id}][imageId]`) ;
  - zone **galerie** → picker `wp.media` multi (`oli_gabarit_zone[{id}][imageIdsCsv]`).
- Persistance : `ZoneContentRepository` → postmeta JSON `_oli_gabarit_zones`.
- Rendu front : `GabaritRenderer` → inclut `template.html.tpl` avec `$zones`.

## Fait technique structurant

`wp_editor()` (TinyMCE) ne s'initialise correctement que dans **l'éditeur
classique**, jamais dans l'écran du block editor. Avoir un rich-editor par zone
**impose** donc de désactiver Gutenberg pour les posts zonaux — ce qui réalise
de fait le « formulaire qui remplace Gutenberg » demandé.

## Architecture (approche A — 100 % PHP, aucun build JS)

### 1. Détection « gabarit zonal »

Ajouter une méthode au value object :

```php
// src/Gabarits/Gabarit.php
public function isZonal(): bool
{
    return $this->zones !== [];
}
```

Source de vérité unique, réutilisée partout (bascule éditeur + rendu metabox).

### 2. Bascule d'éditeur

`GabaritModule` enregistre le filtre WordPress :

```php
add_filter('use_block_editor_for_post', [$this, 'disableBlockEditorForZonal'], 10, 2);
```

```php
public function disableBlockEditorForZonal(bool $useBlockEditor, \WP_Post $post): bool
{
    $gabarit = $this->resolver->resolve($post->ID);   // ?Gabarit
    if ($gabarit !== null && $gabarit->isZonal()) {
        return false;   // éditeur classique
    }
    return $useBlockEditor;   // Gutenberg inchangé (CSS pur / aucun gabarit)
}
```

Pas de gabarit ou gabarit CSS pur → comportement Gutenberg intact.

### 3. Masquage du champ « contenu » natif

`admin_body_class` ajoute la classe `oli-zonal-editor` sur l'écran d'édition
d'un post zonal :

```php
add_filter('admin_body_class', [$this, 'flagZonalEditorBody']);
```

Le filtre lit le `$post` courant (`get_post()` sur les écrans `post.php`/
`post-new.php`) et n'ajoute la classe que si le gabarit est zonal.

CSS admin (enqueue sur `admin_enqueue_scripts`, écrans d'édition uniquement) :

```css
body.oli-zonal-editor #postdivrich { display: none; }
```

Le titre reste affiché. Le formulaire de zones prend la place du contenu.

### 4. Formulaire de zones dans la colonne principale

Le rendu des **champs de zones** migre de la metabox sidebar vers le hook
`edit_form_after_title` (colonne principale, sous le titre) :

```php
add_action('edit_form_after_title', [$this, 'renderZoneForm']);
```

`renderZoneForm()` ne s'exécute que si le gabarit du post est zonal. Pour chaque
zone :

- **texte** → `wp_editor()` toolbar restreinte :

  ```php
  wp_editor($content->text, "oli_zone_{$zone->id}", [
      'textarea_name' => "oli_gabarit_zone[{$zone->id}][text]",
      'media_buttons' => false,
      'quicktags'     => false,
      'textarea_rows' => 8,
      'tinymce'       => [
          'toolbar1' => 'bold,italic,bullist,numlist,link,unlink',
          'toolbar2' => '',
      ],
  ]);
  ```

  Le `textarea_name` conserve **exactement** le nom de champ actuel : le hook de
  sauvegarde n'est pas touché.

- **image** / **galerie** → markup + JS `wp.media` **réutilisés tels quels**
  depuis `GabaritMetabox` (extraits dans des méthodes privées partagées).

### 5. Sélecteur de gabarit

Reste dans la **metabox sidebar** (`GabaritMetabox`), visible aussi bien en mode
Gutenberg qu'en mode classique (WordPress affiche les metaboxes classiques dans
les deux écrans). C'est le point d'entrée pour **basculer** un post vers/depuis
un gabarit zonal. La notice « Enregistrez la page pour faire apparaître les
champs des zones » est conservée — la bascule d'éditeur nécessite un
enregistrement + rechargement (limite inhérente à WordPress, inévitable).

La metabox ne rend donc plus les champs de zones (déplacés en §4) ; elle ne
garde que le sélecteur + la notice.

### 6. Sauvegarde & rendu front

**Inchangés.** Mêmes noms de champs `oli_gabarit_zone[...]`, même hook
`save_post`, même `ZoneContentRepository`, même `GabaritRenderer`. Aucune
migration de données.

## Découpage des responsabilités

- `Gabarit::isZonal()` — prédicat métier.
- `GabaritModule` — câblage des hooks WordPress (filtre éditeur, body class,
  enqueue CSS admin, `edit_form_after_title`).
- `GabaritMetabox` — sélecteur + notice (sidebar) **et** rendu du formulaire de
  zones (colonne principale) ; les contrôles image/galerie sont factorisés en
  méthodes privées réutilisables. Le rendu d'une zone texte passe par
  `wp_editor()`.
- `GabaritZoneEditor` (nouveau, optionnel) — si `GabaritMetabox` devient trop
  gros, extraire le rendu du formulaire de zones dans une classe dédiée
  (responsabilité unique : produire le HTML d'édition d'une liste de zones). À
  décider au moment du plan selon la taille du fichier.

## Tests (TDD)

1. **`Gabarit::isZonal()`** — vrai si zones non vides, faux sinon (test pur, sans
   WordPress).
2. **`GabaritModule::disableBlockEditorForZonal()`** — renvoie `false` pour un
   post à gabarit zonal, conserve la valeur entrante pour un gabarit CSS pur ou
   l'absence de gabarit (Brain/Monkey : stub `resolver`).
3. **Rendu zone texte** — vérifie que le rendu d'une zone texte invoque
   `wp_editor()` avec `media_buttons => false`, le `toolbar1` restreint et le
   `textarea_name` attendu (Brain/Monkey : expectation sur `wp_editor`).
4. **`admin_body_class`** — ajoute `oli-zonal-editor` uniquement pour un post
   zonal.
5. **`SelfContainedThemeTest`** doit rester vert (aucune dépendance externe
   ajoutée).

## Hors périmètre (YAGNI)

- Pas de bascule d'éditeur en AJAX sans rechargement (limite WordPress assumée).
- Pas de migration du `post_content` Gutenberg existant vers les zones.
- Pas de nouveau type de zone.
- Pas de réécriture des 10 gabarits CSS pur.

## Edge case assumé

Un post déjà rempli en Gutenberg puis basculé sur un gabarit zonal conserve son
`post_content` en base, mais celui-ci n'est plus rendu (seules les zones
s'affichent). Comportement déjà en vigueur côté front.
