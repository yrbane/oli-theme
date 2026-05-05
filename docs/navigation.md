# Navigation

## Locations enregistrées

Pour chaque langue activée, deux locations de menu sont créées automatiquement par `OliTheme\Navigation\MenuLocations` :

- `primary_<code>` — menu principal (header)
- `footer_<code>` — menu pied de page

Activer `fr` et `en` produit donc `primary_fr`, `footer_fr`, `primary_en`, `footer_en`.

## Créer un menu

1. **Apparence > Menus** dans l'admin WordPress.
2. Créer un menu, ajouter pages, posts ou liens custom.
3. Cocher la location voulue dans la section « Réglages du menu » (ex. *Menu principal (Français)*).
4. Sauvegarder.

Recommencer pour chaque langue.

## Fonctionnalités front

- **Desktop** (≥ 768 px) : menu horizontal, sous-menus apparaissant au hover ET au focus clavier (`:hover, :focus-within` natifs CSS, sans JavaScript).
- **Mobile** (< 768 px) : drawer plein écran ouvert via le bouton burger (`[data-nav-toggle]`), fermable avec `Escape` ou un clic sur le bouton.
- **A11y** : `aria-label`, `aria-current="page"`, `aria-expanded`, navigation `Tab` complète. Le drawer n'est pas un piège à focus (l'utilisateur peut tabuler en dehors et revenir).

## Classes CSS exposées (BEM)

- `.nav`, `.nav--desktop`, `.nav--mobile`
- `.nav__list`, `.nav__list--root`, `.nav__sublist`
- `.nav__item`, `.nav__item--current`, `.nav__item--ancestor`, `.nav__item--has-children`, `.nav__item--child`
- `.nav__link`, `.nav__link--child`
- `.nav-toggle`, `.nav-toggle__bar`, `.nav-toggle__label`
- `.site-footer__nav`, `.site-footer__list`, `.site-footer__item`

## Comportement no-JS

Le drawer mobile sans JavaScript reste fermé (`hidden`). Le menu desktop reste pleinement fonctionnel (CSS seul). Le footer fonctionne dans tous les cas.

## Pour les développeurs

- `OliTheme\Navigation\MenuItemEntity` — DTO immuable d'un item (id, label, url, target, isCurrent, isAncestor, depth, children).
- `OliTheme\Navigation\MenuModelInterface::toTree(array $items, int $currentObjectId): array` — convertit la liste plate WP en arbre.
- `OliTheme\Navigation\MenuControllerInterface::buildPrimary/buildFooter(Language $lang): array` — point d'extension.
- Les controllers `Posts\PageController`/`PostController`/`NotFoundController` injectent les menus dans les view-models sous les clés `primaryMenu` et `footerMenu`.
- **Résolution location → menu ID** (issue #5) : `MenuController::buildFor()` traduit la `theme_location` en identifiant de menu via `get_nav_menu_locations()` avant d'appeler `wp_get_nav_menu_items()`. Sans cette traduction, `wp_get_nav_menu_items()` retourne `false` et le menu reste vide même avec une assignation correcte.
