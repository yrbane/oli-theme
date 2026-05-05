# Architecture du thème oli-theme

## Vue d'ensemble

Le thème suit un pattern **MVC strict** appliqué à WordPress :

- **Modèles** (`src/*/...Model.php`) : encapsulation de la donnée. Aucun HTML, aucun `echo`.
- **Contrôleurs** (`src/*/...Controller.php`) : orchestrent récupération des données et préparation du `ViewModel`. Aucun HTML.
- **Vues** (`templates/**/*.html.tpl`) : templates Lunar uniquement. Aucun appel WP, aucune logique métier.
- **Modules** (`src/*/...Module.php`) : un par domaine fonctionnel (I18n, SEO, Events, ...). Enregistrent les hooks WordPress.

## Composants Core

| Composant | Rôle |
|-----------|------|
| `Theme` | Bootstrap singleton, accède au conteneur, branche les hooks fondateurs |
| `Container` | Conteneur de dépendances minimaliste (PSR-11 like) |
| `Core\ViewRenderer` | Wrapper de Lunar Template Engine |
| `Core\AssetManager` | Enqueue CSS / JS avec versioning filemtime |
| `Core\RequestContext` | Wrapper immuable de la requête HTTP |
| `Core\HookRegistrar` | Wrapper testable de add_action / add_filter |
| `Core\ModuleInterface` | Contrat des modules fonctionnels |
| `Core\PostTypeInterface` | Contrat des classes enregistrant un CPT |

## Flow d'une requête

```
HTTP /fr/cours
        |
        v
.htaccess -> index.php (theme-bridge)
        |
        v
Theme::container()->get(...Controller::class)->renderXxx()
        |
        +-- Model::find() -> DTO
        +-- SeoController::buildHead()
        +-- compose ViewModel
        v
ViewRenderer::render('pages/page', $vm)
        v
Lunar compile le template (.html.tpl)
        v
HTML envoye
```

## Conventions

- Code en **anglais**, PHPDoc et commentaires en **français**.
- `declare(strict_types=1);` dans tous les fichiers PHP.
- Classes finales par défaut (sauf si extension intentionnelle).
- DTO immuables (`final readonly class`).
- Tests TDD systématiques (Red → Green → Refactor).
- Convention de fichiers : `src/Domain/Class.php` correspond à `OliTheme\Domain\Class`.

## Plans d'implémentation

Le développement suit 10 plans séquentiels (cf. `docs/superpowers/plans/`). Chaque plan livre un thème fonctionnel et testable :

1. **Foundation** (présent plan) — socle MVC, Container, Core, CI
2. **I18n** — système multilingue custom
3. **Templates & Posts/Pages** — layout complet, partials, pages
4. **Navigation** — menus, walker, switcher de langue
5. **Settings** — page d'options (bannière, footer, réseaux)
6. **Slides + Carrousel** — CPT slide + JS carrousel
7. **Events** — CPT événements, archive, fiche
8. **Contact** — formulaire OOP sécurisé
9. **SEO base** — modèle, contrôleur, sitemap, JSON-LD, redirections
10. **SEO avancé** — Flesch FR, score 21 critères, dashboard
