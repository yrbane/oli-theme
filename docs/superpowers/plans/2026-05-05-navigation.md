# Navigation Implementation Plan (oli-theme — Cycle 1, Plan 4/10)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the multilingual navigation system: menu locations registered per language, immutable `MenuItemEntity` tree built from WP nav menu items, `MenuController` exposing primary + footer trees, custom Lunar partials for desktop/mobile, vanilla JS for the mobile drawer, BEM CSS module. After this plan, the theme renders real menus per language with full keyboard accessibility.

**Architecture:** A `NavigationModule` registers nav-menu locations (e.g. `primary_fr`, `primary_en`, `footer_fr`, …) for each enabled language at `after_setup_theme`. `MenuController::buildPrimary(Language)` and `buildFooter(Language)` read the WP nav menu, convert flat WP items into a tree of `MenuItemEntity` DTOs, and inject the result into the header/footer view-models built by `Posts/PageController` and `Posts/PostController`. The desktop and mobile menus are two distinct Lunar partials sharing the same data shape; mobile is a CSS drawer hydrated by a small ES module.

**Tech Stack:** Same as Plans 1-3 — PHP `^8.3`, WordPress 6.9+, Lunar Template (`yrbane/lunar-template` `2de89f0+`), PHPUnit 11 + Brain Monkey, PHPStan level 8, PHP-CS-Fixer (PSR-12). Vanilla JS ES module (no build).

**Reference spec:** `docs/superpowers/specs/2026-05-05-oli-theme-design.md` — sections 1.2 (architecture), 4.4 (Module Navigation).

**Out of scope (later plans):** menu builder UI improvements, mega-menus, rich link rendering (icons, badges) — only Plan 4 essentials.

---

## File Structure

### Source (`src/Navigation/`, namespace `OliTheme\Navigation`)

- `src/Navigation/MenuItemEntity.php` — DTO immuable (`id`, `label`, `url`, `target`, `isCurrent`, `isAncestor`, `depth`, `children[]`)
- `src/Navigation/MenuModel.php` — convertit la liste plate WP en arbre, applique la résolution `current`/`ancestor`
- `src/Navigation/MenuModelInterface.php` — interface narrow pour le mocking PHPUnit
- `src/Navigation/MenuController.php` — expose `buildPrimary(Language)` et `buildFooter(Language)`
- `src/Navigation/MenuControllerInterface.php` — interface narrow pour intégration future avec `PageController`/`PostController`
- `src/Navigation/MenuLocations.php` — enregistre les locations par langue (`register_nav_menus`)
- `src/Navigation/NavigationModule.php` — orchestrateur (Container + hook `after_setup_theme`)

### Tests (`tests/Unit/Navigation/`)

- `MenuItemEntityTest.php`
- `MenuModelTest.php`
- `MenuControllerTest.php`
- `MenuLocationsTest.php`
- `NavigationModuleTest.php`

### Templates Lunar

- `templates/partials/nav-desktop.html.tpl`
- `templates/partials/nav-mobile.html.tpl`
- Modify: `templates/partials/header.html.tpl` (replace the placeholder `<a>Accueil</a>` link with `[% include 'partials/nav-desktop.html.tpl' %]` + drawer trigger)

### Assets

- `assets/css/menu.css` — styles BEM desktop + mobile drawer (importé depuis `main.css`)
- `assets/js/menu-mobile.js` — ES module qui ouvre/ferme le drawer + gestion clavier
- `assets/js/main.js` — point d'entrée ES module (init `menu-mobile.js`)
- Modify: `assets/css/main.css` (importe `menu.css`)

### Modifications

- `src/Theme.php` — branche `NavigationModule` au boot, expose le `MenuControllerInterface` et fait pointer les controllers Posts vers lui.
- `src/Posts/PageController.php`, `PostController.php`, `NotFoundController.php` — injectent un `MenuControllerInterface` et l'ajoutent au view-model sous la clé `nav` (objet `NavigationViewModel` minimal contenant `primary` + `footer`).
- `src/Posts/PostsModule.php` — factories mises à jour pour passer le `MenuControllerInterface`.
- `src/Core/AssetManager.php` — `enqueueFront()` câble aussi `main.js` via `wp_enqueue_script_module` (déjà partiellement présent depuis Plan 1, à confirmer).

### Documentation

