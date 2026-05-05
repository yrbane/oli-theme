# Slides (carrousel d'accueil)

Le thème expose un Custom Post Type `oli_slide` permettant de gérer les visuels du carrousel d'accueil — multilingue, avec expiration et lien d'appel à l'action.

## Créer un slide

1. Admin WordPress > **Slides** > **Ajouter un slide**.
2. Renseigner :
   - **Titre** (utilisé comme `aria-label` du slide).
   - **Image à la une** — sera affichée en pleine largeur.
   - **Extrait** (optionnel) — affiché en légende sur l'image (`figcaption`).
   - **Langue** (taxonomie) — pour quelle langue ce slide doit-il apparaître.
   - **Ordre du menu** — détermine l'ordre d'affichage (croissant).
3. Champs custom (à exposer via metabox dans un plan ultérieur — pour l'instant via meta key) :
   - `_oli_slide_link_url` — URL d'un appel à l'action.
   - `_oli_slide_link_label` — texte du bouton CTA (par défaut « En savoir plus »).
   - `_oli_slide_expires_at` — date au format `Y-m-d H:i:s` après laquelle le slide n'est plus affiché.

## Comportement front

- Le carrousel n'apparaît que sur la page d'accueil (`is_front_page()`).
- Seuls les slides **publiés**, **non expirés**, et de la **langue courante** sont rendus.
- Tri par `menu_order` ascendant.

### Avec JavaScript

- Autoplay (5 s par défaut, configurable via `data-interval`).
- Boutons précédent/suivant.
- Swipe tactile (Pointer Events).
- Navigation clavier : flèches gauche/droite quand le carousel a le focus.
- Pause au hover, focus, ou onglet caché.
- Respecte `prefers-reduced-motion: reduce` (désactive l'autoplay et les transitions).

### Sans JavaScript

- Scroll horizontal natif via `scroll-snap-type: x mandatory`.
- Le visiteur défile à la souris ou au doigt.
- Première slide visible immédiatement.

## Multi-sites

- Sur chaque site, créer ses propres slides via l'admin.
- Le code est partagé entre tous les sites Oli (`yrbane/oli-theme`).

## Pour les développeurs

- `OliTheme\Slides\SlideEntity` — DTO immuable d'un slide.
- `OliTheme\Slides\SlideModelInterface::findActive(Language $lang, int $limit = 10): array`
- `OliTheme\Slides\HomeCarouselControllerInterface::build(): HomeCarouselViewModel`
- Le `HomeCarouselViewModel` expose `slides[]`, `autoplay (bool)`, `intervalMs (int)`, `loop (bool)`. Surchargeable en pré-instanciant le VM dans le `PostsModule` factory si besoin de personnalisation par site.
