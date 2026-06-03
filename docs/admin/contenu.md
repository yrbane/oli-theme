# Contenu — articles, pages & page d'accueil

## Choisir ce qui apparaît en page d'accueil

`Admin → Réglages → Lecture` (menu WordPress standard) propose 2 modes :

### Option A — Les derniers articles publiés

Coche **« La page d'accueil affiche : Vos derniers articles »**.

→ La home rend automatiquement la liste des articles récents sous forme de **cartes** : vignette à gauche, titre h3, date, extrait, le tout cliquable. Aucun travail supplémentaire — il suffit de publier des articles.

C'est le mode recommandé pour un site éditorial / blog où le flux d'actualité est important.

### Option B — Une page statique

Coche **« La page d'accueil affiche : Une page statique »** puis sélectionne dans **Page d'accueil** une page WordPress de ton choix (typiquement une page nommée `Accueil`).

→ La home rend cette page comme une page normale (avec ses blocs Gutenberg, son gabarit, etc.). C'est idéal pour une présentation soignée avec un design custom.

⚠ Pour cette option, **veille à ce que la page choisie soit publiée** et qu'elle ait du contenu — sinon la home apparaît vide.

### Multilingue : 1 home par langue

Si le site est en plusieurs langues, **chaque langue a sa propre page d'accueil**. Tu n'as **rien à régler côté Réglages WordPress** pour ça — le thème gère le routage automatiquement :

- **Option A** (derniers articles) : `/` montre les articles FR, `/en/` montre les articles EN. Les articles sont filtrés par langue automatiquement.
- **Option B** (page statique) : visiter `/en/` rend automatiquement la **traduction** de la page d'accueil FR si elle existe (groupe `_oli_translation_group` — voir le guide *Langues & traductions*). Sans traduction, la page FR est servie en repli sur `/en/`.

Donc pour avoir une vraie home anglaise distincte avec ton design custom : crée une page EN puis lie-la à la page FR via la metabox Traductions de la page FR. C'est tout.

## Articles

`Admin → Articles → Ajouter` : créer un article comme dans n'importe quel WordPress. Le thème affiche automatiquement :

- L'**image mise en avant** en grand sous le titre.
- Le **titre** centré.
- Le **fil d'Ariane** (Accueil → Article).
- La **largeur de lecture** optimisée (~65 caractères par ligne).

Pour appliquer un style particulier (mise en page magazine, story photo, brutalist, etc.), choisir un **gabarit** dans la metabox latérale → voir le guide *Gabarits*.

## Pages

Comme les articles, mais pour les contenus statiques (À propos, Disciplines, Contact, etc.).

`Admin → Pages → Ajouter` :
- Titre + contenu (blocs Gutenberg).
- Image mise en avant optionnelle.
- Gabarit optionnel (idem articles).
- Langue assignée automatiquement à la langue par défaut ; pour une page EN : créer la page EN et la **lier** à la page FR via la metabox Traductions (voir le guide dédié).

## Cartes d'article home

Quand la home est en mode « Derniers articles » (option A), chaque article apparaît comme une **carte** :

- **Vignette** à gauche (image mise en avant, taille `large` recadrée en `object-fit: cover` 160 × 120 px). Sans image, un dégradé subtil remplace la vignette.
- **Titre** de taille moyenne (`h3`, ~1–1.35 rem).
- **Date** de publication.
- **Extrait** si renseigné (sinon les premiers mots du contenu).

La carte entière est cliquable. En mobile (< 560 px), la vignette passe au-dessus du titre sur toute la largeur. **Pas de titre « Actualités »** au-dessus des cartes — les vignettes parlent d'elles-mêmes.