- `docs/navigation.md` — guide utilisateur final (créer un menu par langue, l'attacher à une location)
- `docs/decisions/0005-navigation-menus.md` — ADR (locations par langue vs single menu translatable)
- `CHANGELOG.md` — entrée `1.0.0-alpha.4`

---

## Conventions for every task

- Code identifiers in **English**, PHPDoc and comments in **French**, commits in **French** (Conventional Commits).
- TDD strict (Red → Green → Refactor → Commit) per class.
- One commit per task minimum.
- After each task: `XDEBUG_MODE=off composer ci` returns 0.
- WordPress functions in `src/`: NO leading backslash (project convention since Plan 3).
- Brain Monkey for WP function mocks. Final classes: extract narrow interfaces for mocking.
- Lunar syntax (rappel) : `[[ var ]]`, `[[! raw !]]`, `[% block %]`, `[% extends %]`, `[% include %]`, `[# comment #]`, `##macro()##`. `Lunar\Template\Runtime\Access::get` (issue #14) gère l'accès hybride array/objet, donc on passe les DTO directement.

---

## Task 1: Working branch and warm-up

**Files:** none.

- [ ] **Step 1: Confirm baseline green**

```bash
cd /home/seb/Dev/olikalari.com
git status
XDEBUG_MODE=off composer ci
```

Expected: clean tree, all tests green (97/97 from Plan 3 baseline).

- [ ] **Step 2: Create directory skeleton**

```bash
mkdir -p src/Navigation tests/Unit/Navigation assets/js
touch src/Navigation/.gitkeep tests/Unit/Navigation/.gitkeep
git add src/Navigation/.gitkeep tests/Unit/Navigation/.gitkeep
git commit -m "chore(plan4): squelette de dossiers pour la navigation"
```

---

## Task 2: `MenuItemEntity` (DTO immuable)

**Files:**
- Create: `src/Navigation/MenuItemEntity.php`
- Test: `tests/Unit/Navigation/MenuItemEntityTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Navigation;

use OliTheme\Navigation\MenuItemEntity;
use PHPUnit\Framework\TestCase;

final class MenuItemEntityTest extends TestCase
{
    public function testItExposesAllProperties(): void
    {
        $child = new MenuItemEntity(
            id: 11,
            label: 'Sous-page',
            url: 'https://example.com/sous-page',
            target: '',
            isCurrent: false,
            isAncestor: false,
            depth: 1,
            children: [],
        );

        $entity = new MenuItemEntity(
            id: 1,
            label: 'Accueil',
            url: 'https://example.com/',
            target: '_self',
            isCurrent: true,
            isAncestor: false,
            depth: 0,
            children: [$child],
        );

        self::assertSame(1, $entity->id);
        self::assertSame('Accueil', $entity->label);
        self::assertSame('https://example.com/', $entity->url);
        self::assertSame('_self', $entity->target);
        self::assertTrue($entity->isCurrent);
        self::assertFalse($entity->isAncestor);
        self::assertSame(0, $entity->depth);
        self::assertCount(1, $entity->children);
        self::assertSame($child, $entity->children[0]);
    }
}
```

- [ ] **Step 2: FAIL run**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter MenuItemEntityTest
```

- [ ] **Step 3: Implement `src/Navigation/MenuItemEntity.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

/**
 * DTO immuable représentant un item de menu (page, sous-menu inclus).
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
final readonly class MenuItemEntity
{
    /**
     * @param int                $id         Identifiant WP de l'item de menu.
     * @param string             $label      Libellé affiché.
     * @param string             $url        URL absolue cible.
     * @param string             $target     Cible HTML (`_self`, `_blank`, `''`).
     * @param bool               $isCurrent  Vrai si l'item correspond à la page courante.
     * @param bool               $isAncestor Vrai si l'item est un ancêtre de la page courante.
     * @param int                $depth      Profondeur dans l'arbre (0 = racine).
     * @param MenuItemEntity[]   $children   Sous-items (peut être vide).
     */
    public function __construct(
        public int $id,
        public string $label,
        public string $url,
        public string $target,
        public bool $isCurrent,
        public bool $isAncestor,
        public int $depth,
        public array $children,
    ) {
    }
}
```

- [ ] **Step 4: PASS + commit**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter MenuItemEntityTest
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add src/Navigation/MenuItemEntity.php tests/Unit/Navigation/MenuItemEntityTest.php
git commit -m "feat(navigation): ajoute MenuItemEntity (DTO immuable d'item de menu)"
```

---

## Task 3: `MenuModel` (toTree + résolution current/ancestor)

**Files:**
- Create: `src/Navigation/MenuModelInterface.php`
- Create: `src/Navigation/MenuModel.php`
- Test: `tests/Unit/Navigation/MenuModelTest.php`

The model takes the flat list of `WP_Post`-like nav items returned by `wp_get_nav_menu_items()` and returns a tree of `MenuItemEntity`. Each WP item has `ID`, `menu_item_parent`, `title`, `url`, `target`, and `object_id` (the post id it points to, used to detect `isCurrent`).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Navigation;

use OliTheme\Navigation\MenuItemEntity;
use OliTheme\Navigation\MenuModel;
use PHPUnit\Framework\TestCase;
use stdClass;

final class MenuModelTest extends TestCase
{
    public function testToTreeBuildsSingleLevelArray(): void
    {
        $items = [
            $this->buildWpItem(1, 0, 'Accueil', 'https://example.com/', 100),
            $this->buildWpItem(2, 0, 'Contact', 'https://example.com/contact', 200),
        ];

        $tree = (new MenuModel())->toTree($items, currentObjectId: 0);

        self::assertCount(2, $tree);
        self::assertContainsOnlyInstancesOf(MenuItemEntity::class, $tree);
        self::assertSame('Accueil', $tree[0]->label);
        self::assertSame(0, $tree[0]->depth);
        self::assertCount(0, $tree[0]->children);
    }

    public function testToTreeNestsChildrenUnderParents(): void
    {
        $items = [
            $this->buildWpItem(1, 0, 'Cours', 'https://example.com/cours', 10),
            $this->buildWpItem(2, 1, 'Hebdo', 'https://example.com/cours/hebdo', 11),
            $this->buildWpItem(3, 1, 'Stage', 'https://example.com/cours/stage', 12),
            $this->buildWpItem(4, 0, 'Contact', 'https://example.com/contact', 20),
        ];

        $tree = (new MenuModel())->toTree($items, currentObjectId: 0);

        self::assertCount(2, $tree);
        self::assertSame('Cours', $tree[0]->label);
        self::assertCount(2, $tree[0]->children);
        self::assertSame('Hebdo', $tree[0]->children[0]->label);
        self::assertSame(1, $tree[0]->children[0]->depth);
    }

    public function testCurrentAndAncestorAreResolved(): void
    {
        $items = [
            $this->buildWpItem(1, 0, 'Cours', 'https://example.com/cours', 10),
            $this->buildWpItem(2, 1, 'Hebdo', 'https://example.com/cours/hebdo', 11),
        ];

        $tree = (new MenuModel())->toTree($items, currentObjectId: 11);

        self::assertFalse($tree[0]->isCurrent);
        self::assertTrue($tree[0]->isAncestor);
        self::assertTrue($tree[0]->children[0]->isCurrent);
        self::assertFalse($tree[0]->children[0]->isAncestor);
    }

    private function buildWpItem(int $id, int $parent, string $title, string $url, int $objectId): stdClass
    {
        $item = new stdClass();
        $item->ID = $id;
        $item->menu_item_parent = (string) $parent;
        $item->title = $title;
        $item->url = $url;
        $item->target = '';
        $item->object_id = (string) $objectId;

        return $item;
    }
}
```

- [ ] **Step 2: FAIL run**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter MenuModelTest
```

- [ ] **Step 3: Implement `src/Navigation/MenuModelInterface.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

/**
 * Contrat du modèle de menu (utilisé par les controllers, mockable en test).
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
interface MenuModelInterface
{
    /**
     * Convertit une liste plate d'items WP en arbre de MenuItemEntity.
     *
     * @param array<int, object> $items           Items plats (`wp_get_nav_menu_items`).
     * @param int                $currentObjectId Identifiant du contenu courant (pour résoudre `isCurrent`).
     *
     * @return MenuItemEntity[]
     */
    public function toTree(array $items, int $currentObjectId): array;
}
```

- [ ] **Step 4: Implement `src/Navigation/MenuModel.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

/**
 * Implémentation du modèle de menu : conversion liste plate WP → arbre DTO.
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
final class MenuModel implements MenuModelInterface
{
    /**
     * @param array<int, object> $items
     *
     * @return MenuItemEntity[]
     */
    public function toTree(array $items, int $currentObjectId): array
    {
        if ($items === []) {
            return [];
        }

        // Indexe les items par parent.
        /** @var array<int, list<object>> $byParent */
        $byParent = [];
        foreach ($items as $item) {
            $parentId = (int) ($item->menu_item_parent ?? 0);
            $byParent[$parentId] ??= [];
            $byParent[$parentId][] = $item;
        }

        // Construit l'arbre récursivement à partir de la racine (parent = 0).
        return $this->buildBranch($byParent, 0, 0, $currentObjectId);
    }

    /**
     * @param array<int, list<object>> $byParent
     *
     * @return MenuItemEntity[]
     */
    private function buildBranch(array $byParent, int $parentId, int $depth, int $currentObjectId): array
    {
        if (! isset($byParent[$parentId])) {
            return [];
        }

        $branch = [];
        foreach ($byParent[$parentId] as $item) {
            $children = $this->buildBranch($byParent, (int) ($item->ID ?? 0), $depth + 1, $currentObjectId);
            $branch[] = new MenuItemEntity(
                id: (int) ($item->ID ?? 0),
                label: (string) ($item->title ?? ''),
                url: (string) ($item->url ?? ''),
                target: (string) ($item->target ?? ''),
                isCurrent: $currentObjectId > 0 && (int) ($item->object_id ?? 0) === $currentObjectId,
                isAncestor: $this->branchContainsCurrent($children),
                depth: $depth,
                children: $children,
            );
        }

        return $branch;
    }

    /**
     * @param MenuItemEntity[] $children
     */
    private function branchContainsCurrent(array $children): bool
    {
        foreach ($children as $child) {
            if ($child->isCurrent || $child->isAncestor) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 5: PASS + commit**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter MenuModelTest
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add src/Navigation/MenuModelInterface.php src/Navigation/MenuModel.php tests/Unit/Navigation/MenuModelTest.php
git commit -m "feat(navigation): ajoute MenuModel (arbre d'items + résolution current/ancestor)"
```

---

## Task 4: `MenuLocations` (enregistrement des locations par langue)

**Files:**
- Create: `src/Navigation/MenuLocations.php`
- Test: `tests/Unit/Navigation/MenuLocationsTest.php`

Each enabled language registers its own primary and footer locations: `primary_fr`, `footer_fr`, `primary_en`, `footer_en`, …

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Navigation;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\Navigation\MenuLocations;
use PHPUnit\Framework\TestCase;

final class MenuLocationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRegistersPrimaryAndFooterPerEnabledLanguage(): void
    {
        $registry = $this->createMock(LanguageRegistryInterface::class);
        $registry->method('all')->willReturn([
            new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr'),
            new Language('en', 'English', 'English', '🇬🇧', 'en_US', 'ltr'),
        ]);

        $captured = [];
        Functions\when('register_nav_menus')->alias(static function (array $locations) use (&$captured): void {
            $captured = $locations;
        });
        Functions\when('__')->returnArg(1);

        (new MenuLocations($registry))->register();

        self::assertArrayHasKey('primary_fr', $captured);
        self::assertArrayHasKey('footer_fr', $captured);
        self::assertArrayHasKey('primary_en', $captured);
        self::assertArrayHasKey('footer_en', $captured);
    }

    public function testPrimaryLocationKeyForReturnsExpectedSlug(): void
    {
        $registry = $this->createMock(LanguageRegistryInterface::class);
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $locations = new MenuLocations($registry);

        self::assertSame('primary_fr', $locations->primaryFor($french));
        self::assertSame('footer_fr', $locations->footerFor($french));
    }
}
```

- [ ] **Step 2: FAIL run**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter MenuLocationsTest
```

