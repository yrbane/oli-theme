# Installation du thème oli-theme

## Prérequis

- **PHP** ≥ 8.3 (testé jusqu'à 8.5)
- **WordPress** ≥ 6.9 (testé sur 6.9.4)
- **Composer** ≥ 2.0
- Un hébergeur permettant d'exécuter `composer install` ou un déploiement embarquant `vendor/` pré-installé.

## Installation pas à pas

### 1. Récupérer les sources

Cloner le dépôt dans le dossier `wp-content/themes/` de votre installation WordPress :

```bash
cd wp-content/themes/
git clone https://github.com/yrbane/oli-theme.git
cd oli-theme
```

### 2. Installer les dépendances PHP

Pour la production :

```bash
composer install --no-dev --optimize-autoloader
```

Pour le développement (tests + analyse statique + CS-Fixer) :

```bash
composer install
```

### 3. Activer le thème

Dans l'administration WordPress :

1. Aller dans **Apparence > Thèmes**.
2. Cliquer sur **Activer** sur « Oli Theme ».

À l'activation, le thème :

- **Crée la table `{prefix}oli_redirects`** (via `dbDelta`, hook `after_switch_theme`) — versionnée par l'option WP `oli_theme_db_version`.
- **Vide les rewrite rules** pour réenregistrer les URLs multilingues `/fr/`, `/en/`, …
- **Enregistre les Custom Post Types** : `oli_event`, `oli_slide`.
- **Enregistre la taxonomie `language`** sur `post`, `page`, `oli_event`, `oli_slide`.
- **Enregistre les nav-menu locations** par langue activée : `primary_<code>`, `footer_<code>`.

### 4. Vérifier l'installation

- La page d'accueil affiche soit l'archive des posts, soit la page d'accueil statique configurée — avec le carousel d'accueil (si des slides sont publiés pour la langue courante).
- L'écran d'édition d'un post / d'une page / d'un événement affiche les métaboxes :
  - **Traductions** (lien vers la version dans une autre langue)
  - **SEO** (titre, description, focus keyword, score)
  - Pour les événements : **Détails de l'événement** (dates, lieu, prix, inscription).
- L'admin expose deux pages utilitaires :
  - **Outils > SEO Dashboard** (MVP)
  - **Outils > Redirections** (MVP)

## Configuration recommandée

- Activer les **permalinks** : `Réglages > Permaliens > Nom de l'article`.
- Définir le **fuseau horaire** dans `Réglages > Général`.
- Vérifier que `wp_mail()` est fonctionnel (test du formulaire de contact, livré au Plan 8).
- Configurer les langues activées dans la page Settings (livrée au Plan 9). En attendant, le thème lit l'option `oli_languages` directement (format : `['enabled' => ['fr', 'en'], 'default' => 'fr']`).

## Mise à jour du thème

```bash
cd wp-content/themes/oli-theme
git pull
composer install --no-dev --optimize-autoloader
```

**Bonne nouvelle** : `RedirectInstaller` est idempotent et hooké à `init` priorité 5. Une mise à jour qui modifie le schéma de `oli_redirects` (incrément de `RedirectInstaller::DB_VERSION`) déclenche automatiquement la migration au premier `init` après `git pull`. Aucune action manuelle requise (cf. issue #3).

## Désinstallation

Désactiver le thème dans `Apparence > Thèmes`. Les options et contenus sont conservés (réactivation propre possible).

Pour purger complètement les données (à la WP-CLI) :

```bash
wp option delete oli_theme_settings
wp option delete oli_languages
wp option delete oli_theme_db_version
wp db query "DROP TABLE IF EXISTS {prefix}oli_redirects;"
wp post delete $(wp post list --post_type=oli_event --format=ids) --force
wp post delete $(wp post list --post_type=oli_slide --format=ids) --force
```

## Versions livrées

Voir [`CHANGELOG.md`](../CHANGELOG.md) pour la liste détaillée. Tags Git :

- `v1.0.0-alpha.1-foundation` — Plan 1
- `v1.0.0-alpha.2-i18n` — Plan 2
- `v1.0.0-alpha.3-templates` — Plan 3
- `v1.0.0-alpha.4-navigation` — Plan 4
- `v1.0.0-alpha.5-slides` — Plan 5
- `v1.0.0-alpha.6-events` — Plan 6
- `v1.0.0-alpha.7-seo` — Plan 7

## Problèmes courants

| Symptôme | Cause probable | Solution |
|----------|----------------|----------|
| Page blanche après activation | `vendor/` absent | Lancer `composer install` |
| 404 sur `/fr/...` | Rewrite rules pas vidées | Aller dans `Réglages > Permaliens > Enregistrer` |
| `Class \OliTheme\Theme not found` | PSR-4 mal configuré | Vérifier `composer dump-autoload` |
| Notice `Table 'wp_oli_redirects' doesn't exist` | Plan 7 déployé sans réactivation | Visiter une page front une fois — `init` priorité 5 crée la table automatiquement (issue #3 fixée) |
| `composer docs` retourne un message désactivation | `phpdocumentor` incompatible PHP 8.5 (dépendance `json-path 0.2.1`) | Réintroduit dès que l'amont supportera PHP 8.5 |
