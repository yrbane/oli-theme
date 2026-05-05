# Système multilingue d'oli-theme

## Principe

Le thème gère le multilingue **sans plugin** :

- Une **taxonomie `language`** étiquette chaque page/post par sa langue.
- Un **groupe de traduction** (UUID stocké en post meta `_oli_translation_group`) lie ensemble les versions linguistiques d'un même contenu.
- Des **rewrite rules** font correspondre `/fr/...`, `/en/...`, `/it/...`, `/es/...` à la langue courante.
- Le filtre `home_url` ajoute automatiquement le préfixe de langue.

## Configuration

Les langues activées sont stockées dans l'option WordPress `oli_languages` :

```php
[
    'enabled' => ['fr', 'en'],
    'default' => 'fr',
]
```

L'UI d'administration arrivera au Plan 5 (Settings > Identité du site).

## Créer une traduction

1. Créer la version FR d'une page.
2. Créer une nouvelle page (version EN).
3. Dans la metabox **Traductions** sur la version EN, coller l'identifiant de groupe affiché sur la version FR.
4. Sauver. Les deux pages sont liées : le switcher de langue les fera correspondre.

À terme (Plan 3), un bouton "Créer la version EN" automatisera ce flux.

## URLs

- Langue par défaut : `https://example.test/` (pas de préfixe).
- Autres langues : `https://example.test/en/`, `https://example.test/it/`...

## Détection de la langue

Ordre de priorité :

1. **Préfixe d'URL** (`/en/`, `/it/`...) capturé par les rewrite rules.
2. **Cookie `oli_lang`** (posé lors d'un changement explicite).
3. **En-tête `Accept-Language`** (négociation de contenu).
4. **Langue par défaut** (`oli_languages.default`).

## Ajouter une langue

Pour le moment (Plan 2), modifier l'option `oli_languages` via une commande WP-CLI :

```bash
wp option update oli_languages '{"enabled":["fr","en","it"],"default":"fr"}' --format=json
```

Puis vider les rewrite rules : `Réglages > Permaliens > Enregistrer`.

L'UI viendra au Plan 5.
