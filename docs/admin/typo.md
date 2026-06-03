# Typographie

`Admin → Apparence du thème → Identité & Marque → Typographie` propose 4 champs pour piloter toute l'échelle typographique du site sans toucher au CSS.

## Les 4 réglages

| Réglage | Effet | Plage | Défaut |
|---|---|---|---|
| **Taille de base (rem)** | Taille du texte courant (1 rem = 16 px). | 0.75 – 1.5 | 1.0 |
| **Ratio d'échelle des titres** | Suite géométrique entre `h6` et `h1`. Plus le ratio est élevé, plus les titres sont contrastés. | 1.05 – 1.6 | 1.2 |
| **Taille du menu (rem)** | Taille de la typo du menu principal. | 0.6 – 1.4 | 0.875 |
| **Taille du pied de page (rem)** | Taille de la typo du pied de page. | 0.6 – 1.4 | 0.875 |

## Comment le ratio fonctionne

Avec `base = 1.0` et `ratio = 1.2`, l'échelle générée est :

| Tag | Calcul | Taille |
|---|---|---|
| h6 | base × ratio | **1.20 rem** (19.2 px) |
| h5 | base × ratio² | **1.44 rem** (23 px) |
| h4 | base × ratio³ | **1.73 rem** (27.6 px) |
| h3 | base × ratio⁴ | **2.07 rem** (33.2 px) |
| h2 | base × ratio⁵ | **2.49 rem** (39.8 px) |
| h1 | base × ratio⁶ | **2.99 rem** (47.8 px) |

Augmenter le ratio à `1.4` rend les titres beaucoup plus présents (h1 ≈ 7.5 rem) ; le baisser à `1.1` les tasse vers la taille de base.

## Comment ces réglages se matérialisent

Le thème injecte un bloc CSS au début du `<head>` qui définit les variables :

```css
:root {
    --font-size-base: 1rem;
    --font-size-h1: 2.986rem;
    --font-size-h2: 2.488rem;
    /* ... */
    --font-size-menu: 0.875rem;
    --font-size-footer: 0.875rem;
}
```

Les balises `h1`–`h6` (et le menu / footer là où le CSS le supporte) consomment ces variables avec un repli sur des valeurs raisonnables en cas d'absence.

## Sécurité

Toute valeur hors plage est **automatiquement clampée** dans les bornes acceptées (voir tableau ci-dessus). Impossible d'envoyer un thème dans un état illisible.

## Si le menu déborde sur deux lignes

Si vous réduisez la taille du menu et que cela ne suffit pas, voir le guide **Menus** (la bascule en burger est déjà active à 992 px).