- [ ] **Step 3: Implement `src/Navigation/MenuLocations.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;

/**
 * Enregistre les locations de menus WordPress par langue activée.
 *
 * Chaque langue contribue deux locations : `primary_<code>` et `footer_<code>`.
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
final class MenuLocations
{
    public function __construct(private readonly LanguageRegistryInterface $registry)
    {
    }

    public function register(): void
    {
        $locations = [];
        foreach ($this->registry->all() as $language) {
            $locations[$this->primaryFor($language)] = sprintf(
                __('Menu principal (%s)', 'oli-theme'),
                $language->nativeLabel,
            );
            $locations[$this->footerFor($language)] = sprintf(
                __('Pied de page (%s)', 'oli-theme'),
                $language->nativeLabel,
            );
        }

        register_nav_menus($locations);
    }

    public function primaryFor(Language $language): string
    {
        return 'primary_' . $language->code;
    }

    public function footerFor(Language $language): string
    {
        return 'footer_' . $language->code;
    }
}
```

- [ ] **Step 4: PASS + commit**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter MenuLocationsTest
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add src/Navigation/MenuLocations.php tests/Unit/Navigation/MenuLocationsTest.php
git commit -m "feat(navigation): ajoute MenuLocations (primary/footer par langue activée)"
```

---

## Task 5: `MenuController` (buildPrimary + buildFooter)

**Files:**
- Create: `src/Navigation/MenuControllerInterface.php`
- Create: `src/Navigation/MenuController.php`
- Test: `tests/Unit/Navigation/MenuControllerTest.php`

`MenuController::buildPrimary(Language)` and `buildFooter(Language)` return `MenuItemEntity[]` for the given language. They use `wp_get_nav_menu_object` + `wp_get_nav_menu_items` to fetch the WP menu, then delegate to `MenuModel`. If no menu is assigned to the location, an empty array is returned (template renders nothing).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Navigation;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\Language;
use OliTheme\Navigation\MenuController;
use OliTheme\Navigation\MenuItemEntity;
use OliTheme\Navigation\MenuLocations;
use OliTheme\Navigation\MenuModelInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

final class MenuControllerTest extends TestCase
{
    private Language $french;
    private MenuModelInterface $model;
    private MenuLocations $locations;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $this->model = $this->createMock(MenuModelInterface::class);
        $this->locations = new MenuLocations($this->createStub(\OliTheme\I18n\LanguageRegistryInterface::class));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testBuildPrimaryReturnsEmptyWhenNoMenu(): void
    {
        Functions\when('has_nav_menu')->justReturn(false);

        $controller = new MenuController($this->locations, $this->model);

        self::assertSame([], $controller->buildPrimary($this->french));
    }

    public function testBuildPrimaryReturnsTreeFromModel(): void
    {
        $items = [(new stdClass())];
        $entity = new MenuItemEntity(1, 'A', '/', '', false, false, 0, []);

        Functions\when('has_nav_menu')->justReturn(true);
        Functions\when('wp_get_nav_menu_items')->justReturn($items);
        Functions\when('get_queried_object_id')->justReturn(42);

        $this->model
            ->expects(self::once())
            ->method('toTree')
            ->with($items, 42)
            ->willReturn([$entity]);

        $controller = new MenuController($this->locations, $this->model);

        self::assertSame([$entity], $controller->buildPrimary($this->french));
    }

    public function testBuildFooterUsesFooterLocation(): void
    {
        Functions\when('has_nav_menu')->justReturn(true);
        Functions\when('wp_get_nav_menu_items')->justReturn([]);
        Functions\when('get_queried_object_id')->justReturn(0);

        $this->model->method('toTree')->willReturn([]);

        $controller = new MenuController($this->locations, $this->model);

        self::assertSame([], $controller->buildFooter($this->french));
    }
}
```

