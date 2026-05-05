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

### Notes

- `phpdocumentor/phpdocumentor` retiré temporairement de `require-dev` (incompatible PHP 8.5 via sa dépendance `phpdocumentor/json-path 0.2.1`). À réintroduire quand l'amont supportera PHP 8.5. Le script `composer docs` est désactivé en attendant.
- `yrbane/lunar-template` installé via dépôt VCS GitHub (non publié sur Packagist) en `dev-main` faute de tag stable.
