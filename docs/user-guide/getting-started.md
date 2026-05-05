# Premiers pas avec oli-theme — 10 minutes

Ce guide vous accompagne pas à pas pour publier votre première page multilingue, ajouter un événement et configurer le SEO de base.

## Prérequis

- WordPress 6.9+ avec le thème `oli-theme` activé.
- Compte administrateur (`manage_options`).

## 1. Vérifier l'identité du site (1 min)

Aller dans **Apparence > Identité du site** :

- Onglet **Identité visuelle** : uploader un logo (à venir cycle 2 — pour l'instant via WP-CLI ou option directe).
- Onglet **Langues** : par défaut, `fr` est activé. Pour ajouter `en`, modifier l'option `oli_theme_settings` (via WP-CLI `wp option update`) ou attendre l'UI complète du cycle 2.
- Onglet **Réseaux sociaux** : renseigner les URLs Facebook / Instagram / etc.

## 2. Créer une page « Accueil » (2 min)

**Pages > Ajouter** :

- Titre : `Accueil`.
- Contenu : un mot d'accueil court.
- Dans la métabox **Traductions** (à droite), choisir la langue : `Français`.
- Publier.

Aller dans **Réglages > Lecture** et choisir « Une page statique > Page d'accueil = Accueil ».

## 3. Créer la version anglaise (1 min)

Sur l'écran d'édition de la page « Accueil » :

- Métabox **Traductions** : cliquer sur « Créer la version EN ».
- L'éditeur ouvre une nouvelle page liée à la même `_oli_translation_group`.
- Saisir le titre `Home` et le contenu en anglais.
- Publier.

L'URL `https://votresite.com/en/home/` rend maintenant la version anglaise. Le switcher de langue dans le header bascule entre les deux.

## 4. Configurer le menu principal (2 min)

**Apparence > Menus** :

- Créer un menu nommé `Menu principal FR`.
- Ajouter les pages voulues (Accueil, À propos, Contact…).
- Cocher la location **Menu principal (Français)** dans « Réglages du menu ».
- Sauvegarder.

Recommencer pour `Menu principal EN` avec la location **Menu principal (English)**.

## 5. Publier un événement (2 min)

**Événements > Ajouter** :

- Titre : `Atelier Yoga`.
- Contenu : description.
- Image à la une : optionnelle.
- Métabox **Traductions** : choisir la langue.
- Métabox **Détails de l'événement** :
  - Date de début : `2026-06-15 18:00`
  - Lieu : `Studio Olikalari`
  - URL d'inscription : `https://billetterie.example/atelier-yoga`
  - Prix : `Gratuit`
- Publier.

L'événement apparait sur `https://votresite.com/fr/evenements/`.

## 6. Renseigner le SEO de la page d'accueil (1 min)

Sur l'écran d'édition de la page « Accueil » :

- Métabox **SEO** :
  - Titre SEO : `Olikalari — Yoga, Kalari, Bien-être à Bordeaux`
  - Méta description : `Découvrez nos ateliers, événements et stages tout au long de l'année.`
  - Mot-clé focus : `yoga`
  - Image OG : (ID d'un média uploadé)
- Sauvegarder.

L'aperçu Google SERP en bas de la métabox affiche le rendu attendu en direct.

## 7. Ajouter le formulaire de contact (1 min)

Créer une page **Contact** :

- Titre : `Contact`.
- Contenu :
  ```
  [oli_contact_form]
  ```
- Publier.

Configurer l'email destinataire :

```bash
wp option update oli_contact_email "contact@olikalari.com"
```

L'auto-réponse et le logging sont désactivés par défaut. Pour les activer :

```bash
wp option update oli_contact_autoreply 1
wp option update oli_contact_logging 1
```

## C'est fait !

Votre site est :

- ✅ **Multilingue** (FR/EN) avec switcher dans le header
- ✅ **Avec menus** par langue
- ✅ **Avec un événement** publié
- ✅ **Avec un SEO** complet (`<title>`, OG, JSON-LD `@graph`, hreflang)
- ✅ **Avec un formulaire de contact** sécurisé

## Et après ?

- [Ajouter un slide d'accueil](../slides.md) (image + lien CTA)
- [Configurer la page d'options en détail](../settings.md)
- [Comprendre le score SEO et les redirections](../seo.md)
- [Gérer les langues et les fallbacks](../multilingue.md)