- [ ] **Step 2: FAIL run**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter MenuControllerTest
```

- [ ] **Step 3: Implement `src/Navigation/MenuControllerInterface.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

use OliTheme\I18n\Language;

/**
 * Contrat du controller de menus (mockable depuis les autres modules).
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
interface MenuControllerInterface
{
    /**
     * @return MenuItemEntity[]
     */
    public function buildPrimary(Language $language): array;

    /**
     * @return MenuItemEntity[]
     */
    public function buildFooter(Language $language): array;
}
```

- [ ] **Step 4: Implement `src/Navigation/MenuController.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

use OliTheme\I18n\Language;

/**
 * Construit les arbres de menus (primaire + pied de page) pour une langue donnée.
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
final class MenuController implements MenuControllerInterface
{
    public function __construct(
        private readonly MenuLocations $locations,
        private readonly MenuModelInterface $model,
    ) {
    }

    /**
     * @return MenuItemEntity[]
     */
    public function buildPrimary(Language $language): array
    {
        return $this->buildFor($this->locations->primaryFor($language));
    }

    /**
     * @return MenuItemEntity[]
     */
    public function buildFooter(Language $language): array
    {
        return $this->buildFor($this->locations->footerFor($language));
    }

    /**
     * @return MenuItemEntity[]
     */
    private function buildFor(string $location): array
    {
        if (! has_nav_menu($location)) {
            return [];
        }

        $items = wp_get_nav_menu_items($location);
        if (! is_array($items)) {
            return [];
        }

        $currentObjectId = (int) get_queried_object_id();

        return $this->model->toTree($items, $currentObjectId);
    }
}
```

- [ ] **Step 5: PASS + commit**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter MenuControllerTest
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add src/Navigation/MenuControllerInterface.php src/Navigation/MenuController.php tests/Unit/Navigation/MenuControllerTest.php
git commit -m "feat(navigation): ajoute MenuController (buildPrimary/buildFooter par langue)"
```

---

## Task 6: `NavigationModule` (orchestrateur)

**Files:**
- Create: `src/Navigation/NavigationModule.php`
- Test: `tests/Unit/Navigation/NavigationModuleTest.php`

`NavigationModule::register()` :
1. Enregistre les services (`MenuModel`, `MenuLocations`, `MenuController`) dans le `Container` (factories) — sous les clés concrètes ET sous les interfaces (`MenuModelInterface`, `MenuControllerInterface`).
2. Hooke `after_setup_theme` pour appeler `MenuLocations::register()`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Navigation;

use Brain\Monkey;
use Brain\Monkey\Actions;
use OliTheme\Container;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\Navigation\MenuController;
use OliTheme\Navigation\MenuControllerInterface;
use OliTheme\Navigation\MenuLocations;
use OliTheme\Navigation\MenuModel;
use OliTheme\Navigation\MenuModelInterface;
use OliTheme\Navigation\NavigationModule;
use PHPUnit\Framework\TestCase;

final class NavigationModuleTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->container = new Container();
        $this->container->set(LanguageRegistryInterface::class, $this->createMock(LanguageRegistryInterface::class));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRegisterBindsAllNavigationServices(): void
    {
        (new NavigationModule($this->container))->register();

        self::assertInstanceOf(MenuModel::class, $this->container->get(MenuModel::class));
        self::assertInstanceOf(MenuModel::class, $this->container->get(MenuModelInterface::class));
        self::assertInstanceOf(MenuLocations::class, $this->container->get(MenuLocations::class));
        self::assertInstanceOf(MenuController::class, $this->container->get(MenuController::class));
        self::assertInstanceOf(MenuController::class, $this->container->get(MenuControllerInterface::class));
    }

    public function testRegisterHooksAfterSetupTheme(): void
    {
        Actions\expectAdded('after_setup_theme')->once();

        (new NavigationModule($this->container))->register();
    }
}
```

- [ ] **Step 2: FAIL run**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter NavigationModuleTest
```

- [ ] **Step 3: Implement `src/Navigation/NavigationModule.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Navigation;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\I18n\LanguageRegistryInterface;

/**
 * Module Navigation : enregistre les services et hooke les nav-menu locations.
 *
 * @package OliTheme\Navigation
 *
 * @since 1.0.0
 */
final class NavigationModule implements ModuleInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        $container = $this->container;

        if (! $container->has(MenuModel::class)) {
            $container->factory(
                MenuModel::class,
                static fn (): MenuModel => new MenuModel(),
            );
            $container->factory(
                MenuModelInterface::class,
                static fn (Container $c): MenuModelInterface => $c->get(MenuModel::class),
            );
        }

        if (! $container->has(MenuLocations::class)) {
            $container->factory(
                MenuLocations::class,
                static fn (Container $c): MenuLocations => new MenuLocations(
                    $c->get(LanguageRegistryInterface::class),
                ),
            );
        }

        if (! $container->has(MenuController::class)) {
            $container->factory(
                MenuController::class,
                static fn (Container $c): MenuController => new MenuController(
                    $c->get(MenuLocations::class),
                    $c->get(MenuModelInterface::class),
                ),
            );
            $container->factory(
                MenuControllerInterface::class,
                static fn (Container $c): MenuControllerInterface => $c->get(MenuController::class),
            );
        }

        add_action('after_setup_theme', function (): void {
            $locations = $this->container->get(MenuLocations::class);
            \assert($locations instanceof MenuLocations);
            $locations->register();
        });
    }
}
```

