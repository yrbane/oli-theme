# Foundation Implementation Plan (oli-theme — Cycle 1, Plan 1/10)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Set up an activatable WordPress theme `oli-theme` with the OOP/MVC scaffolding (Container, ViewRenderer with Lunar, AssetManager, RequestContext, HookRegistrar, Theme bootstrap) and a green test/quality pipeline (PHPUnit + Brain Monkey, PHPStan 8, PHP-CS-Fixer, GitHub Actions CI).

**Architecture:** Composer-based PHP 8.3+ theme using Lunar Template Engine for views. A minimal DI container wires Core services that every future module will depend on. The theme activates cleanly in WordPress 6.9+ without rendering any visible content yet — its only on-screen output is a minimal "Coming soon" page rendered through Lunar to prove the pipeline works end-to-end. All commits in French; all code identifiers in English.

**Tech Stack:** PHP `^8.3` (testé sur 8.5.4), WordPress 6.9+, Composer, Lunar Template Engine `^1.1` (`yrbane/lunar-template`), PHPUnit 11, Brain Monkey 2.6+, PHPStan 1.11+ (level 8), php-stubs/wordpress-stubs 6.9+, PHP-CS-Fixer 3.50+, captainhook 5.20+, GitHub Actions.

**Reference spec:** `docs/superpowers/specs/2026-05-05-oli-theme-design.md` (sections 0, 1, 5, 6.1).

---

## File Structure

This plan creates the following files (in execution order):

### Project root
- `.gitignore` — exclude `vendor/`, `coverage/`, `.phpunit.cache/`, `node_modules/`, `*.cache`, IDE files
- `.editorconfig` — UTF-8, LF, 4-space indent for PHP, 2-space for YAML/JSON/MD
- `LICENSE` — MIT
- `README.md` — project description, installation, scripts
- `CHANGELOG.md` — Keep a Changelog format, initial 1.0.0-alpha entry
- `composer.json` — dependencies, autoload PSR-4, scripts
- `phpunit.xml.dist` — test suites (Unit, Integration), bootstrap, coverage
- `phpstan.neon` — level 8, paths, WP stubs
- `.php-cs-fixer.dist.php` — PSR-12 + PHP 8.3 migration rules
- `captainhook.json` — pre-commit hooks (cs:fix --dry-run, phpstan, phpunit unit)
- `style.css` — WordPress theme header (Theme Name, Version, Description...)
- `functions.php` — bootstrap: `require vendor/autoload.php; Theme::boot(__DIR__);`

### Source (`src/`, namespace `OliTheme\`)
- `src/Theme.php` — singleton bootstrap (boot, container access, activation/deactivation hooks)
- `src/Container.php` — minimal DI container (PSR-11 like)
- `src/Core/ModuleInterface.php` — contract for feature modules
- `src/Core/PostTypeInterface.php` — contract for CPT registrars
- `src/Core/RequestContext.php` — wrapper for query vars, cookies, headers, server
- `src/Core/HookRegistrar.php` — wrapper for add_action/add_filter (testable, introspectable)
- `src/Core/ViewRenderer.php` — wrapper for Lunar Template Engine (escape, helpers, defaults)
- `src/Core/AssetManager.php` — enqueue front/admin CSS/JS with filemtime versioning

### Templates
- `templates/layouts/empty.html.tpl` — minimal HTML layout used by smoke test

### Theme bridge (WP template hierarchy)
- `theme-bridge/index.php` — fallback: render a "Coming soon" Lunar template to prove pipeline

### Tests (`tests/`)
- `tests/bootstrap.php` — Brain Monkey setup, Composer autoload
- `tests/bootstrap-phpstan.php` — empty stub for PHPStan
- `tests/Unit/ContainerTest.php`
- `tests/Unit/Core/RequestContextTest.php`
- `tests/Unit/Core/HookRegistrarTest.php`
- `tests/Unit/Core/ViewRendererTest.php`
- `tests/Unit/Core/AssetManagerTest.php`
- `tests/Integration/ActivationTest.php` — verify Theme::onActivation runs without fatal

### CI/CD
- `.github/workflows/ci.yml` — matrix PHP 8.3/8.4/8.5, runs `composer ci`

### Documentation
- `docs/architecture.md` — high-level MVC/module layout
- `docs/installation.md` — composer install, activation, default options
- `docs/testing.md` — running tests, Brain Monkey, mocking patterns
- `docs/decisions/0001-mvc-pattern.md` — ADR: why a strict MVC inside WordPress
- `docs/decisions/0002-lunar-template.md` — ADR: choice of Lunar Template Engine

---

## Conventions for every task

- All code identifiers in **English** (classes, methods, variables).
- All PHPDoc and inline comments in **French**.
- All commit messages in **French**, prefixed by Conventional Commits type (`feat:`, `fix:`, `chore:`, `test:`, `docs:`, `refactor:`, `ci:`).
- Strict TDD: Red → Green → Refactor for every class. Run the failing test FIRST, then implement.
- One commit per task by default (multiple commits OK if a task naturally splits).
- After each task: `composer ci` must remain green.

---

## Task 1: Initialize git repository and project skeleton

**Files:**
- Create: `.gitignore`
- Create: `.editorconfig`
- Create: `LICENSE`

- [ ] **Step 1: Initialize git repository**

```bash
cd /home/seb/Dev/olikalari.com
git init
git branch -m main
```

Expected: `Initialized empty Git repository in /home/seb/Dev/olikalari.com/.git/`

- [ ] **Step 2: Create `.gitignore`**

```gitignore
# Dependencies
/vendor/
/node_modules/

# Tests & quality
/coverage/
/.phpunit.cache/
/.phpunit.result.cache
/.php-cs-fixer.cache
/.phpstan.cache

# IDE
/.idea/
/.vscode/
*.iml

# OS
.DS_Store
Thumbs.db

# Misc
*.log
*.tmp
```

- [ ] **Step 3: Create `.editorconfig`**

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
indent_style = space
indent_size = 4
insert_final_newline = true
trim_trailing_whitespace = true

[*.{yml,yaml,json,md}]
indent_size = 2

[*.{tpl,html.tpl}]
indent_size = 2
```

- [ ] **Step 4: Create `LICENSE` (MIT)**

```
MIT License

Copyright (c) 2026 yrbane

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
```

- [ ] **Step 5: Stage and commit**

```bash
git add .gitignore .editorconfig LICENSE
git commit -m "chore: initialise le dépôt git et les fichiers de base (gitignore, editorconfig, licence MIT)"
```

---

## Task 2: Create README.md

**Files:**
- Create: `README.md`

- [ ] **Step 1: Write `README.md`**

```markdown
# oli-theme

Thème WordPress custom OOP / MVC, multilingue, réutilisable sur plusieurs sites.

## Caractéristiques

