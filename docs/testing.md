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