- [ ] **Step 4: PASS + commit**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter NavigationModuleTest
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add src/Navigation/NavigationModule.php tests/Unit/Navigation/NavigationModuleTest.php
git commit -m "feat(navigation): ajoute NavigationModule (services + hook after_setup_theme)"
```

---

## Task 7: Wire `NavigationModule` in `Theme::boot()`

**Files:**
- Modify: `src/Theme.php`
- Modify: `tests/Unit/ThemeTest.php`

- [ ] **Step 1: Update test**

```php
public function testBootRegistersNavigationModule(): void
{
    \OliTheme\Theme::reset();
    \OliTheme\Theme::boot(__DIR__);

    $container = \OliTheme\Theme::container();

    self::assertTrue($container->has(\OliTheme\Navigation\MenuController::class));
    self::assertTrue($container->has(\OliTheme\Navigation\MenuControllerInterface::class));
}
```

- [ ] **Step 2: Update `src/Theme.php` `registerCoreHooks()`**

Insert (after `(new \OliTheme\Posts\PostsModule(...))->register()`) :

```php
(new \OliTheme\Navigation\NavigationModule($container))->register();
```

Move it BEFORE `PostsModule` if `PostsModule` ever depends on the menu services — currently it doesn't (Plan 3 controllers don't pull `MenuControllerInterface` from the Container yet).

- [ ] **Step 3: Run + commit**

```bash
XDEBUG_MODE=off composer ci
git add src/Theme.php tests/Unit/ThemeTest.php
git commit -m "feat(theme): branche NavigationModule au boot"
```

---

## Task 8: Inject menu trees into Posts view-models

**Files:**
- Modify: `src/Posts/PageController.php`
- Modify: `src/Posts/PostController.php`
- Modify: `src/Posts/NotFoundController.php`
- Modify: `src/Posts/PostsModule.php`
- Modify: `tests/Unit/Posts/PageControllerTest.php`
- Modify: `tests/Unit/Posts/PostControllerTest.php`
- Modify: `tests/Unit/Posts/NotFoundControllerTest.php`

Each Posts controller now receives a `MenuControllerInterface` and adds two keys to the view-model: `primaryMenu` (`MenuItemEntity[]`) and `footerMenu` (`MenuItemEntity[]`), built for the current language via `MenuController::buildPrimary($current)` / `buildFooter($current)`.

- [ ] **Step 1: Update `PageController`**

Add the constructor parameter and inject in `buildBaseViewModel`:

```php
public function __construct(
    private readonly PostModelInterface $posts,
    private readonly LanguageResolverInterface $resolver,
    private readonly LanguageSwitcherControllerInterface $switcher,
    private readonly MenuControllerInterface $menus,
    private readonly RendererInterface $renderer,
) {
}