- Architecture MVC stricte, principes SOLID/DRY/KISS
- Moteur de templates [Lunar Template Engine](https://github.com/yrbane/lunar-template)
- Système multilingue custom (URLs `/fr/`, `/en/`, `/it/`...)
- CSS / JavaScript en vanilla (pas de pipeline de build)
- Tests TDD via PHPUnit + Brain Monkey
- Qualité : PHPStan niveau 8, PHP-CS-Fixer (PSR-12)
- PHP `^8.3`, WordPress 6.9+

## Installation

```bash
composer install
```

Puis activer le thème dans `Apparence > Thèmes` du back-office WordPress.

## Scripts Composer

| Commande | Description |
|----------|-------------|
| `composer test` | Exécute la suite de tests unitaires |
| `composer test:all` | Tests unitaires + intégration |
| `composer test:coverage` | Tests + rapport de couverture HTML |
| `composer analyse` | Analyse statique PHPStan niveau 8 |
| `composer cs` | Vérifie le formatage du code (dry-run) |
| `composer cs:fix` | Corrige le formatage du code |
| `composer qa` | Lance cs + analyse + test |
| `composer ci` | Lance cs + analyse + tous les tests (cible CI) |
| `composer docs` | Génère la documentation API HTML |

## Documentation

- Architecture : [`docs/architecture.md`](docs/architecture.md)
- Installation : [`docs/installation.md`](docs/installation.md)
- Tests : [`docs/testing.md`](docs/testing.md)
- Décisions architecturales : [`docs/decisions/`](docs/decisions/)

## Licence

MIT — voir [LICENSE](LICENSE).
```

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: ajoute le README principal du projet"
```

---

## Task 3: Create composer.json with all dependencies

**Files:**
- Create: `composer.json`

- [ ] **Step 1: Write `composer.json`**

> **Note** (mise à jour 2026-05-05) :
> - `yrbane/lunar-template` n'est pas publié sur Packagist → installation via dépôt VCS GitHub.
> - Lunar Template dépend de `yrbane/lunar-cli` et `yrbane/lunar-config`, eux aussi hors Packagist → trois entrées `repositories` au lieu d'une.
> - Aucun tag stable n'est disponible sur Lunar Template (`dev-main` uniquement) → `require` utilise `dev-main` et `minimum-stability` est descendu à `"dev"` avec `prefer-stable: true`. À réviser quand Lunar publiera un tag v1.x.
> - `phpdocumentor/phpdocumentor` est incompatible PHP 8.5 (sa dépendance transitive `phpdocumentor/json-path 0.2.1` exclut PHP 8.5) → retiré du `require-dev` ; le script `docs` est désactivé temporairement. À réintroduire dans un cycle ultérieur quand PHP 8.5 sera supporté en amont.

```json
{
  "name": "yrbane/oli-theme",
  "description": "Thème WordPress custom OOP/MVC multilingue, réutilisable multi-sites.",
  "type": "wordpress-theme",
  "license": "MIT",
  "authors": [
    { "name": "yrbane", "email": "yrbane@nethttp.net" }
  ],
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/yrbane/lunar-template"
    }
  ],
  "require": {
    "php": "^8.3",
    "yrbane/lunar-template": "^1.1"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "brain/monkey": "^2.6",
    "phpstan/phpstan": "^1.11",
    "phpstan/extension-installer": "^1.4",
    "php-stubs/wordpress-stubs": "^6.9",
    "friendsofphp/php-cs-fixer": "^3.50",
    "captainhook/captainhook": "^5.20"
  },
  "autoload": {
    "psr-4": {
      "OliTheme\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "OliTheme\\Tests\\": "tests/"
    }
  },
  "config": {
    "sort-packages": true,
    "allow-plugins": {
      "phpstan/extension-installer": true
    }
  },
  "minimum-stability": "stable",
  "prefer-stable": true,
  "scripts": {
    "test": "phpunit --testsuite=unit",
    "test:integration": "phpunit --testsuite=integration",
    "test:all": "phpunit",
    "test:coverage": "XDEBUG_MODE=coverage phpunit --coverage-html coverage/",
    "analyse": "phpstan analyse --memory-limit=512M",
    "cs": "php-cs-fixer fix --dry-run --diff",
    "cs:fix": "php-cs-fixer fix",
    "docs": "echo 'Documentation API désactivée temporairement (phpdocumentor incompatible PHP 8.5). Voir docs/decisions/0003-phpdoc-deferred.md.'",
    "qa": ["@cs", "@analyse", "@test"],
    "ci": ["@cs", "@analyse", "@test:all"]
  }
}
```

- [ ] **Step 2: Validate JSON syntax**

```bash
composer validate --no-check-publish
```

Expected: `./composer.json is valid` (warnings about missing license metadata are OK if any).

- [ ] **Step 3: Install dependencies**

```bash
composer install
```

Expected: vendor/ created, autoload generated, no errors.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore: ajoute composer.json et installe les dépendances de base"
```

---

## Task 4: WordPress theme header (style.css)

**Files:**
- Create: `style.css`

- [ ] **Step 1: Write `style.css`**

```css
/*
Theme Name: Oli Theme
Theme URI: https://github.com/yrbane/oli-theme
Author: yrbane
Author URI: https://nethttp.net
Description: Thème WordPress custom OOP/MVC multilingue, réutilisable multi-sites. Architecture stricte avec Lunar Template Engine, système multilingue custom, sans dépendance à un builder visuel.
Version: 1.0.0-alpha
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.3
License: MIT
License URI: https://opensource.org/licenses/MIT
Text Domain: oli-theme
Domain Path: /languages
Tags: custom-theme, mvc, multilingual, accessibility-ready, translation-ready
*/

/* Le CSS réel est chargé depuis assets/css/main.css via wp_enqueue_style. */
```

- [ ] **Step 2: Commit**

```bash
git add style.css
git commit -m "feat: ajoute l'en-tête WordPress du thème (style.css)"
```

---

## Task 5: PHPUnit configuration

**Files:**
- Create: `phpunit.xml.dist`
- Create: `tests/bootstrap.php`

- [ ] **Step 1: Write `phpunit.xml.dist`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
    bootstrap="tests/bootstrap.php"
    colors="true"
    cacheDirectory=".phpunit.cache"
    requireCoverageMetadata="false"
    beStrictAboutCoverageMetadata="false"
    beStrictAboutOutputDuringTests="true"
    failOnRisky="true"
    failOnWarning="true"
    displayDetailsOnTestsThatTriggerWarnings="true"
    displayDetailsOnTestsThatTriggerErrors="true">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <source restrictDeprecations="true" restrictNotices="true" restrictWarnings="true">
        <include>
            <directory>src</directory>
        </include>
    </source>
    <coverage>
        <report>
            <html outputDirectory="coverage/html"/>
            <text outputFile="coverage/coverage.txt"/>
            <clover outputFile="coverage/clover.xml"/>
        </report>
    </coverage>
</phpunit>
```

- [ ] **Step 2: Write `tests/bootstrap.php`**

```php
<?php

declare(strict_types=1);

/**
 * Bootstrap PHPUnit pour le thème oli-theme.
 *
 * Charge l'autoloader Composer et initialise Brain Monkey si nécessaire
 * (chaque test fait son propre setUp/tearDown).
 *
 * @package OliTheme\Tests
 */

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    fwrite(STDERR, "Autoloader introuvable. Lancer 'composer install' avant les tests.\n");
    exit(1);
}

require_once $autoload;
```

- [ ] **Step 3: Create empty test directories**

```bash
mkdir -p tests/Unit/Core tests/Integration tests/helpers
touch tests/Unit/.gitkeep tests/Integration/.gitkeep tests/helpers/.gitkeep
```

- [ ] **Step 4: Verify PHPUnit runs (no tests yet)**

```bash
vendor/bin/phpunit --testsuite=unit
```

Expected: `No tests executed!` (exit code 0 or 2 — accept either since no tests defined).

- [ ] **Step 5: Commit**

```bash
git add phpunit.xml.dist tests/bootstrap.php tests/Unit/.gitkeep tests/Integration/.gitkeep tests/helpers/.gitkeep
git commit -m "chore: configure PHPUnit (suites unit/integration, couverture)"
```

---

## Task 6: PHPStan configuration

**Files:**
- Create: `phpstan.neon`
- Create: `tests/bootstrap-phpstan.php`

- [ ] **Step 1: Write `tests/bootstrap-phpstan.php`**

```php
<?php

declare(strict_types=1);

/**
 * Bootstrap PHPStan : pas d'init nécessaire pour le moment.
 * Existe pour être référencé depuis phpstan.neon (extensible).
 *
 * @package OliTheme\Tests
 */
```

- [ ] **Step 2: Write `phpstan.neon`**

```neon
parameters:
    level: 8
    paths:
        - src
        - tests
    bootstrapFiles:
        - tests/bootstrap-phpstan.php
    excludePaths:
        - vendor
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
    treatPhpDocTypesAsCertain: true
    reportUnmatchedIgnoredErrors: true

includes:
    - vendor/php-stubs/wordpress-stubs/wordpress-stubs.php
```

- [ ] **Step 3: Verify PHPStan runs**

```bash
vendor/bin/phpstan analyse --memory-limit=512M
```

Expected: `[OK] No errors` (no source files yet, so trivially clean).

- [ ] **Step 4: Commit**

```bash
git add phpstan.neon tests/bootstrap-phpstan.php
git commit -m "chore: configure PHPStan niveau 8 avec stubs WordPress"
```

---

## Task 7: PHP-CS-Fixer configuration

**Files:**
- Create: `.php-cs-fixer.dist.php`

- [ ] **Step 1: Write `.php-cs-fixer.dist.php`**

```php
<?php

declare(strict_types=1);

/**
 * Configuration PHP-CS-Fixer pour oli-theme.
 * Règles : PSR-12 + migration PHP 8.3 + conventions internes (final classes, strict_types).
 */

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP83Migration' => true,
        '@PHP82Migration:risky' => true,
        'declare_strict_types' => true,
        'final_class' => true,
        'array_syntax' => ['syntax' => 'short'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'ordered_class_elements' => true,
        'no_extra_blank_lines' => ['tokens' => ['extra', 'use', 'use_trait']],
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_separation' => true,
        'phpdoc_trim' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => false],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
```

- [ ] **Step 2: Verify CS-Fixer runs**

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Expected: `Found 0 of 0 files that can be fixed in ...` (no source files yet).

- [ ] **Step 3: Commit**

```bash
git add .php-cs-fixer.dist.php
git commit -m "chore: configure PHP-CS-Fixer (PSR-12 + migration PHP 8.3 + final classes)"
```

---

## Task 8: ModuleInterface

**Files:**
- Create: `src/Core/ModuleInterface.php`

- [ ] **Step 1: Write `src/Core/ModuleInterface.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Contrat des modules fonctionnels du thème.
 *
 * Un module regroupe une cohérence métier (I18n, SEO, Contact, Events...) et
 * enregistre ses propres hooks WordPress lors de son initialisation au
 * chargement du thème par {@see \OliTheme\Theme::boot()}.
 *
 * @package OliTheme\Core
 * @since 1.0.0
 */
interface ModuleInterface
{
    /**
     * Enregistre les hooks WordPress nécessaires au module.
     *
     * Cette méthode est appelée une seule fois, au démarrage du thème.
     * Elle ne doit déclencher aucun appel à add_action / add_filter avant
     * que WordPress soit prêt — limiter à des bindings sur des hooks
     * postérieurs ('init', 'wp_loaded', 'template_redirect'...).
     */
    public function register(): void;
}
```

- [ ] **Step 2: Run CS + PHPStan**

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse --memory-limit=512M
```

Expected: both pass.

- [ ] **Step 3: Commit**

```bash
git add src/Core/ModuleInterface.php
git commit -m "feat(core): ajoute ModuleInterface, contrat des modules fonctionnels"
```

---

## Task 9: PostTypeInterface

**Files:**
- Create: `src/Core/PostTypeInterface.php`

- [ ] **Step 1: Write `src/Core/PostTypeInterface.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Contrat des classes responsables d'enregistrer un Custom Post Type.
 *
 * Implémenté par {@see \OliTheme\Events\EventCpt}, {@see \OliTheme\Slides\SlideCpt}, etc.
 * La méthode register() doit être branchée sur le hook 'init' par le module
 * porteur (et non depuis l'implémentation elle-même).
 *
 * @package OliTheme\Core
 * @since 1.0.0
 */
interface PostTypeInterface
{
    /**
     * Enregistre le Custom Post Type via register_post_type() et la taxonomie associée si besoin.
     *
     * @return void
     */
    public function register(): void;
}
```

- [ ] **Step 2: Run CS + PHPStan**

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse --memory-limit=512M
```

Expected: both pass.

- [ ] **Step 3: Commit**

```bash
git add src/Core/PostTypeInterface.php
git commit -m "feat(core): ajoute PostTypeInterface, contrat des CPT"
```

---

## Task 10: Container — TDD

**Files:**
- Create: `tests/Unit/ContainerTest.php`
- Create: `src/Container.php`

- [ ] **Step 1: Write the failing test `tests/Unit/ContainerTest.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit;

use OliTheme\Container;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests du conteneur de dépendances minimaliste.
 */
final class ContainerTest extends TestCase
{
    public function test_it_should_register_and_retrieve_an_instance(): void
    {
        $container = new Container();
        $instance = new stdClass();
        $container->set(stdClass::class, $instance);

        self::assertSame($instance, $container->get(stdClass::class));
    }

    public function test_it_should_register_and_retrieve_via_factory(): void
    {
        $container = new Container();
        $container->factory('greeting', static fn (): string => 'bonjour');

        self::assertSame('bonjour', $container->get('greeting'));
    }

    public function test_it_should_share_factory_instances_by_default(): void
    {
        $container = new Container();
        $container->factory(stdClass::class, static fn (): stdClass => new stdClass());

        $first = $container->get(stdClass::class);
        $second = $container->get(stdClass::class);

        self::assertSame($first, $second);
    }

    public function test_it_should_pass_container_to_factory_for_dependency_injection(): void
    {
        $container = new Container();
        $container->set('config', ['name' => 'oli']);
        $container->factory('greeter', static function (Container $c): string {
            $config = $c->get('config');

            return 'salut ' . $config['name'];
        });

        self::assertSame('salut oli', $container->get('greeter'));
    }

    public function test_it_should_check_if_service_is_registered(): void
    {
        $container = new Container();
        self::assertFalse($container->has('foo'));
        $container->set('foo', 'bar');
        self::assertTrue($container->has('foo'));
    }

    public function test_it_should_throw_when_service_not_found(): void
    {
        $container = new Container();

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage("Service 'unknown' non enregistré dans le conteneur.");

        $container->get('unknown');
    }
}
```

- [ ] **Step 2: Run test, expect fail**

```bash
vendor/bin/phpunit --testsuite=unit --filter ContainerTest
```

Expected: FAIL with `Class "OliTheme\Container" not found`.

- [ ] **Step 3: Write `src/Container.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme;

use Closure;
use OutOfBoundsException;

/**
 * Conteneur de dépendances minimaliste, inspiré de PSR-11.
 *
 * Stocke des instances déjà construites (set) ou des fabriques paresseuses
 * (factory) qui produisent une instance unique partagée par défaut.
 * Volontairement spartiate : pas d'auto-wiring, pas de cycle de vie complexe,
 * pas de tags. Conçu pour être 100 % testable et lisible.
 *
 * @package OliTheme
 * @since 1.0.0
 */
final class Container
{
    /**
     * Instances déjà résolues, indexées par identifiant logique.
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Fabriques paresseuses, indexées par identifiant logique.
     *
     * @var array<string, Closure>
     */
    private array $factories = [];

    /**
     * Enregistre une instance déjà construite sous l'identifiant donné.
     *
     * @param string $id Identifiant logique du service (souvent le FQCN).
     * @param mixed $instance Instance à mémoriser.
     */
    public function set(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
        unset($this->factories[$id]);
    }

    /**
     * Enregistre une fabrique paresseuse pour l'identifiant donné.
     *
     * La fabrique reçoit le conteneur en argument et n'est appelée qu'au premier
     * appel à get(). Le résultat est mémoïsé (singleton effectif).
     *
     * @param string $id Identifiant logique du service.
     * @param Closure(Container): mixed $factory Fabrique du service.
     */
    public function factory(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /**
     * Récupère le service enregistré sous cet identifiant.
     *
     * @throws OutOfBoundsException Si aucun service n'est enregistré sous cet id.
     */
    public function get(string $id): mixed
    {
        if (\array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            $instance = ($this->factories[$id])($this);
            $this->instances[$id] = $instance;

            return $instance;
        }

        throw new OutOfBoundsException(\sprintf("Service '%s' non enregistré dans le conteneur.", $id));
    }

    /**
     * Indique si un service est enregistré sous cet identifiant.
     */
    public function has(string $id): bool
    {
        return \array_key_exists($id, $this->instances) || isset($this->factories[$id]);
    }
}
```

- [ ] **Step 4: Run test, expect pass**

```bash
vendor/bin/phpunit --testsuite=unit --filter ContainerTest
```

Expected: PASS (6 tests, 6 assertions or more).

- [ ] **Step 5: Run CS + PHPStan**

```bash
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse --memory-limit=512M
```

Expected: clean output.

- [ ] **Step 6: Commit**

```bash
git add src/Container.php tests/Unit/ContainerTest.php
git commit -m "feat(core): ajoute le Container DI minimaliste avec tests TDD"
```

---

## Task 11: RequestContext — TDD

**Files:**
- Create: `tests/Unit/Core/RequestContextTest.php`
- Create: `src/Core/RequestContext.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use OliTheme\Core\RequestContext;
use PHPUnit\Framework\TestCase;

/**
 * Tests du wrapper de la requête HTTP courante.
 */
final class RequestContextTest extends TestCase
{
    public function test_it_should_return_query_var_when_set(): void
    {
        $ctx = new RequestContext(query: ['oli_lang' => 'fr']);
        self::assertSame('fr', $ctx->queryVar('oli_lang'));
    }

    public function test_it_should_return_null_when_query_var_missing(): void
    {
        $ctx = new RequestContext();
        self::assertNull($ctx->queryVar('oli_lang'));
    }

    public function test_it_should_return_cookie_when_set(): void
    {
        $ctx = new RequestContext(cookies: ['oli_lang' => 'en']);
        self::assertSame('en', $ctx->cookie('oli_lang'));
    }

    public function test_it_should_return_request_method_uppercased(): void
    {
        $ctx = new RequestContext(server: ['REQUEST_METHOD' => 'post']);
        self::assertSame('POST', $ctx->method());
    }

    public function test_it_should_default_request_method_to_get(): void
    {
        $ctx = new RequestContext();
        self::assertSame('GET', $ctx->method());
    }

    public function test_it_should_return_remote_ip_from_remote_addr(): void
    {
        $ctx = new RequestContext(server: ['REMOTE_ADDR' => '203.0.113.5']);
        self::assertSame('203.0.113.5', $ctx->ip());
    }

    public function test_it_should_return_header_from_server_http_prefix(): void
    {
        $ctx = new RequestContext(server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9']);
        self::assertSame('fr-FR,fr;q=0.9', $ctx->header('Accept-Language'));
    }

    public function test_it_should_return_null_when_header_missing(): void
    {
        $ctx = new RequestContext();
        self::assertNull($ctx->header('Accept-Language'));
    }
}
```

- [ ] **Step 2: Run test, expect fail**

```bash
vendor/bin/phpunit --testsuite=unit --filter RequestContextTest
```

Expected: FAIL with class not found.

- [ ] **Step 3: Write `src/Core/RequestContext.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Wrapper immuable de la requête HTTP courante.
 *
 * Encapsule $_GET / $_POST / $_COOKIE / $_SERVER pour rendre les classes
 * dépendantes 100 % testables sans toucher aux superglobales en test.
 * Construit avec les valeurs réelles côté production via {@see self::fromGlobals()}.
 *
 * @package OliTheme\Core
 * @since 1.0.0
 */
final readonly class RequestContext
{
    /**
     * @param array<string, mixed> $query Variables de query string ($_GET).
     * @param array<string, mixed> $post Variables POST ($_POST).
     * @param array<string, string> $cookies Cookies ($_COOKIE).
     * @param array<string, mixed> $server Variables serveur ($_SERVER).
     */
    public function __construct(
        private array $query = [],
        private array $post = [],
        private array $cookies = [],
        private array $server = [],
    ) {
    }

    /**
     * Construit le contexte à partir des superglobales PHP courantes.
     */
    public static function fromGlobals(): self
    {
        /** @var array<string, mixed> $get */
        $get = $_GET;
        /** @var array<string, mixed> $post */
        $post = $_POST;
        /** @var array<string, string> $cookie */
        $cookie = $_COOKIE;
        /** @var array<string, mixed> $server */
        $server = $_SERVER;

        return new self($get, $post, $cookie, $server);
    }

    /**
     * Retourne la valeur d'une variable de query string, ou null si absente.
     */
    public function queryVar(string $name): ?string
    {
        $value = $this->query[$name] ?? null;

        return \is_scalar($value) ? (string) $value : null;
    }

    /**
     * Retourne la valeur d'un champ POST, ou null si absent.
     */
    public function postVar(string $name): ?string
    {
        $value = $this->post[$name] ?? null;

        return \is_scalar($value) ? (string) $value : null;
    }

    /**
     * Retourne la valeur d'un cookie, ou null si absent.
     */
    public function cookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * Méthode HTTP en majuscules (GET par défaut).
     */
    public function method(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';

        return \is_string($method) ? \strtoupper($method) : 'GET';
    }

    /**
     * Adresse IP du client. Retourne 0.0.0.0 si introuvable (ex. CLI).
     */
    public function ip(): string
    {
        $ip = $this->server['REMOTE_ADDR'] ?? '0.0.0.0';

        return \is_string($ip) ? $ip : '0.0.0.0';
    }

    /**
     * Lecture d'un en-tête HTTP via la convention $_SERVER['HTTP_*'].
     *
     * @example $ctx->header('Accept-Language')
     */
    public function header(string $name): ?string
    {
        $key = 'HTTP_' . \str_replace('-', '_', \strtoupper($name));
        $value = $this->server[$key] ?? null;

        return \is_string($value) ? $value : null;
    }
}
```

- [ ] **Step 4: Run test, expect pass**

```bash
vendor/bin/phpunit --testsuite=unit --filter RequestContextTest
```

Expected: PASS (8 tests, 8 assertions).

- [ ] **Step 5: Run CS + PHPStan**

```bash
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse --memory-limit=512M
```

Expected: clean.

- [ ] **Step 6: Commit**

```bash
git add src/Core/RequestContext.php tests/Unit/Core/RequestContextTest.php
git commit -m "feat(core): ajoute RequestContext immuable avec tests TDD"
```

---

## Task 12: HookRegistrar — TDD

**Files:**
- Create: `tests/Unit/Core/HookRegistrarTest.php`
- Create: `src/Core/HookRegistrar.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\HookRegistrar;
use PHPUnit\Framework\TestCase;

/**
 * Tests du wrapper testable autour de add_action / add_filter.
 */
final class HookRegistrarTest extends TestCase
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

    public function test_it_should_register_an_action_with_default_priority(): void
    {
        $callback = static function (): void {};

        Functions\expect('add_action')
            ->once()
            ->with('init', $callback, 10, 1);

        $registrar = new HookRegistrar();
        $registrar->action('init', $callback);

        $this->addToAssertionCount(1);
    }

    public function test_it_should_register_a_filter_with_custom_priority_and_args(): void
    {
        $callback = static fn (string $value): string => $value;

        Functions\expect('add_filter')
            ->once()
            ->with('the_title', $callback, 5, 2);

        $registrar = new HookRegistrar();
        $registrar->filter('the_title', $callback, priority: 5, acceptedArgs: 2);

        $this->addToAssertionCount(1);
    }

    public function test_it_should_track_registered_hooks_for_introspection(): void
    {
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);

        $registrar = new HookRegistrar();
        $registrar->action('init', static fn () => null);
        $registrar->filter('the_content', static fn ($v) => $v, 20);

        $registered = $registrar->registered();
        self::assertCount(2, $registered);
        self::assertSame('action', $registered[0]['type']);
        self::assertSame('init', $registered[0]['hook']);
        self::assertSame('filter', $registered[1]['type']);
        self::assertSame('the_content', $registered[1]['hook']);
        self::assertSame(20, $registered[1]['priority']);
    }
}
```

- [ ] **Step 2: Run test, expect fail**

```bash
vendor/bin/phpunit --testsuite=unit --filter HookRegistrarTest
```

Expected: FAIL with class not found.

- [ ] **Step 3: Write `src/Core/HookRegistrar.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Wrapper testable autour des fonctions WordPress add_action / add_filter.
 *
 * Permet (1) d'injecter le registrar dans les modules pour mocker les hooks
 * en test, et (2) de tenir un registre interne des hooks branchés à des
 * fins d'introspection / debug.
 *
 * @package OliTheme\Core
 * @since 1.0.0
 */
final class HookRegistrar
{
    /**
     * Liste des hooks enregistrés depuis la création de l'instance.
     *
     * @var array<int, array{type: string, hook: string, priority: int, acceptedArgs: int}>
     */
    private array $registered = [];

    /**
     * Enregistre une action WordPress.
     *
     * @param string $hook Nom du hook ('init', 'wp_enqueue_scripts'...).
     * @param callable $callback Callback à exécuter.
     * @param int $priority Priorité d'exécution (10 par défaut).
     * @param int $acceptedArgs Nombre d'arguments acceptés (1 par défaut).
     */
    public function action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        \add_action($hook, $callback, $priority, $acceptedArgs);
        $this->registered[] = [
            'type' => 'action',
            'hook' => $hook,
            'priority' => $priority,
            'acceptedArgs' => $acceptedArgs,
        ];
    }

    /**
     * Enregistre un filtre WordPress.
     *
     * @param string $hook Nom du filtre.
     * @param callable $callback Callback à exécuter.
     * @param int $priority Priorité d'exécution (10 par défaut).
     * @param int $acceptedArgs Nombre d'arguments acceptés (1 par défaut).
     */
    public function filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        \add_filter($hook, $callback, $priority, $acceptedArgs);
        $this->registered[] = [
            'type' => 'filter',
            'hook' => $hook,
            'priority' => $priority,
            'acceptedArgs' => $acceptedArgs,
        ];
    }

    /**
     * Retourne la liste des hooks enregistrés via cette instance.
     *
     * @return array<int, array{type: string, hook: string, priority: int, acceptedArgs: int}>
     */
    public function registered(): array
    {
        return $this->registered;
    }
}
```

- [ ] **Step 4: Run test, expect pass**

```bash
vendor/bin/phpunit --testsuite=unit --filter HookRegistrarTest
```

Expected: PASS (3 tests, 3+ assertions).

- [ ] **Step 5: Run CS + PHPStan**

```bash
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse --memory-limit=512M
```

Expected: clean.

- [ ] **Step 6: Commit**

```bash
git add src/Core/HookRegistrar.php tests/Unit/Core/HookRegistrarTest.php
git commit -m "feat(core): ajoute HookRegistrar testable avec tests TDD"
```

---

## Task 13: ViewRenderer — TDD

**Files:**
- Create: `tests/Unit/Core/ViewRendererTest.php`
- Create: `src/Core/ViewRenderer.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use OliTheme\Core\ViewRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Tests du wrapper Lunar Template (ViewRenderer).
 *
 * On crée un répertoire de templates temporaire pour chaque test, on y écrit
 * un .tpl, on appelle render() et on vérifie la sortie HTML.
 */
final class ViewRendererTest extends TestCase
{
    private string $templateDir;
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateDir = \sys_get_temp_dir() . '/oli-theme-templates-' . \uniqid();
        $this->cacheDir = \sys_get_temp_dir() . '/oli-theme-cache-' . \uniqid();
        \mkdir($this->templateDir, recursive: true);
        \mkdir($this->cacheDir, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->templateDir);
        $this->rrmdir($this->cacheDir);
        parent::tearDown();
    }

    public function test_it_should_render_a_template_with_variables(): void
    {
        $this->writeTemplate('hello.html.tpl', '<p>Bonjour [[ name ]]</p>');

        $renderer = new ViewRenderer($this->templateDir, $this->cacheDir);
        $output = $renderer->render('hello.html', ['name' => 'Olivier']);

        self::assertSame('<p>Bonjour Olivier</p>', \trim($output));
    }

    public function test_it_should_escape_variables_by_default(): void
    {
        $this->writeTemplate('escape.html.tpl', '<p>[[ raw ]]</p>');

        $renderer = new ViewRenderer($this->templateDir, $this->cacheDir);
        $output = $renderer->render('escape.html', ['raw' => '<script>alert(1)</script>']);

        self::assertStringContainsString('&lt;script&gt;', $output);
        self::assertStringNotContainsString('<script>', $output);
    }

    public function test_it_should_inject_default_variables_into_every_render(): void
    {
        $this->writeTemplate('site.html.tpl', '<title>[[ siteName ]]</title>');

        $renderer = new ViewRenderer($this->templateDir, $this->cacheDir);
        $renderer->setDefaultVariables(['siteName' => 'Olikalari']);
        $output = $renderer->render('site.html', []);

        self::assertStringContainsString('Olikalari', $output);
    }

    private function writeTemplate(string $name, string $content): void
    {
        \file_put_contents($this->templateDir . '/' . $name, $content);
    }

    private function rrmdir(string $dir): void
    {
        if (!\is_dir($dir)) {
            return;
        }
        foreach (\scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            \is_dir($path) ? $this->rrmdir($path) : \unlink($path);
        }
        \rmdir($dir);
    }
}
```

- [ ] **Step 2: Run test, expect fail**

```bash
vendor/bin/phpunit --testsuite=unit --filter ViewRendererTest
```

Expected: FAIL with `Class "OliTheme\Core\ViewRenderer" not found`.

- [ ] **Step 3: Write `src/Core/ViewRenderer.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Core;

use Lunar\Template\AdvancedTemplateEngine;

/**
 * Wrapper du moteur Lunar Template Engine pour le thème oli-theme.
 *
 * Centralise la configuration du moteur (chemin templates, cache),
 * expose les variables globales injectées dans chaque rendu (siteName,
 * lang, i18n...) et fournit des macros utilitaires (asset, formatDate...).
 *
 * Les templates portent l'extension .html.tpl et sont stockés dans
 * templates/ à la racine du thème.
 *
 * @package OliTheme\Core
 * @since 1.0.0
 */
final class ViewRenderer
{
    private AdvancedTemplateEngine $engine;

    /**
     * Variables injectées par défaut dans chaque rendu.
     *
     * @var array<string, mixed>
     */
    private array $defaults = [];

    /**
     * @param string $templatesPath Chemin absolu vers le dossier templates/.
     * @param string $cachePath Chemin absolu vers le dossier de cache compilé.
     */
    public function __construct(string $templatesPath, string $cachePath)
    {
        $this->engine = new AdvancedTemplateEngine(
            templatePath: $templatesPath,
            cachePath: $cachePath,
        );
    }

    /**
     * Définit les variables disponibles dans tous les templates rendus
     * par cette instance (siteName, lang, i18n, etc.).
     *
     * @param array<string, mixed> $variables
     */
    public function setDefaultVariables(array $variables): void
    {
        $this->defaults = $variables;
    }

    /**
     * Enregistre une macro utilisable dans les templates via ##name(args)##.
     */
    public function registerMacro(string $name, callable $callback): void
    {
        $this->engine->registerMacro($name, $callback);
    }

    /**
     * Rend un template Lunar et retourne le HTML produit.
     *
     * @param string $template Nom logique du template (sans extension .tpl).
     *                          Ex. 'pages/page' rend templates/pages/page.html.tpl.
     * @param array<string, mixed> $variables Variables propres à ce rendu (fusionnées
     *                                         par-dessus les valeurs par défaut).
     */
    public function render(string $template, array $variables = []): string
    {
        $merged = \array_merge($this->defaults, $variables);

        return $this->engine->render($template, $merged);
    }
}
```

- [ ] **Step 4: Run test, expect pass**

```bash
vendor/bin/phpunit --testsuite=unit --filter ViewRendererTest
```

Expected: PASS (3 tests, 3+ assertions). If Lunar uses a different template extension or macro API than expected, adjust the test fixtures and re-run; do not modify ViewRenderer to bypass Lunar's contract.

- [ ] **Step 5: Run CS + PHPStan**

```bash
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse --memory-limit=512M
```

Expected: clean. If PHPStan complains about the Lunar API signatures, add a narrow `@phpstan-ignore-next-line` comment with a TODO referencing this task.

- [ ] **Step 6: Commit**

```bash
git add src/Core/ViewRenderer.php tests/Unit/Core/ViewRendererTest.php
git commit -m "feat(core): ajoute ViewRenderer (wrapper Lunar Template) avec tests TDD"
```

---

## Task 14: AssetManager — TDD

**Files:**
- Create: `tests/Unit/Core/AssetManagerTest.php`
- Create: `src/Core/AssetManager.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\AssetManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests d'enregistrement des assets (CSS / JS modules) avec versioning auto.
 */
final class AssetManagerTest extends TestCase
{
    private string $themePath;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->themePath = \sys_get_temp_dir() . '/oli-asset-test-' . \uniqid();
        \mkdir($this->themePath . '/assets/css', recursive: true);
        \mkdir($this->themePath . '/assets/js', recursive: true);
        \file_put_contents($this->themePath . '/assets/css/main.css', 'body{}');
        \file_put_contents($this->themePath . '/assets/js/main.js', '// js');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        $this->rrmdir($this->themePath);
        parent::tearDown();
    }

    public function test_it_should_enqueue_main_stylesheet_with_filemtime_version(): void
    {
        $expectedVersion = (string) \filemtime($this->themePath . '/assets/css/main.css');

        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('oli-theme', 'https://example.test/wp-content/themes/oli/assets/css/main.css', [], $expectedVersion);

        Functions\expect('wp_enqueue_script_module')
            ->once();

        $manager = new AssetManager($this->themePath, 'https://example.test/wp-content/themes/oli');
        $manager->enqueueFront();

        $this->addToAssertionCount(1);
    }

    public function test_it_should_enqueue_main_script_module(): void
    {
        $expectedVersion = (string) \filemtime($this->themePath . '/assets/js/main.js');

        Functions\expect('wp_enqueue_style')->once();
        Functions\expect('wp_enqueue_script_module')
            ->once()
            ->with('oli-theme', 'https://example.test/wp-content/themes/oli/assets/js/main.js', [], $expectedVersion);

        $manager = new AssetManager($this->themePath, 'https://example.test/wp-content/themes/oli');
        $manager->enqueueFront();

        $this->addToAssertionCount(1);
    }

    public function test_it_should_use_fallback_version_when_file_missing(): void
    {
        \unlink($this->themePath . '/assets/css/main.css');

        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('oli-theme', \Mockery::any(), [], '1.0.0');
        Functions\expect('wp_enqueue_script_module')->once();

        $manager = new AssetManager($this->themePath, 'https://example.test/wp-content/themes/oli');
        $manager->enqueueFront();

        $this->addToAssertionCount(1);
    }

    private function rrmdir(string $dir): void
    {
        if (!\is_dir($dir)) {
            return;
        }
        foreach (\scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            \is_dir($path) ? $this->rrmdir($path) : \unlink($path);
        }
        \rmdir($dir);
    }
}
```

- [ ] **Step 2: Run test, expect fail**

```bash
vendor/bin/phpunit --testsuite=unit --filter AssetManagerTest
```

Expected: FAIL with class not found.

- [ ] **Step 3: Write `src/Core/AssetManager.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Gestionnaire d'enregistrement des assets CSS / JS du thème.
 *
 * Versionne automatiquement les fichiers via filemtime() pour que les
 * navigateurs invalident leur cache à chaque modification du fichier.
 * À enregistrer sur les hooks 'wp_enqueue_scripts' (front) et
 * 'admin_enqueue_scripts' (admin).
 *
 * @package OliTheme\Core
 * @since 1.0.0
 */
final class AssetManager
{
    /**
     * @param string $themePath Chemin absolu du thème (sans slash final).
     * @param string $themeUri URL absolue du thème (sans slash final).
     */
    public function __construct(
        private readonly string $themePath,
        private readonly string $themeUri,
    ) {
    }

    /**
     * Enregistre les feuilles de styles et modules JS frontaux.
     */
    public function enqueueFront(): void
    {
        \wp_enqueue_style(
            'oli-theme',
            $this->themeUri . '/assets/css/main.css',
            [],
            $this->version('assets/css/main.css'),
        );

        \wp_enqueue_script_module(
            'oli-theme',
            $this->themeUri . '/assets/js/main.js',
            [],
            $this->version('assets/js/main.js'),
        );
    }

    /**
     * Enregistre les assets de l'administration WordPress.
     * Étendu plus tard par les modules SEO / Settings.
     */
    public function enqueueAdmin(): void
    {
        \wp_enqueue_style(
            'oli-theme-admin',
            $this->themeUri . '/assets/css/admin.css',
            [],
            $this->version('assets/css/admin.css'),
        );
    }

    /**
     * Calcule la version d'un fichier à partir de son mtime
     * pour invalider le cache navigateur lors d'une modification.
     */
    private function version(string $relativePath): string
    {
        $absolute = $this->themePath . '/' . $relativePath;

        return \file_exists($absolute) ? (string) \filemtime($absolute) : '1.0.0';
    }
}
```

- [ ] **Step 4: Run test, expect pass**

```bash
vendor/bin/phpunit --testsuite=unit --filter AssetManagerTest
```

Expected: PASS (3 tests).

- [ ] **Step 5: Run CS + PHPStan**

```bash
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse --memory-limit=512M
```

Expected: clean.

- [ ] **Step 6: Commit**

```bash
git add src/Core/AssetManager.php tests/Unit/Core/AssetManagerTest.php
git commit -m "feat(core): ajoute AssetManager (enqueue CSS/JS + versioning filemtime) avec tests TDD"
```

---

## Task 15: Theme bootstrap class

**Files:**
- Create: `tests/Unit/ThemeTest.php`
- Create: `src/Theme.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Container;
use OliTheme\Core\AssetManager;
use OliTheme\Core\HookRegistrar;
use OliTheme\Core\RequestContext;
use OliTheme\Core\ViewRenderer;
use OliTheme\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Tests du bootstrap principal du thème.
 */
final class ThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\stubs([
            'get_template_directory_uri' => 'https://example.test/wp-content/themes/oli-theme',
        ]);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Theme::reset();
        parent::tearDown();
    }

    public function test_it_should_register_core_services_in_container(): void
    {
        Functions\when('add_action')->justReturn(true);

        Theme::boot(\sys_get_temp_dir());
        $container = Theme::container();

        self::assertInstanceOf(Container::class, $container);
        self::assertInstanceOf(ViewRenderer::class, $container->get(ViewRenderer::class));
        self::assertInstanceOf(AssetManager::class, $container->get(AssetManager::class));
        self::assertInstanceOf(RequestContext::class, $container->get(RequestContext::class));
        self::assertInstanceOf(HookRegistrar::class, $container->get(HookRegistrar::class));
    }

    public function test_it_should_register_enqueue_hook_on_boot(): void
    {
        Functions\expect('add_action')
            ->atLeast()->once()
            ->with('wp_enqueue_scripts', \Mockery::any());

        Theme::boot(\sys_get_temp_dir());

        $this->addToAssertionCount(1);
    }

    public function test_it_should_be_idempotent_on_boot(): void
    {
        Functions\when('add_action')->justReturn(true);

        Theme::boot(\sys_get_temp_dir());
        $first = Theme::container();

        Theme::boot(\sys_get_temp_dir());
        $second = Theme::container();

        self::assertSame($first, $second);
    }
}
```

- [ ] **Step 2: Run test, expect fail**

```bash
vendor/bin/phpunit --testsuite=unit --filter ThemeTest
```

Expected: FAIL with class not found.

- [ ] **Step 3: Write `src/Theme.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme;

use OliTheme\Core\AssetManager;
use OliTheme\Core\HookRegistrar;
use OliTheme\Core\RequestContext;
use OliTheme\Core\ViewRenderer;

/**
 * Bootstrap principal du thème oli-theme.
 *
 * Singleton applicatif : la première invocation de boot() crée le conteneur,
 * enregistre les services Core et branche les hooks WordPress fondateurs.
 * Les invocations suivantes sont idempotentes.
 *
 * @package OliTheme
 * @since 1.0.0
 */
final class Theme
{
    private static ?Container $container = null;
    private static ?string $themePath = null;

    /**
     * Démarre le thème. Appelé depuis functions.php.
     *
     * @param string $themePath Chemin absolu du thème (généralement __DIR__).
     */
    public static function boot(string $themePath): void
    {
        if (self::$container !== null) {
            return;
        }

        self::$themePath = $themePath;
        self::$container = self::buildContainer($themePath);
        self::registerCoreHooks(self::$container);
    }

    /**
     * Retourne le conteneur applicatif (à appeler après boot()).
     *
     * @throws \LogicException Si boot() n'a pas encore été appelé.
     */
    public static function container(): Container
    {
        if (self::$container === null) {
            throw new \LogicException('Theme::boot() doit être appelé avant Theme::container().');
        }

        return self::$container;
    }

    /**
     * Réinitialise l'état statique. Réservé aux tests.
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$container = null;
        self::$themePath = null;
    }

    /**
     * Hook 'after_switch_theme' : initialisation à l'activation du thème.
     */
    public static function onActivation(): void
    {
        \flush_rewrite_rules();
    }

    /**
     * Hook 'switch_theme' : nettoyage à la désactivation du thème.
     */
    public static function onDeactivation(): void
    {
        \flush_rewrite_rules();
    }

    /**
     * Construit le conteneur et y enregistre les services Core.
     */
    private static function buildContainer(string $themePath): Container
    {
        $container = new Container();
        $themeUri = \get_template_directory_uri();

        $container->set(RequestContext::class, RequestContext::fromGlobals());
        $container->set(HookRegistrar::class, new HookRegistrar());
        $container->factory(
            ViewRenderer::class,
            static fn (): ViewRenderer => new ViewRenderer(
                $themePath . '/templates',
                $themePath . '/.cache/templates',
            ),
        );
        $container->factory(
            AssetManager::class,
            static fn (): AssetManager => new AssetManager($themePath, $themeUri),
        );

        return $container;
    }

    /**
     * Branche les hooks WordPress fondateurs (assets, activation/désactivation).
     */
    private static function registerCoreHooks(Container $container): void
    {
        $registrar = $container->get(HookRegistrar::class);
        \assert($registrar instanceof HookRegistrar);

        $registrar->action('wp_enqueue_scripts', static function () use ($container): void {
            $assets = $container->get(AssetManager::class);
            \assert($assets instanceof AssetManager);
            $assets->enqueueFront();
        });

        $registrar->action('admin_enqueue_scripts', static function () use ($container): void {
            $assets = $container->get(AssetManager::class);
            \assert($assets instanceof AssetManager);
            $assets->enqueueAdmin();
        });

        \add_action('after_switch_theme', [self::class, 'onActivation']);
        \add_action('switch_theme', [self::class, 'onDeactivation']);
    }
}
```

- [ ] **Step 4: Run test, expect pass**

```bash
vendor/bin/phpunit --testsuite=unit --filter ThemeTest
```

Expected: PASS (3 tests).

- [ ] **Step 5: Run CS + PHPStan**

```bash
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse --memory-limit=512M
```

Expected: clean.

- [ ] **Step 6: Commit**

```bash
git add src/Theme.php tests/Unit/ThemeTest.php
git commit -m "feat(core): ajoute Theme::boot() avec enregistrement des services Core"
```

---

## Task 16: functions.php

**Files:**
- Create: `functions.php`

- [ ] **Step 1: Write `functions.php`**

```php
<?php

declare(strict_types=1);

/**
 * Bootstrap principal du thème oli-theme.
 *
 * Charge l'autoloader Composer puis délègue toute l'initialisation à
 * la classe \OliTheme\Theme. Toute logique métier vit dans src/.
 *
 * @package OliTheme
 */

if (!\defined('ABSPATH')) {
    exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (!\file_exists($autoload)) {
    \wp_die('Le thème oli-theme n\'a pas été installé. Lancer "composer install" depuis le répertoire du thème.');
}

require_once $autoload;

\OliTheme\Theme::boot(__DIR__);
```

- [ ] **Step 2: PHP lint**

```bash
php -l functions.php
```

Expected: `No syntax errors detected in functions.php`.

- [ ] **Step 3: Commit**

```bash
git add functions.php
git commit -m "feat: ajoute functions.php (bootstrap du thème via Theme::boot)"
```

---

## Task 17: Empty layout template + theme-bridge fallback

**Files:**
- Create: `templates/layouts/empty.html.tpl`
- Create: `theme-bridge/index.php`

- [ ] **Step 1: Create directories and write template**

```bash
mkdir -p templates/layouts theme-bridge
```

Write `templates/layouts/empty.html.tpl`:

```html
[# Layout minimal de pontage. Variables attendues:
     - title (string)
     - message (string)
   Utilisé tant qu'aucun layout complet n'est disponible (cycle 1 - Plan 1).
#]
<!DOCTYPE html>
<html lang="[[ lang ]]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>[[ title ]]</title>
    [[! wpHead !]]
</head>
<body class="oli-theme-bootstrap">
    <main>
        <h1>[[ title ]]</h1>
        <p>[[ message ]]</p>
    </main>
    [[! wpFooter !]]
</body>
</html>
```

- [ ] **Step 2: Write `theme-bridge/index.php`**

```php
<?php

declare(strict_types=1);

/**
 * Pont WordPress -> contrôleur de fallback.
 *
 * Tant que le module Posts n'est pas livré (Plan 3), affiche un layout
 * minimal "Coming soon" via Lunar pour valider la chaîne de rendu.
 *
 * @package OliTheme
 */

use OliTheme\Core\ViewRenderer;
use OliTheme\Theme;

\ob_start();
\wp_head();
$wpHead = \ob_get_clean() ?: '';

\ob_start();
\wp_footer();
$wpFooter = \ob_get_clean() ?: '';

$renderer = Theme::container()->get(ViewRenderer::class);
\assert($renderer instanceof ViewRenderer);

echo $renderer->render('layouts/empty.html', [
    'lang' => \get_bloginfo('language') ?: 'fr',
    'title' => \get_bloginfo('name'),
    'message' => \__('Site en cours de construction.', 'oli-theme'),
    'wpHead' => $wpHead,
    'wpFooter' => $wpFooter,
]);
```

- [ ] **Step 3: PHP lint**

```bash
php -l theme-bridge/index.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4: Symlink theme-bridge files at theme root**

WordPress looks for templates at the theme root. We expose them via tiny shims:

Create `index.php` at the theme root:

```php
<?php

declare(strict_types=1);

/** @package OliTheme */

require __DIR__ . '/theme-bridge/index.php';
```

- [ ] **Step 5: PHP lint**

```bash
php -l index.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add templates/layouts/empty.html.tpl theme-bridge/index.php index.php
git commit -m "feat(view): ajoute le layout minimal et le pont WordPress index.php"
```

---

## Task 18: ActivationTest (integration)

**Files:**
- Create: `tests/Integration/ActivationTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Integration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Test d'intégration : vérifie que le bootstrap du thème ne lève aucune
 * exception et que les hooks d'activation/désactivation tournent
 * sans erreur fatale (mocks Brain Monkey).
 */
final class ActivationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\stubs([
            'get_template_directory_uri' => 'https://example.test/wp-content/themes/oli-theme',
            'flush_rewrite_rules' => null,
        ]);
        Functions\when('add_action')->justReturn(true);
    }

    protected function tearDown(): void
    {
        Theme::reset();
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_boot_runs_without_throwing(): void
    {
        Theme::boot(\dirname(__DIR__, 2));
        self::assertNotNull(Theme::container());
    }

    public function test_activation_hook_calls_flush_rewrite_rules(): void
    {
        Functions\expect('flush_rewrite_rules')->once();
        Theme::onActivation();
        $this->addToAssertionCount(1);
    }

    public function test_deactivation_hook_calls_flush_rewrite_rules(): void
    {
        Functions\expect('flush_rewrite_rules')->once();
        Theme::onDeactivation();
        $this->addToAssertionCount(1);
    }
}
```

- [ ] **Step 2: Run integration tests**

```bash
vendor/bin/phpunit --testsuite=integration
```

Expected: PASS (3 tests).

- [ ] **Step 3: Run full CI suite to confirm everything is green**

```bash
composer ci
```

Expected: cs OK, phpstan OK, all tests PASS.

- [ ] **Step 4: Commit**

```bash
git add tests/Integration/ActivationTest.php
git commit -m "test(integration): vérifie le bootstrap et les hooks d'activation du thème"
```

---

## Task 19: GitHub Actions CI workflow

**Files:**
- Create: `.github/workflows/ci.yml`

- [ ] **Step 1: Write the workflow**

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:

jobs:
  qa:
    name: QA on PHP ${{ matrix.php }}
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: ['8.3', '8.4', '8.5']
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: xdebug
          tools: composer:v2

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-php-${{ matrix.php }}-${{ hashFiles('composer.lock') }}
          restore-keys: ${{ runner.os }}-php-${{ matrix.php }}-

      - name: Install dependencies
        run: composer install --no-progress --prefer-dist

      - name: Code style
        run: composer run-script cs

      - name: Static analysis
        run: composer run-script analyse

      - name: Run tests
        run: composer run-script test:all
```

- [ ] **Step 2: Validate YAML syntax**

```bash
python3 -c "import yaml; yaml.safe_load(open('.github/workflows/ci.yml'))" && echo OK
```

Expected: `OK`.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "ci: ajoute le workflow GitHub Actions (matrice PHP 8.3/8.4/8.5)"
```

---

## Task 20: Captainhook pre-commit hooks

**Files:**
- Create: `captainhook.json`

- [ ] **Step 1: Write `captainhook.json`**

```json
{
    "config": {
        "verbosity": "normal"
    },
    "pre-commit": {
        "enabled": true,
        "actions": [
            {
                "action": "vendor/bin/php-cs-fixer fix --dry-run --diff",
                "options": [],
                "conditions": []
            },
            {
                "action": "vendor/bin/phpstan analyse --memory-limit=512M",
                "options": [],
                "conditions": []
            },
            {
                "action": "vendor/bin/phpunit --testsuite=unit",
                "options": [],
                "conditions": []
            }
        ]
    },
    "commit-msg": {
        "enabled": false,
        "actions": []
    }
}
```

- [ ] **Step 2: Install captainhook hooks (optional, contributor-side)**

```bash
vendor/bin/captainhook install --no-interaction
```

Expected: hooks installed (`.git/hooks/pre-commit` symlinked or written).

- [ ] **Step 3: Commit**

```bash
git add captainhook.json
git commit -m "chore: configure captainhook (cs:fix dry-run + phpstan + phpunit unit en pre-commit)"
```

---

## Task 21: Documentation — installation guide

**Files:**
- Create: `docs/installation.md`

- [ ] **Step 1: Write `docs/installation.md`**

```markdown
# Installation du thème oli-theme

## Prérequis

- **PHP** ≥ 8.3 (testé jusqu'à 8.5)
- **WordPress** ≥ 6.9
- **Composer** ≥ 2.0
- Un hébergeur permettant d'exécuter `composer install` ou un déploiement
  embarquant déjà `vendor/`.

## Installation pas à pas

### 1. Récupérer les sources

Cloner le dépôt dans le dossier `wp-content/themes/` de votre installation WordPress :

```bash
cd wp-content/themes/
git clone <url-du-depot> oli-theme
cd oli-theme
```

### 2. Installer les dépendances PHP

```bash
composer install --no-dev --optimize-autoloader
```

Pour développer (tests + analyse statique) :

```bash
composer install
```

### 3. Activer le thème

Dans l'administration WordPress :

1. Aller dans `Apparence > Thèmes`
2. Cliquer sur **Activer** sur "Oli Theme"

À l'activation, le thème :

- Crée la table `oli_redirects` (cycle ultérieur).
- Initialise les options par défaut (`oli_theme_settings`, `oli_languages`).
- Enregistre les Custom Post Types et taxonomies (cycles ultérieurs).
- Vide les rewrite rules pour réenregistrer les URLs multilingues.

### 4. Vérifier l'installation

Vérifier que la page d'accueil affiche le message "Site en cours de construction."
(layout temporaire de bootstrap, sera remplacé au Plan 3).

## Configuration recommandée

- Activer les **permalinks** : `Réglages > Permaliens > Nom de l'article`.
- Définir le fuseau horaire dans `Réglages > Général`.
- Vérifier `wp_mail()` (test du formulaire de contact lors du Plan 8).

## Mise à jour du thème

```bash
cd wp-content/themes/oli-theme
git pull
composer install --no-dev --optimize-autoloader
```

## Désinstallation

Désactiver le thème dans `Apparence > Thèmes`. Les options et contenus sont
conservés (réactivation propre possible). Pour purger :

```bash
wp option delete oli_theme_settings
wp option delete oli_languages
```

## Problèmes courants

| Symptôme | Cause probable | Solution |
|----------|----------------|----------|
| Page blanche après activation | Autoload absent | Lancer `composer install` |
| 404 sur `/fr/...` | Rewrite rules pas vidées | Aller dans `Réglages > Permaliens > Enregistrer` |
| `Class \OliTheme\Theme not found` | PSR-4 mal configuré | Vérifier `composer dump-autoload` |
```

- [ ] **Step 2: Commit**

```bash
mkdir -p docs
git add docs/installation.md
git commit -m "docs: ajoute le guide d'installation du thème"
```

---

## Task 22: Documentation — architecture

**Files:**
- Create: `docs/architecture.md`

- [ ] **Step 1: Write `docs/architecture.md`**

```markdown
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
        │
        ▼
.htaccess → index.php (theme-bridge)
        │
        ▼
Theme::container()->get(...Controller::class)->renderXxx()
        │
        ├── Model::find() → DTO
        ├── SeoController::buildHead()
        └── compose ViewModel
        ▼
ViewRenderer::render('pages/page', $vm)
        ▼
Lunar compile le template (.html.tpl)
        ▼
HTML envoyé
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
```

- [ ] **Step 2: Commit**

```bash
git add docs/architecture.md
git commit -m "docs: ajoute le document d'architecture du thème"
```

---

## Task 23: Documentation — testing guide

**Files:**
- Create: `docs/testing.md`

- [ ] **Step 1: Write `docs/testing.md`**

```markdown
# Tester le thème oli-theme

## Lancer les tests

```bash
composer test            # tests unitaires
composer test:integration # tests d'intégration
composer test:all        # les deux
composer test:coverage   # rapport HTML dans coverage/html
```

## Stratégie TDD

Chaque classe est précédée de son test. Cycle Red → Green → Refactor :

1. Écrire un test qui décrit le comportement attendu.
2. Lancer le test, vérifier qu'il échoue.
3. Écrire le **minimum** de code pour le faire passer.
4. Lancer à nouveau, vérifier qu'il passe.
5. Refactorer en gardant le test vert.

## Conventions

- Une méthode `test_it_should_*` par cas.
- Trois sections par test : Arrange / Act / Assert.
- `@dataProvider` pour les cas multiples (validation, parsing).
- Mocks Brain Monkey pour les fonctions WordPress (`get_post`, `wp_mail`, ...).
- Pas d'assertions sur des messages traduits — utiliser des clés ou des codes.

## Brain Monkey

Brain Monkey permet de mocker les fonctions WordPress sans charger WordPress :

```php
use Brain\Monkey;
use Brain\Monkey\Functions;

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

public function test_example(): void
{
    // Stub : retourne toujours la même valeur
    Functions\when('get_option')->justReturn(['fr', 'en']);

    // Expectation : vérifie l'appel
    Functions\expect('wp_mail')
        ->once()
        ->with('to@example.com', 'Sujet', \Mockery::pattern('/.*/'))
        ->andReturn(true);

    // Filter / action mocking
    \Brain\Monkey\Filters\expectApplied('the_title')->andReturn('Mocked');
}
```

## Couverture de code

Cible globale : **≥ 90 %**. Cibles par domaine dans la spec (`docs/superpowers/specs/2026-05-05-oli-theme-design.md` section 5.9).

Générer le rapport :

```bash
composer test:coverage
open coverage/html/index.html  # ou xdg-open sous Linux
```

## CI

GitHub Actions exécute `composer ci` sur PHP 8.3, 8.4, 8.5 à chaque push et PR. Les hooks pre-commit (captainhook) reproduisent localement le même cycle (cs + phpstan + tests unitaires).

## Tests d'intégration

Réservés aux scénarios qui ne peuvent pas être unitaires (activation, génération de sitemap, requêtes croisées). Restent rapides — pas de WordPress complet sauf besoin explicite.
```

- [ ] **Step 2: Commit**

```bash
git add docs/testing.md
git commit -m "docs: ajoute le guide de tests et patterns Brain Monkey"
```

---

## Task 24: ADR 0001 — MVC pattern

**Files:**
- Create: `docs/decisions/0001-mvc-pattern.md`

- [ ] **Step 1: Write the ADR**

```markdown
# ADR 0001 — Pattern MVC strict appliqué à WordPress

**Date :** 2026-05-05
**Statut :** Accepté

## Contexte

WordPress n'est pas un framework MVC. Sa hiérarchie de templates `single.php`,
`page.php`, `archive.php` mêle traditionnellement HTML, requêtes SQL et logique
métier dans un même fichier PHP. Cette pratique est :

- difficile à tester (couplage fort à WordPress et au DOM rendu) ;
- difficile à maintenir sur le long terme ;
- difficile à transmettre à un autre prestataire ;
- en contradiction avec les principes SOLID exigés par le commanditaire.

## Décision

Le thème impose un pattern MVC strict, par discipline et par convention de
nommage :

- **Modèles** (`src/*/...Model.php`) : encapsulent la donnée (post types,
  meta, options, requêtes WP_Query). N'émettent jamais de HTML.
- **Contrôleurs** (`src/*/...Controller.php`) : orchestrent (récupèrent
  données via Models, préparent un ViewModel, appellent le Renderer).
  Ne contiennent jamais de HTML.
- **Vues** (`templates/**/*.html.tpl`) : templates Lunar Template Engine.
  N'appellent jamais de fonction WordPress, ne contiennent aucune logique
  métier ; seulement de l'affichage.
- **Modules** (`src/*/...Module.php`) : un par domaine fonctionnel
  (I18n, SEO, Events, ...). Enregistrent les hooks WordPress et instancient
  leurs contrôleurs.

Les fichiers de pontage WP (`theme-bridge/single.php`, `page.php`, ...)
contiennent **une seule ligne d'appel** au contrôleur correspondant.

## Conséquences

### Positives

- Tests unitaires possibles via Brain Monkey sans charger WordPress.
- Lisibilité : on sait où chercher chaque type de logique.
- Réutilisation : Models et Views évoluent indépendamment.
- Transmissibilité : convention claire pour tout futur prestataire.

### Négatives

- Plus de classes que dans un thème WP classique → courbe d'apprentissage.
- Discipline collective requise (un PR qui met du HTML dans un Model passe
  CI mais viole l'architecture — revue de code nécessaire).

## Alternatives écartées

- **Templates WP natifs** : rejeté (mélange vue/logique).
- **Timber/Twig** : rejeté au profit de Lunar Template (cf. ADR 0002).
- **Frameworks PHP type Laravel intégré à WP** : rejeté (overkill, violation
  des conventions WordPress et complexité de déploiement).
```

- [ ] **Step 2: Commit**

```bash
mkdir -p docs/decisions
git add docs/decisions/0001-mvc-pattern.md
git commit -m "docs(adr): ADR 0001 - pattern MVC strict appliqué à WordPress"
```

---

## Task 25: ADR 0002 — Lunar Template Engine

**Files:**
- Create: `docs/decisions/0002-lunar-template.md`

- [ ] **Step 1: Write the ADR**

```markdown
# ADR 0002 — Choix de Lunar Template Engine

**Date :** 2026-05-05
**Statut :** Accepté

## Contexte

Le thème impose une séparation stricte vue / logique (cf. ADR 0001). Trois
moteurs de templates étaient candidats :

1. **Timber + Twig** — standard MVC pour WordPress, dépendance Composer
2. **Moteur custom léger** maison — zéro dépendance, à coder/tester
3. **Templates PHP WP natifs** — rejeté par ADR 0001

## Décision

Adopter **Lunar Template Engine** (`yrbane/lunar-template`) :

- Standalone, aucune dépendance hors PHP 8.3+
- 100 % testé, PHPStan niveau 7
- Syntaxe propre :
  - `[[ var ]]` (variable échappée),
  - `[[! var !]]` ou `| raw` (sans échappement),
  - `[% extends 'base.tpl' %]` / `[% block content %]` (héritage multi-niveaux),
  - `##macroName(args)##` (macros réutilisables),
  - `[% include 'partial.tpl' %]`, `[% set foo = ... %]`.
- Cache compilé sur disque, prewarming supporté.
- Échappement XSS automatique, validation des chemins de templates.
- Architecture modulaire (Parser / Compiler / Renderer / Cache) — propre pour
  injection de dépendances.
- Maintenu par l'auteur (yrbane), licence MIT.

## Conséquences

### Positives

- Cohérence avec la philosophie "zéro dépendance lourde" du commanditaire.
- Performance : cache compilé, pas de jQuery côté front.
- Sécurité : échappement XSS par défaut.
- Aligné sur les compétences PHP du futur prestataire (pas de Twig à apprendre).

### Négatives

- Communauté plus restreinte que Twig.
- Documentation moins fournie ; certaines fonctionnalités à découvrir au cas par cas.

## Alternatives écartées

- **Timber/Twig** : ajout d'une dépendance significative et d'une syntaxe
  Twig à apprendre pour un thème custom à long terme.
- **Moteur custom maison** : reproduire un sous-ensemble de Lunar serait
  inutile et chronophage.
- **PHP natif `<?php ?>`** : violation de la séparation vue/logique.
```

- [ ] **Step 2: Commit**

```bash
git add docs/decisions/0002-lunar-template.md
git commit -m "docs(adr): ADR 0002 - choix de Lunar Template Engine"
```

---

## Task 26: CHANGELOG initial

**Files:**
- Create: `CHANGELOG.md`

- [ ] **Step 1: Write `CHANGELOG.md`**

```markdown
# Changelog

Format basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), versions selon [Semantic Versioning](https://semver.org/lang/fr/).

## [Unreleased]

## [1.0.0-alpha] - 2026-05-05

### Added

- Bootstrap du thème via `OliTheme\Theme::boot()`.
- Conteneur de dépendances minimaliste (`OliTheme\Container`).
- Services Core : `ViewRenderer` (Lunar), `AssetManager`, `RequestContext`, `HookRegistrar`.
- Contrats `ModuleInterface` et `PostTypeInterface`.
- Layout minimal `templates/layouts/empty.html.tpl` + pont `theme-bridge/index.php`.
- Pipeline qualité : PHPUnit 11, Brain Monkey, PHPStan niveau 8, PHP-CS-Fixer (PSR-12), captainhook.
- Workflow GitHub Actions (matrice PHP 8.3 / 8.4 / 8.5).
- Documentation : architecture, installation, tests, ADR 0001 (MVC), ADR 0002 (Lunar).
```

- [ ] **Step 2: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs: ajoute le CHANGELOG initial (1.0.0-alpha)"
```

---

## Task 27: Final verification — full CI green

**Files:** none (verification step)

- [ ] **Step 1: Refresh dependencies and autoload**

```bash
composer dump-autoload --optimize
```

Expected: no errors.

- [ ] **Step 2: Run full quality pipeline**

```bash
composer ci
```

Expected: cs OK, phpstan OK, all tests PASS (5+ unit test files, 1 integration test).

- [ ] **Step 3: Run coverage report**

```bash
composer test:coverage
```

Expected: coverage report generated in `coverage/`. Current Foundation coverage should be ≥ 90 % on `src/Core/` and `src/Container.php`, and ≥ 80 % on `src/Theme.php`.

- [ ] **Step 4: List committed files for sanity check**

```bash
git log --oneline
git ls-files | head -50
```

Expected commits include (in order) :
- `chore: initialise le dépôt git...`
- `docs: ajoute le README...`
- `chore: ajoute composer.json...`
- `feat: ajoute l'en-tête WordPress...`
- `chore: configure PHPUnit...`
- `chore: configure PHPStan...`
- `chore: configure PHP-CS-Fixer...`
- `feat(core): ajoute ModuleInterface...`
- `feat(core): ajoute PostTypeInterface...`
- `feat(core): ajoute le Container DI...`
- `feat(core): ajoute RequestContext...`
- `feat(core): ajoute HookRegistrar...`
- `feat(core): ajoute ViewRenderer...`
- `feat(core): ajoute AssetManager...`
- `feat(core): ajoute Theme::boot()...`
- `feat: ajoute functions.php...`
- `feat(view): ajoute le layout minimal...`
- `test(integration): vérifie le bootstrap...`
- `ci: ajoute le workflow GitHub Actions...`
- `chore: configure captainhook...`
- `docs: ajoute le guide d'installation...`
- `docs: ajoute le document d'architecture...`
- `docs: ajoute le guide de tests...`
- `docs(adr): ADR 0001...`
- `docs(adr): ADR 0002...`
- `docs: ajoute le CHANGELOG initial...`

- [ ] **Step 5: Tag the foundation milestone**

```bash
git tag -a v1.0.0-alpha.1-foundation -m "Plan 1 (Foundation) livré : socle MVC, Core, CI"
```

Expected: `(no output)` — tag créé.

---

## Definition of Done — Plan 1 (Foundation)

This plan is **done** when ALL of the following are true:

1. ✅ `composer ci` returns 0 (cs + phpstan + all tests PASS)
2. ✅ Test coverage ≥ 80 % on `src/` (will rise as next plans add tests)
3. ✅ Theme can be activated in WordPress 6.9+ without fatal error and displays the "Site en cours de construction." page
4. ✅ All 27 tasks committed
5. ✅ Tag `v1.0.0-alpha.1-foundation` posed
6. ✅ Documentation present : README, architecture, installation, testing, 2 ADRs, CHANGELOG
7. ✅ CI workflow green on GitHub Actions for PHP 8.3 / 8.4 / 8.5 (after first push)

When all 7 boxes are ticked, **Plan 2 (I18n)** can start.

---

## Self-Review (planificateur)

**1. Spec coverage (cycle 1, Plan 1 — Foundation)**

| Spec section | Couvert ? | Tâches |
|--------------|-----------|--------|
| 0.4 Code en anglais, commentaires/commits en français | ✅ | Conventions section + chaque task |
| 1.2 Arborescence (root files, src/, tests/, docs/, theme-bridge/, templates/) | ✅ | T1, T3-T7, T17 |
| 1.3 Bootstrap functions.php | ✅ | T16 |
| 1.4 Pattern de module (ModuleInterface) | ✅ | T8 |
| 1.5 Documentation (PHPDoc, headers, dossier docs/, ADRs) | ✅ | Toutes les tasks (PHPDoc), T21-T25 |
| 5.1 Organisation des tests (bootstrap, helpers, Unit, Integration) | ✅ | T5, T18 |
| 5.2 Stratégie TDD (Red → Green → Refactor) | ✅ | Pattern dans T10-T15 |
| 5.3 PHPStan niveau 8 + WP stubs | ✅ | T6 |
| 5.4 PHP-CS-Fixer (PSR-12, PHP 8.3 migration, final_class) | ✅ | T7 |
| 5.5 Scripts Composer (test, analyse, cs, ci, docs) | ✅ | T3 |
| 5.6 Pre-commit hooks captainhook | ✅ | T20 |
| 5.7 CI GitHub Actions matrice PHP | ✅ | T19 |
| 5.8 Theme::onActivation / onDeactivation | ✅ | T15, T18 |
| 5.9 Couverture (≥ 80 % à ce stade) | ✅ | T27 |
| 5.11 Livrables techniques (Composer, tests verts, CI, docs) | ✅ | T27 |
| 6.1 Dépendances Composer | ✅ | T3 |
| 6.2 ADRs (0001 MVC, 0002 Lunar) | ✅ | T24, T25 |

**2. Placeholder scan**

Aucun "TODO", "TBD", "implement later" laissé sans code. Le seul TODO commenté est dans T13 step 5 ("If PHPStan complains... add a narrow @phpstan-ignore-next-line with TODO referencing this task"), conditionnel à un cas réel — acceptable car la directive précise quoi faire.

**3. Type consistency**

- `Container::set/factory/get/has` — signatures cohérentes T10
- `RequestContext::queryVar/cookie/method/ip/header` — cohérent T11
- `HookRegistrar::action/filter/registered` — cohérent T12
- `ViewRenderer::render/setDefaultVariables/registerMacro` — cohérent T13
- `AssetManager::enqueueFront/enqueueAdmin` — cohérent T14
- `Theme::boot/container/onActivation/onDeactivation/reset` — cohérent T15, T18

**4. Scope**

Plan 1 = Foundation pure. Aucun module fonctionnel (I18n, Posts, SEO...) inclus — bien découpé. Livre un thème activable mais "vide" (juste "Site en cours de construction.") qui valide le pipeline.

---

## Next Step

Plan terminé. Sauvegardé dans `docs/superpowers/plans/2026-05-05-foundation.md`.

Deux options d'exécution :

1. **Subagent-Driven (recommandé)** — un sous-agent par tâche, revue entre chaque tâche, itérations rapides
2. **Inline Execution** — exécution dans cette session avec checkpoints

À demander au commanditaire.
