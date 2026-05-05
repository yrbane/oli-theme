# ADR 0004 — Layout Lunar unique + bridge minimal + macros lazy

**Statut :** accepté
**Date :** 2026-05-05
**Contexte :** Plan 3 — Templates et Posts/Pages.

## Décision

1. **Un seul layout racine** : `templates/layouts/base.html.tpl`. Chaque template de page l'`extends` et remplit le bloc `main`.
2. **Pontage WordPress minimal** : un fichier PHP par entrée de la WP template hierarchy (`page.php`, `single.php`, `archive.php`, `search.php`, `404.php`, `front-page.php`, `index.php`). Chaque pontage tient en une ligne logique : `echo Theme::container()->get(<Controller>::class)->render*();`.
3. **Variables globales** câblées au boot via `Theme::bootstrapViewRenderer()` (`siteName`, `siteUrl`, `homeUrl`, `themeUri`, `charset`, `currentYear`).
4. **`wp_head()` / `wp_footer()` exposés en macros lazy** (`##wpHead()##`, `##wpFooter()##`) plutôt qu'en variables précalculées, pour permettre aux plugins WP d'injecter leur HTML au moment du rendu.
5. **Interfaces extraites** pour les services injectés dans les controllers (`PostModelInterface`, `LanguageRegistryInterface`, `LanguageResolverInterface`, `LanguageSwitcherControllerInterface`). Permet le mocking PHPUnit 11 (les `final classes` ne sont pas mockables) tout en respectant le DIP de SOLID.

## Alternatives rejetées

- **Plusieurs layouts (`layouts/full.html.tpl`, `layouts/narrow.html.tpl`)** : reporté. Aucun besoin avéré au cycle 1, ajoute de la complexité avant qu'elle soit utile (YAGNI).
- **Dispatcher unique via `template_include`** : fonctionnel mais opaque — multiplie les hooks à mocker dans les tests, et les pontages PHP individuels sont plus lisibles pour les développeurs WordPress.
- **`wp_head()` / `wp_footer()` capturés au boot** : aurait gelé la sortie des plugins. Les macros préservent la dynamique au rendu.
- **Mockery au lieu d'extraire des interfaces** : Mockery n'est pas dans `require-dev` et autoriserait le mocking des `final classes`, mais ajouterait une dépendance et une syntaxe parallèle à PHPUnit. Les interfaces narrow respectent l'ISP et restent dans la stack PHPUnit pure.

## Conséquences

- ✅ Lecture immédiate : « quel template WP rend `single.php` ? → `theme-bridge/single.php` → `PostController::renderSingle()` ».
- ✅ Tests unitaires faciles : on instancie le controller (avec mocks d'interfaces) et on vérifie le HTML.
- ✅ Évolutivité : pour ajouter un type d'écran (par exemple `single-oli_event.php`), on ajoute un fichier de pontage et un controller.
- ✅ Plugins WP fonctionnels : les macros `wpHead`/`wpFooter` capturent dynamiquement.
- ❌ Léger duplicata entre pontages (pattern `echo Theme::container()->get(...)`) — accepté pour la lisibilité.
- ❌ Une interface par service mockable — surface API à maintenir si un service évolue. Acceptable car les interfaces sont narrow (1-4 méthodes) et alignées sur l'usage réel.

## Note sur `lunar-template`

L'accès hybride array/objet via la notation pointée `[[ obj.prop ]]` a été demandé en amont via [yrbane/lunar-template#14](https://github.com/yrbane/lunar-template/issues/14) et livré dans le commit `2de89f0`. Sans ce support, on aurait dû convertir tous les DTO en arrays côté controller (refactor lourd) ou introduire un wrapper de conversion automatique dans `ViewRenderer` (magie cachée). Le fix amont préserve le pattern DTO immuable.