private function buildBaseViewModel(int $currentPostId): array
{
    $current = $this->resolver->current();

    return [
        'lang' => $current,
        'languageSwitcher' => $this->switcher->build($currentPostId),
        'primaryMenu' => $this->menus->buildPrimary($current),
        'footerMenu' => $this->menus->buildFooter($current),
        'bodyClasses' => 'lang-' . $current->code,
    ];
}
```

- [ ] **Step 2: Update `PostController` and `NotFoundController` similarly**

Same pattern: add `MenuControllerInterface $menus` to the constructor, call `$menus->buildPrimary($current)` and `$menus->buildFooter($current)` in `buildBaseViewModel`/`render`.

- [ ] **Step 3: Update `PostsModule` factories**

```php
$container->factory(
    PageController::class,
    static fn (Container $c): PageController => new PageController(
        $c->get(PostModelInterface::class),
        $c->get(LanguageResolverInterface::class),
        $c->get(LanguageSwitcherControllerInterface::class),
        $c->get(\OliTheme\Navigation\MenuControllerInterface::class),
        $c->get(RendererInterface::class),
    ),
);
// idem pour PostController et NotFoundController
```

Use `PostModelInterface::class` (extracted T3 of Plan 3) when fetching from container — match what's already registered.

- [ ] **Step 4: Update tests**

For each of the 3 controller tests, add a `MenuControllerInterface` mock that returns `[]` for primary/footer (no menus assigned during the test), and update the constructor call. Add the `primaryMenu` and `footerMenu` keys to the `self::callback` view-model assertions where applicable.

- [ ] **Step 5: Run + commit**

```bash
XDEBUG_MODE=off composer ci
git add src/Posts/ tests/Unit/Posts/
git commit -m "feat(posts): injecte les menus primary/footer dans les view-models"
```

---

## Task 9: `nav-desktop.html.tpl` partial

**Files:**
- Create: `templates/partials/nav-desktop.html.tpl`

- [ ] **Step 1: Create file**

```html
[# Navigation principale — version desktop.
   Variables attendues:
     - primaryMenu (MenuItemEntity[])
#]
[% if primaryMenu %]
<nav class="nav nav--desktop" aria-label="Menu principal" data-nav>
    <ul class="nav__list nav__list--root">
        [% for item in primaryMenu %]
            <li class="nav__item[% if item.isCurrent %] nav__item--current[% endif %][% if item.isAncestor %] nav__item--ancestor[% endif %][% if item.children %] nav__item--has-children[% endif %]">
                <a class="nav__link"
                   href="[[ item.url ]]"
                   [% if item.target %]target="[[ item.target ]]" rel="noopener"[% endif %]
                   [% if item.isCurrent %]aria-current="page"[% endif %]>
                    [[ item.label ]]
                </a>
                [% if item.children %]
                    <ul class="nav__sublist">
                        [% for child in item.children %]
                            <li class="nav__item nav__item--child[% if child.isCurrent %] nav__item--current[% endif %]">
                                <a class="nav__link nav__link--child"
                                   href="[[ child.url ]]"
                                   [% if child.target %]target="[[ child.target ]]" rel="noopener"[% endif %]
                                   [% if child.isCurrent %]aria-current="page"[% endif %]>
                                    [[ child.label ]]
                                </a>
                            </li>
                        [% endfor %]
                    </ul>
                [% endif %]
            </li>
        [% endfor %]
    </ul>
</nav>
[% endif %]
```

- [ ] **Step 2: Commit**

```bash
git add templates/partials/nav-desktop.html.tpl
git commit -m "feat(templates): partial nav-desktop (menu principal hover/focus)"
```

---

## Task 10: `nav-mobile.html.tpl` partial

**Files:**
- Create: `templates/partials/nav-mobile.html.tpl`

- [ ] **Step 1: Create file**

```html
[# Navigation principale — version mobile (drawer).
   Variables attendues: primaryMenu (MenuItemEntity[]).
   Le drawer est masqué par défaut en CSS et révélé par menu-mobile.js.
#]
[% if primaryMenu %]
<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-mobile" data-nav-toggle>
    <span class="nav-toggle__bar"></span>
    <span class="nav-toggle__bar"></span>
    <span class="nav-toggle__bar"></span>
    <span class="nav-toggle__label">Menu</span>
</button>
<nav id="nav-mobile" class="nav nav--mobile" aria-label="Menu mobile" hidden data-nav-mobile>
    <ul class="nav__list nav__list--root">
        [% for item in primaryMenu %]
            <li class="nav__item[% if item.children %] nav__item--has-children[% endif %]">
                <a class="nav__link" href="[[ item.url ]]" [% if item.isCurrent %]aria-current="page"[% endif %]>[[ item.label ]]</a>
                [% if item.children %]
                    <ul class="nav__sublist">
                        [% for child in item.children %]
                            <li class="nav__item nav__item--child">
                                <a class="nav__link nav__link--child" href="[[ child.url ]]">[[ child.label ]]</a>
                            </li>
                        [% endfor %]
                    </ul>
                [% endif %]
            </li>
        [% endfor %]
    </ul>
</nav>
[% endif %]
```

- [ ] **Step 2: Commit**

```bash
git add templates/partials/nav-mobile.html.tpl
git commit -m "feat(templates): partial nav-mobile (drawer + bouton burger)"
```

---

## Task 11: Update `header.html.tpl` to include nav partials

**Files:**
- Modify: `templates/partials/header.html.tpl`

- [ ] **Step 1: Replace the placeholder nav with the new partials**

```html
[# Header du thème.
   Variables attendues:
     - lang             (Language)
     - languageSwitcher (LanguageSwitcherViewModel)
     - primaryMenu      (MenuItemEntity[])
     - footerMenu       (MenuItemEntity[])  (utilisé dans le footer, pas ici)
   Variables globales: homeUrl, siteName.
#]
<header class="site-header" role="banner">
    [% include 'partials/banner.html.tpl' %]
    [% include 'partials/nav-desktop.html.tpl' %]
    [% include 'partials/nav-mobile.html.tpl' %]
    [% if languageSwitcher.items %]
        <ul class="language-switcher" aria-label="Changer de langue">
            [% for item in languageSwitcher.items %]
                <li class="language-switcher__item[% if item.isCurrent %] language-switcher__item--current[% endif %]">
                    <a href="[[ item.url ]]" hreflang="[[ item.code ]]" lang="[[ item.code ]]">
                        [[ item.label ]]
                    </a>
                </li>
            [% endfor %]
        </ul>
    [% endif %]
</header>
```

- [ ] **Step 2: Commit**

```bash
git add templates/partials/header.html.tpl
git commit -m "feat(templates): header inclut nav-desktop et nav-mobile"
```

---

## Task 12: Update `footer.html.tpl` to render `footerMenu`

**Files:**
- Modify: `templates/partials/footer.html.tpl`

- [ ] **Step 1: Replace the placeholder footer with menu-aware version**

```html
[# Pied de page minimal.
   Variables attendues:
     - footerMenu  (MenuItemEntity[])  optionnel
   Variables globales: siteName, currentYear.
#]
<footer class="site-footer" role="contentinfo">
    <div class="site-footer__inner">
        [% if footerMenu %]
            <nav class="site-footer__nav" aria-label="Menu pied de page">
                <ul class="site-footer__list">
                    [% for item in footerMenu %]
                        <li class="site-footer__item">
                            <a href="[[ item.url ]]">[[ item.label ]]</a>
                        </li>
                    [% endfor %]
                </ul>
            </nav>
        [% endif %]
        <p class="site-footer__copy">© [[ currentYear ]] [[ siteName ]]. Tous droits réservés.</p>
    </div>
</footer>
```

- [ ] **Step 2: Run base layout test (sans menu doit toujours passer)**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter ViewRendererBaseLayoutTest
```

If it fails because `footerMenu` is undefined, the `[% if footerMenu %]` guard already handles missing variables (Lunar treats undefined as falsy).

- [ ] **Step 3: Commit**

```bash
git add templates/partials/footer.html.tpl
git commit -m "feat(templates): footer rend le footerMenu si présent"
```

---

## Task 13: `menu.css` styles

**Files:**
- Create: `assets/css/menu.css`
- Modify: `assets/css/main.css` (add `@import url('./menu.css')`)

- [ ] **Step 1: Create `assets/css/menu.css`**

```css
/* Navigation BEM — desktop hover/focus + mobile drawer. */
.nav { display: contents; }
.nav__list { list-style: none; margin: 0; display: flex; gap: var(--space-4); flex-wrap: wrap; }
.nav__item { position: relative; }
.nav__link { display: inline-block; padding: var(--space-2) var(--space-3); text-decoration: none; color: var(--color-text); }
.nav__link:hover, .nav__link:focus-visible { color: var(--color-link-hover); text-decoration: underline; }
.nav__item--current > .nav__link { font-weight: 700; }

/* Sous-menu desktop : caché par défaut, ouvert au hover/focus du parent. */
.nav--desktop .nav__sublist {
    list-style: none;
    margin: 0;
    padding: var(--space-2) 0;
    position: absolute;
    top: 100%; left: 0;
    min-width: 12rem;
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    border-radius: 0.25rem;
    display: none;
    flex-direction: column;
    z-index: 10;
}
.nav--desktop .nav__item--has-children:hover > .nav__sublist,
.nav--desktop .nav__item--has-children:focus-within > .nav__sublist {
    display: flex;
}

/* Mobile drawer : caché par défaut, révélé via [hidden=false]. */
.nav-toggle { display: none; }
.nav--mobile { display: none; }

@media (max-width: 47.99rem) {
    .nav--desktop { display: none; }
    .nav-toggle {
        display: inline-flex; align-items: center; gap: var(--space-2);
        background: transparent; border: 1px solid var(--color-border);
        padding: var(--space-2) var(--space-3); border-radius: 0.25rem;
        cursor: pointer;
    }
    .nav-toggle__bar { display: block; width: 1rem; height: 2px; background: var(--color-text); }
    .nav--mobile {
        display: block;
        position: fixed; inset: 0;
        background: var(--color-bg);
        padding: var(--space-6) var(--space-4);
        overflow-y: auto;
        z-index: 100;
    }
    .nav--mobile[hidden] { display: none; }
    .nav--mobile .nav__list { flex-direction: column; gap: 0; }
    .nav--mobile .nav__sublist { list-style: none; margin: 0; padding-left: var(--space-5); }
}
```

- [ ] **Step 2: Update `assets/css/main.css`**

```css
/* Point d'entrée CSS du thème. Importe tous les modules vanilla. */
@import url('./tokens.css');
@import url('./reset.css');
@import url('./base.css');
@import url('./menu.css');
```

- [ ] **Step 3: Commit**

```bash
git add assets/css/menu.css assets/css/main.css
git commit -m "feat(assets): styles menu.css (desktop hover, mobile drawer responsive)"
```

---

## Task 14: `menu-mobile.js` ES module + `main.js` entry

**Files:**
- Create: `assets/js/main.js`
- Create: `assets/js/menu-mobile.js`

- [ ] **Step 1: Create `assets/js/menu-mobile.js`**

```js
/**
 * Menu mobile (drawer) — gestion ouverture/fermeture + clavier.
 * Sans dépendance, ES module.
 */
export function initMobileMenu() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const drawer = document.querySelector('[data-nav-mobile]');
    if (!toggle || !drawer) {
        return;
    }

    const setOpen = (isOpen) => {
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        drawer.hidden = !isOpen;
        document.body.classList.toggle('has-mobile-menu-open', isOpen);
    };

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        setOpen(!isOpen);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && drawer.hidden === false) {
            setOpen(false);
            toggle.focus();
        }
    });
}
```

- [ ] **Step 2: Create `assets/js/main.js`**

```js
/**
 * Point d'entrée ES module — auto-init des composants présents dans le DOM.
 */
import { initMobileMenu } from './menu-mobile.js';

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-nav-toggle]')) {
        initMobileMenu();
    }
});
```

- [ ] **Step 3: Commit**

```bash
git add assets/js/main.js assets/js/menu-mobile.js
git commit -m "feat(assets): menu-mobile.js (drawer accessible) + main.js (auto-init)"
```

---

## Task 15: Confirm `AssetManager::enqueueFront` câble `main.js`

**Files:**
- Verify: `src/Core/AssetManager.php`

`AssetManager::enqueueFront()` should already call `wp_enqueue_script_module('oli-theme', ..., 'assets/js/main.js')` since Plan 1 (per the report from T19). **Verify** by reading the file. If absent, add it in the same shape as the CSS enqueue, with `version()` for cache-busting. Add a unit test mirroring `testEnqueueFrontEnqueuesMainCssWithFilemtimeVersion` for the script.

- [ ] **Step 1: Read existing AssetManager**

```bash
grep -n 'wp_enqueue_script_module\|main.js' src/Core/AssetManager.php tests/Unit/Core/AssetManagerTest.php
```

- [ ] **Step 2: If `main.js` is already wired, no code change**

Otherwise, add :

```php
public function enqueueFront(): void
{
    wp_enqueue_style(
        'oli-theme',
        $this->themeUri . '/assets/css/main.css',
        [],
        $this->version('assets/css/main.css'),
    );
    wp_enqueue_script_module(
        'oli-theme',
        $this->themeUri . '/assets/js/main.js',
        [],
        $this->version('assets/js/main.js'),
    );
}
```

And a test :

```php
public function testEnqueueFrontEnqueuesMainJsAsModule(): void
{
    // Pattern miroir de testEnqueueFrontEnqueuesMainCssWithFilemtimeVersion
    // mais sur wp_enqueue_script_module + assets/js/main.js
}
```

- [ ] **Step 3: Commit (if changes)**

```bash
git add src/Core/AssetManager.php tests/Unit/Core/AssetManagerTest.php
git commit -m "feat(core): AssetManager câble main.js comme ES module (versioning filemtime)"
```

If no change: skip commit.

---

## Task 16: Documentation + ADR 0005

**Files:**
- Create: `docs/navigation.md`
- Create: `docs/decisions/0005-navigation-menus.md`

- [ ] **Step 1: `docs/navigation.md`**

```markdown
# Navigation

## Locations enregistrées

Pour chaque langue activée, deux locations sont créées automatiquement par `OliTheme\Navigation\MenuLocations` :

- `primary_<code>` — menu principal
- `footer_<code>` — menu pied de page

Ex. activer `fr` et `en` produit `primary_fr`, `footer_fr`, `primary_en`, `footer_en`.

## Créer un menu

1. **Apparence > Menus** dans l'admin WP.
2. Créer un menu, ajouter pages/posts/liens.
3. Cocher la location voulue dans la section "Réglages du menu".
4. Sauvegarder.

## Fonctionnalités front

- **Desktop** : menu horizontal, sous-menus apparaissant au hover ET au focus clavier (`:hover, :focus-within`).
- **Mobile** (< 768px) : drawer plein écran, ouvert via le bouton burger, fermable avec `Escape`.
- **A11y** : `aria-label`, `aria-current`, `aria-expanded`, navigation Tab/Escape.

## Personnalisation CSS

Les classes BEM exposées :
- `.nav`, `.nav--desktop`, `.nav--mobile`
- `.nav__list`, `.nav__list--root`, `.nav__sublist`
- `.nav__item`, `.nav__item--current`, `.nav__item--ancestor`, `.nav__item--has-children`, `.nav__item--child`
- `.nav__link`, `.nav__link--child`
- `.nav-toggle`, `.nav-toggle__bar`

## Comportement no-JS

Le drawer mobile sans JS reste fermé (`hidden`). Le menu desktop reste fonctionnel (CSS seul).
```

- [ ] **Step 2: `docs/decisions/0005-navigation-menus.md`**

```markdown
# ADR 0005 — Locations de menus par langue

**Statut :** accepté
**Date :** 2026-05-05
**Contexte :** Plan 4 — Navigation.

## Décision

Pour chaque langue activée, **enregistrer deux locations distinctes** : `primary_<code>` et `footer_<code>`. Le rédacteur compose un menu par langue dans l'admin WP standard et l'attache à la location correspondante.

## Alternatives rejetées

- **Un seul menu translatable** (un menu central, items traduits via meta) : couplerait la traduction des menus au système multilingue custom et nécessiterait une UI custom pour gérer les overrides par langue. Plus de code, moins lisible côté admin.
- **Menus auto-générés à partir des pages** : pratique mais inflexible (l'auteur veut souvent un ordre / des libellés différents du titre des pages).

## Conséquences

- ✅ Compatible 100 % avec l'UI WP standard (Apparence > Menus).
- ✅ Indépendance totale entre menus FR / EN / IT.
- ✅ Locations dynamiques : ajouter une langue dans `Settings > Langues` (plan ultérieur) crée automatiquement deux nouvelles locations au prochain `after_setup_theme`.
- ❌ Le rédacteur doit recréer la structure pour chaque langue (pas de duplication automatique en cycle 1).
```

- [ ] **Step 3: Commit**

```bash
git add docs/navigation.md docs/decisions/0005-navigation-menus.md
git commit -m "docs: guide navigation + ADR 0005 (locations par langue)"
```

---

## Task 17: Changelog `1.0.0-alpha.4` + tag + push

**Files:**
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Add entry**

```markdown
## [1.0.0-alpha.4] - 2026-05-05

### Added (Plan 4 — Navigation)

- `Navigation\MenuItemEntity` — DTO immuable d'item de menu (avec arbre `children`).
- `Navigation\MenuModel` — convertit la liste plate WP en arbre, résout `current` et `ancestor`.
- `Navigation\MenuLocations` — enregistre `primary_<code>` et `footer_<code>` pour chaque langue activée.
- `Navigation\MenuController` — `buildPrimary(Language)` / `buildFooter(Language)`.
- `Navigation\NavigationModule` — orchestrateur, branché dans `Theme::boot()`.
- Interfaces extraites pour le mocking : `MenuModelInterface`, `MenuControllerInterface`.
- Templates `partials/nav-desktop.html.tpl` + `partials/nav-mobile.html.tpl`.
- Header (`partials/header.html.tpl`) inclut les deux navs.
- Footer (`partials/footer.html.tpl`) rend `footerMenu` quand présent.
- `assets/css/menu.css` — styles BEM desktop/hover + mobile drawer.
- `assets/js/main.js` (entry) + `assets/js/menu-mobile.js` (drawer accessible Escape/Tab).
- Posts controllers (`PageController`, `PostController`, `NotFoundController`) injectent `MenuControllerInterface` et exposent `primaryMenu` / `footerMenu` au view-model.
- Guide `docs/navigation.md` + ADR 0005.
```

- [ ] **Step 2: Commit + tag + push**

```bash
git add CHANGELOG.md
git commit -m "docs: changelog 1.0.0-alpha.4 (livraison Plan 4 - Navigation)"
XDEBUG_MODE=off composer ci
git tag -a v1.0.0-alpha.4-navigation -m "Release 1.0.0-alpha.4 — Plan 4 (Navigation)"
git push origin main
git push origin v1.0.0-alpha.4-navigation
```

---

## Definition of Done — Plan 4 (Navigation)

1. ✅ `composer ci` returns 0
2. ✅ Coverage ≥ 90 % on `src/Navigation/`
3. ✅ All 17 tasks committed
4. ✅ Tag `v1.0.0-alpha.4-navigation` posed and pushed
5. ✅ CI workflow green for PHP 8.3 / 8.4 / 8.5
6. ✅ ADR 0005 + `docs/navigation.md` present
7. ✅ `NavigationModule` registered in `Theme::boot()`
8. ✅ Posts controllers expose `primaryMenu` / `footerMenu` in view-models
9. ✅ Theme renders the header nav (desktop + mobile drawer) when a menu is assigned

When all 9 boxes are ticked, **Plan 5 (Slides + Home Carousel)** can start.

---

## Self-Review

### 1. Spec coverage

| Spec section | Couvert ? | Tâches |
|--------------|-----------|--------|
| 1.2 Arborescence `src/Navigation/` | ✅ | T2-T6 |
| 4.4 Menus WP par langue (`primary_<code>`, `footer_<code>`) | ✅ | T4 (MenuLocations) |
| 4.4 `MenuController::buildPrimary` | ✅ | T5 |
| 4.4 `MenuItemEntity` immuable (id, label, url, target, isCurrent, isAncestor, depth, children) | ✅ | T2 |
| 4.4 Desktop hover + focus | ✅ | T9 (`:hover, :focus-within` en T13) |
| 4.4 Mobile drawer + accordéons | ⚠️ partiel | T10 + T13 + T14 (accordéons reportés au cycle suivant — Tab/Escape couvert) |
| 4.4 Clavier ARIA (Tab/Escape/Arrows) | ⚠️ partiel | T14 (Tab/Escape couvert ; Arrows reporté à un raffinement ultérieur si demandé) |

### 2. Placeholder scan

Pas de TODO en code. Les arrows clavier dans le drawer mobile sont volontairement reportés (le drawer reste navigable Tab + Escape ; ajouter Arrows demande un Roving Tabindex non triviale qui mérite une task dédiée si l'a11y audit le requiert).

### 3. Type consistency

- `MenuItemEntity` props (id, label, url, target, isCurrent, isAncestor, depth, children) — utilisées identiquement dans T2, T9, T10, T11.
- `MenuModelInterface::toTree(array, int): array` — cohérent T3 ↔ T5 ↔ T6.
- `MenuControllerInterface::buildPrimary/buildFooter(Language): array` — cohérent T5 ↔ T8 ↔ T15.
- `MenuLocations::primaryFor/footerFor(Language): string` — cohérent T4 ↔ T5.

### 4. Scope

Plan 4 = Navigation pure. Aucun chevauchement avec Slides / Events / SEO / Settings. Livre un thème dont les menus rendent vraiment.

---

## Next Step

Plan 5 (Slides + Home Carousel) — `SlideEntity`, `SlideCpt`, `SlideModel`, `HomeCarouselController`, `templates/partials/carousel.html.tpl`, `assets/js/carousel.js`, etc.
