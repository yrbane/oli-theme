# Drapeaux du sélecteur de langue

Le `LanguageSwitcherController` cherche un drapeau dans cet ordre :

1. `assets/img/flags/{code}.svg` (recommandé)
2. `assets/img/flags/{code}.png`
3. Fallback : emoji Unicode du `Language` (`🇫🇷`, `🇬🇧`, `🇮🇹`, `🇪🇸`)

## Drapeaux livrés

Pré-amorçage avec [flag-icons](https://github.com/lipis/flag-icons) (MIT) :

| Code | Fichier        | Source                          |
|------|----------------|---------------------------------|
| `fr` | `fr.svg`       | flag-icons `flags/4x3/fr.svg`   |
| `en` | `en.svg`       | flag-icons `flags/4x3/gb.svg`   |
| `it` | `it.svg`       | flag-icons `flags/4x3/it.svg`   |
| `es` | `es.svg`       | flag-icons `flags/4x3/es.svg`   |

## Remplacer par d'autres drapeaux (TitanUI, ronds, autres styles…)

1. Téléchargez vos SVG/PNG (ex. depuis
   [TitanUI 200 Free SVG/PNG Flags](https://www.titanui.com/128081-200-free-svg-png-flags-in-figma/)).
2. Renommez chaque fichier par le **code ISO 2 lettres** de la langue
   (`fr.svg`, `en.svg`, `it.svg`, `es.svg`).
3. Écrasez les fichiers existants ici.
4. Aucun code à changer : le contrôleur détecte automatiquement le nouveau
   fichier et l'URL est mise à jour.

## Format conseillé

- **Ratio** : 4:3 (1024×768, 256×192, etc.) — les CSS `.language-switcher__flag--svg`
  utilisent ce ratio pour le rendu.
- **Format** : SVG en priorité (vectoriel, qualité parfaite à toute taille).
  PNG accepté en fallback (préférer 64×48 px minimum).
- **Code** : utilisez `en.svg` pour l'anglais (= drapeau UK `gb` chez la
  plupart des sources). Le thème ne reconnaît que les codes ISO actifs
  dans `LanguageRegistry`.
