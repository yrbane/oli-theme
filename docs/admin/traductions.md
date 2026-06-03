# Langues & traductions

Le thème gère le multilingue **sans plugin** : chaque contenu (article, page, événement, slide) peut avoir une **version par langue**, et le thème route automatiquement les visiteurs vers la bonne version.

## 1. Activer les langues

`Admin → Apparence du thème → Identité & Marque → Langues` :

1. **Cocher** les langues à activer (Français, Anglais, etc.).
2. **Choisir la langue par défaut** via le bouton radio.
3. **Sauvegarder**.

URLs frontend :

| Langue | URL |
|---|---|
| Langue **par défaut** | pas de préfixe (`/`, `/contact`, …) |
| Autres langues | préfixe `/{code}/` (`/en/`, `/en/contact`, …) |

> Si **une seule langue** est cochée, le sélecteur de langue est **automatiquement masqué** sur le site (logique : pas besoin de choisir).

## 2. Créer une traduction d'un contenu existant

C'est le workflow le plus courant : tu as un article FR publié, tu veux la version EN.

1. Édite l'article FR (`Admin → Articles → ton article`).
2. Repère la metabox **« Traductions »** dans la colonne de droite.
3. Pour la langue cible (EN), 2 options :
   - **Créer un brouillon** → un nouvel article EN vide est créé, lié au FR. Tu cliques pour l'éditer et tu remplis le contenu en anglais.
   - **Lier un article existant** → si tu as déjà rédigé la version EN, sélectionne-la dans le menu déroulant pour les lier.
4. **Sauvegarder** l'article FR.

→ La metabox affiche désormais la liste : `🇫🇷 FR (cet article) · 🇬🇧 EN (titre de la traduction)`. Le sélecteur de langue côté front basculera automatiquement entre les deux.

## 3. Créer un contenu directement dans une autre langue

1. `Admin → Articles → Ajouter`.
2. Dans la metabox **« Langue »** (colonne de droite), choisir la langue (par défaut : la langue par défaut du site).
3. Rédiger et publier.
4. Si tu veux le lier à une version dans une autre langue : ouvre l'article que tu veux lier et utilise la metabox **« Traductions »** comme au paragraphe 2.

## 4. Auditer les traductions manquantes

Sur l'onglet `Apparence du thème → Identité & Marque → Langues`, en bas de page : un **panneau d'audit** liste **tous les contenus traduisibles** (articles, pages, événements, slides) et signale lesquels n'ont pas de version dans chaque langue activée.

Le bouton **« Créer les brouillons manquants »** génère automatiquement les contreparties pour tous les contenus manquant une traduction. Tu n'as plus qu'à les éditer pour remplir le contenu traduit.

## 5. Comportement automatique côté front

Le thème route les visiteurs sans que tu n'aies rien à configurer :

- **Sélecteur de langue** dans l'en-tête (drapeaux) : un visiteur sur l'article FR voit `🇬🇧 EN` actif (s'il y a une traduction). Cliquer → bascule sur l'article EN.
- **Sans traduction** : le drapeau EN est désaturé et pointe vers `/en/` (la home anglaise) — pas vers un 404.
- **Cookie** : la dernière langue choisie est mémorisée pour les pages sans URL explicite (ex. accès direct à une URL sans préfixe).

## 6. Page d'accueil par langue

Quand `Réglages → Lecture` est sur **« Une page statique »** :
- Visiter `/` rend la page d'accueil dans la langue par défaut.
- Visiter `/en/` rend automatiquement la **traduction** de cette page si elle existe (lien `_oli_translation_group`). Sans traduction, la page par défaut est servie en repli.

Pour avoir une vraie home anglaise avec ton design custom : crée une page `Home (EN)`, lie-la à la page d'accueil FR via la metabox Traductions, c'est tout.

Quand `Réglages → Lecture` est sur **« Vos derniers articles »** :
- `/` montre les articles FR, `/en/` montre les articles EN. Le filtre se fait automatiquement par la taxonomy `language` sur chaque post.

## 7. Slides du carousel par langue

Chaque slide (CPT `oli_slide`) appartient à **une langue**, fixée via la metabox **« Langue »** lors de la création. Le carousel de `/` affiche les slides FR, celui de `/en/` les slides EN. Pour les lier entre langues : metabox Traductions comme pour les articles.

## 8. Menus par langue

`Admin → Apparence → Menus` : sélectionner la langue en haut de l'écran avant d'éditer. Chaque langue peut avoir son propre menu assigné aux emplacements **Menu principal** / **Menu footer**.

## Référence technique

- Taxonomie `language` sur `post`, `page`, `oli_event`, `oli_slide`, `oli_contact_log`.
- Postmeta `_oli_translation_group` : UUID partagé entre les différentes versions linguistiques d'un même contenu.
- API : `TranslationModel::getTranslations(int $postId): array<string, int>` retourne le mapping `code-langue → post-id`.
