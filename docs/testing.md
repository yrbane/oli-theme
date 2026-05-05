# Tester le thème oli-theme

## Lancer les tests

```bash
composer test               # tests unitaires uniquement
composer test:integration   # tests d'intégration
composer test:all           # tous les tests
composer test:coverage      # rapport HTML dans coverage/html
```

Pour aller plus vite localement (désactive Xdebug même si installé) :

```bash
XDEBUG_MODE=off composer ci
```

## Stratégie TDD

Chaque classe est précédée de son test. Cycle Red → Green → Refactor → Commit :

1. Écrire un test qui décrit le comportement attendu.
2. Lancer le test, vérifier qu'il **échoue** (Red).
3. Écrire le **minimum** de code pour le faire passer.
4. Lancer à nouveau, vérifier qu'il passe (Green).
5. Refactorer en gardant le test vert.
6. Commit (Conventional Commit en français, **sans** ligne `Co-Authored-By: Claude`).

## Conventions

- Méthodes `test_it_should_*` ou `testCamelCase` selon le module (existence des deux styles tolérée — un test par cas).
- Trois sections par test : Arrange / Act / Assert (pas obligatoirement séparées par commentaires).
- `@dataProvider` pour les cas multiples (validation, parsing).
- Mocks Brain Monkey pour les fonctions WordPress (`get_post`, `wp_mail`, `apply_filters`, ...).
- Pas d'assertions sur des messages traduits — utiliser des clés ou des codes.

## Brain Monkey

Brain Monkey permet de mocker les fonctions WordPress sans charger WordPress.

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
    Functions\when('get_option')->justReturn(['enabled' => ['fr', 'en'], 'default' => 'fr']);

    // Stub avec calcul depuis les arguments
    Functions\when('apply_filters')->returnArg(2);

    // Capture des arguments d'appel
    $captured = [];
    Functions\when('wp_enqueue_style')->alias(static function (...$args) use (&$captured): void {
        $captured[] = $args;
    });

    // Expectation stricte (échoue si non appelée)
    Functions\expect('wp_mail')
        ->once()
        ->with('to@example.com', 'Sujet', \Mockery::pattern('/.*/'))
        ->andReturn(true);

    // Filter / action mocking
    \Brain\Monkey\Filters\expectApplied('the_title')->andReturn('Mocked');
    \Brain\Monkey\Actions\expectAdded('init')->once();
}
```

Quand un test n'appelle aucun `assert*` direct (par exemple : il ne vérifie qu'une `expectAdded` Brain Monkey), ajouter `$this->addToAssertionCount(1);` à la fin pour éviter le warning « risky test » de PHPUnit 11.

## Mocking des classes `final`

PHPUnit 11 **ne peut pas mocker les classes `final`**. Le projet adopte la convention systématique : pour chaque classe `final` consommée par un autre composant via `createMock`, une **interface narrow** est extraite.

```php
// src/I18n/LanguageResolverInterface.php
interface LanguageResolverInterface
{
    public function current(): Language;
    public function resolve(): Language;
}

// src/I18n/LanguageResolver.php
final class LanguageResolver implements LanguageResolverInterface { ... }

// Dans un test :
$resolver = $this->createMock(LanguageResolverInterface::class);
$resolver->method('current')->willReturn($french);
```

Voir [`docs/architecture.md`](architecture.md) pour la liste complète des interfaces extraites.

## Stub de `\wpdb`

`\wpdb` n'est pas chargé en tests unitaires. Pattern recommandé : classe anonyme exposant uniquement les méthodes utilisées.

```php
$this->wpdb = new class () {
    public string $prefix = 'wp_';
    public ?object $rowToReturn = null;

    public function prepare(string $query, mixed ...$args): string { return $query; }
    public function get_row(string $query): ?object { return $this->rowToReturn; }
    public function get_results(string $query): array { return []; }
    public function insert(string $table, array $data, array $formats = []): int { return 1; }
};
```

Quand le code de production type-hint `\wpdb`, deux options :

1. Type-hint `object` côté production (avec `@phpstan-param \wpdb $wpdb`) — accepte le stub.
2. Helper privé dans le test : `private function wpdb(): \wpdb { /** @phpstan-var \wpdb $w */ $w = $this->wpdb; return $w; }` — appelé partout où PHPStan veut le type strict.

L'option 1 est utilisée dans `RedirectModel` et `RedirectInstaller`.

## Couverture de code

Cible globale : **≥ 90 %**. Cibles par domaine dans la spec ([`docs/superpowers/specs/2026-05-05-oli-theme-design.md`](superpowers/specs/2026-05-05-oli-theme-design.md) section 5.9).

Générer le rapport :

```bash
composer test:coverage
xdg-open coverage/html/index.html  # Linux
open coverage/html/index.html      # macOS
```

## CI

GitHub Actions exécute `composer ci` sur PHP 8.3, 8.4, 8.5 à chaque push et PR. Workflow : `.github/workflows/ci.yml`.

Les hooks pre-commit ([captainhook](https://github.com/captainhookphp/captainhook)) reproduisent localement le même cycle (cs:fix dry-run + phpstan + phpunit unit).

## Tests d'intégration

Réservés aux scénarios qui ne peuvent pas être unitaires :

- Activation du thème (`tests/Integration/ActivationTest.php`).
- Rendu end-to-end avec boot complet (`RenderEndToEndTest`, `CarouselFrontPageTest`, `EventResolutionTest`, `SeoE2ETest`).

Restent **rapides** — pas de WordPress complet, juste Brain Monkey avec un set de stubs plus large.

## État courant

À la livraison du Plan 7 + fix #3 : **242 tests** (217 unitaires + 25 intégration), **801 assertions**, pipeline vert sur PHP 8.3, 8.4, 8.5.

## Patterns spécifiques

### Lunar templates dans les tests

Le `ViewRenderer` de Lunar nécessite un dossier de cache. Pattern utilisé :

```php
protected function setUp(): void
{
    $this->cacheDir = sys_get_temp_dir() . '/oli-theme-cache-' . uniqid();
    mkdir($this->cacheDir, recursive: true);
}

protected function tearDown(): void
{
    $this->rrmdir($this->cacheDir);
}

private function rrmdir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $path = $dir . '/' . $file;
        is_dir($path) ? $this->rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}
```

### Reset du singleton `Theme`

`Theme::reset()` (méthode `@internal`) remet `self::$container` à `null`. À appeler en `setUp()` ET `tearDown()` des tests qui boot le thème, pour garantir l'isolation.

### Templates Lunar passent des objets

Depuis `lunar-template` `2de89f0+` (issue #14), la notation pointée `[[ obj.prop ]]` accède aux propriétés d'objet via `Lunar\Template\Runtime\Access::get`. On peut donc passer directement les DTO `final readonly` (`Language`, `PostEntity`, `EventEntity`, `SeoHeadViewModel`, …) sans conversion en arrays.
