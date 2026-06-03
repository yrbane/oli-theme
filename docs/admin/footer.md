# Pied de page (footer)

`Admin → Apparence du thème → Identité & Marque → Pied de page` rassemble tous les réglages du pied de page.

## Logo footer

Champ **Logo footer** (sélecteur médiathèque) : ouvre la bibliothèque WordPress pour choisir une image. Le logo est :

- Affiché en haut du pied de page, centré, taille max 180 px de large.
- Cliquable, renvoie à la page d'accueil.
- Optionnel : si aucune image n'est choisie, aucun logo ne s'affiche (les autres blocs restent intacts).

Format recommandé : **SVG** ou **PNG** transparent, hauteur ~80–120 px.

## Texte libre du footer

Champ **Texte libre du footer** (textarea HTML) : texte affiché tout en bas du pied de page, juste au-dessus de la ligne de copyright. Le HTML est filtré par `wp_kses_post` (liens, gras, italique, paragraphes, listes) — le code arbitraire (`<script>`, attributs `onclick`, etc.) est strippé.

Cas d'usage typiques :
- Mentions légales courtes ;
- Adresse postale du studio ;
- Crédits / partenaires ;
- Petite signature ou citation.

## Réseaux sociaux

`Admin → Apparence du thème → Contact → Réseaux sociaux` : URLs des comptes (Facebook, Instagram, etc.). Les icônes apparaissent au centre du pied de page, avec un **hover aux couleurs de marque**.

## Mentions légales par langue + copyright + bascules

Les autres champs du sous-onglet **Pied de page** :
- **Mentions légales (FR / EN)** : HTML par langue.
- **Modèle de copyright** : avec placeholders `{year}` et `{site}` (défaut : `© {year} {site}`).
- **Cases à cocher** pour afficher / masquer chaque bloc (mentions, réseaux, menu footer).

## Ordre d'affichage du footer (de haut en bas)

1. Logo footer (si défini).
2. Menu footer (si une localisation `footer` est assignée).
3. Icônes réseaux sociaux.
4. Texte libre.
5. Ligne de copyright.
