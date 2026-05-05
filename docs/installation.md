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
