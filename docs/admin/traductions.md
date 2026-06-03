# Langues & traductions

`Admin → Apparence du thème → Identité & Marque → Langues` gère les langues actives et l'audit des traductions.

## Activer / désactiver une langue

Cocher les langues à activer. La langue **par défaut** est sélectionnée via le bouton radio. URLs frontend :

- Langue par défaut : pas de préfixe (`/`).
- Autres langues : préfixe `/{code}/` (ex. `/en/`).

> Si **une seule langue** est cochée, le sélecteur de langue est **automatiquement masqué** sur le site.

## Lier un article FR à sa version EN

1. Éditer l'article FR.
2. Dans la **metabox « Traductions »** (colonne de droite) : créer un brouillon EN ou lier un article EN existant.
3. Le mécanisme repose sur la meta `_oli_translation_group` partagée.

## Audit des traductions

Sur le même onglet, un **panneau d'audit** liste tous les contenus traduisibles et signale les versions manquantes. Le bouton **« Créer les brouillons manquants »** génère automatiquement les contreparties.

## Bug connu

Lien vers l'article traduit absent sur la home anglaise — voir [#8](https://github.com/yrbane/oli-theme/issues/8).
