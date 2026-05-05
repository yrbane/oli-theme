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
