# Consolidation des pages d'administration du thème — Design

**Date :** 2026-05-19
**Statut :** Validé (brainstorming)
**Objectif :** Rassembler les 6 pages de configuration dispersées du thème oli-theme
sur une page unique à onglets, accessible via
`/wp-admin/themes.php?page=oli-theme-settings`, et éliminer les doublons.

---

## 1. Contexte et problème

Le thème expose aujourd'hui **6 pages d'admin distinctes**, réparties dans deux
menus WordPress :

| Page | Classe | Slug actuel | Menu WP | Sous-onglets |
|------|--------|-------------|---------|--------------|
| Identité du site | `Settings\ThemeSettingsPage` | `oli-theme-settings` | Apparence | 6 (banner, languages, social, footer, contact, seo) |
| Réseaux sociaux | `Social\SocialAdminPage` | `oli-social-links` | Apparence | — |
| Galerie | `Gallery\GalleryAdminPage` | `oli-gallery` | Apparence | — |
| Variations CSS | `Appearance\ThemeVariationPage` | `oli-theme-variations` | Apparence | — |
| SEO Dashboard | `Seo\Admin\SeoOverviewPage` | `oli-seo-dashboard` | Outils | filtres |
| Redirections | `Seo\Admin\RedirectsPage` | `oli-seo-redirects` | Outils | — |

**Doublon identifié :** l'onglet « Réseaux sociaux » (`social`) de `ThemeSettingsPage`
gère 5 réseaux (facebook, instagram, youtube, linkedin, twitter) dans l'option
`oli_theme_settings[social]`, **sans icônes**. Cette donnée n'est lue **nulle part
au front** (vérifié : aucune lecture de `oli_theme_settings[social]` dans `src/` ou
`templates/`). La page séparée `SocialAdminPage` (option `oli_social_links`, 10
plateformes + icônes SVG) est la seule source réellement affichée au footer, via la
macro `socialIcons` (`Theme.php:377`). L'onglet `social` de `ThemeSettingsPage` est
donc **du code mort** à supprimer.

**Complémentaires (PAS un doublon) :** l'onglet « SEO global » (`seo`) de
`ThemeSettingsPage` gère des réglages (twitter handle, etc.) ; `SeoOverviewPage` est
un tableau de bord de scores avec export CSV. Les deux sont conservés.

---

## 2. Architecture : page hôte + délégation

On ne réécrit **pas** la logique de sauvegarde de chaque module. On change seulement
*où* leur contenu s'affiche.

### Composants

- **`Admin\ThemeAdminPage`** (nouvelle classe hôte) : enregistre **un seul**
  `add_theme_page()` avec le slug `oli-theme-settings`. Lit `?tab=` (groupe) et
  `?sub=` (sous-onglet) depuis la query string, vérifie la capability, et délègue
  le rendu au module concerné.

- **`Admin\AdminTabInterface`** (nouveau contrat) :
  ```php
  interface AdminTabInterface
  {
      public function id(): string;          // slug du sous-onglet (ex. 'galerie')
      public function group(): string;       // onglet principal (ex. 'contenu')
      public function label(): string;       // libellé du sous-onglet
      public function capability(): string;  // 'manage_options'
      public function render(): void;        // imprime le HTML du panneau
  }
  ```

- **Modules existants** : chacun implémente `AdminTabInterface` (ou expose une
  méthode `renderTab()` appelée par un adaptateur). Le HTML actuellement produit par
  leur `render()` est déplacé tel quel dans `render()` de l'onglet. **Aucune
  modification du HTML des formulaires.**

- **Handlers de sauvegarde inchangés** : les hooks `admin_post_*` (Social, Galerie,
  Redirections) et `register_setting` (Identité via Settings API) sont enregistrés
  indépendamment du menu admin. Ils continuent de fonctionner sans modification, à
  l'exception des **URLs de redirection post-save** qui pointent désormais vers
  `oli-theme-settings&tab=…&sub=…`.

### Pourquoi cette approche

- Respecte le SRP : chaque module conserve sa responsabilité.
- Réutilise tout le code déjà testé (handlers, sanitize, repositories).
- Le changement est concentré dans : l'enregistrement du menu (1 page au lieu de 6),
  une couche de navigation, et les URLs de redirection.

### Alternative écartée

Fusionner toute la logique dans une seule méga-classe : violerait le SRP, produirait
un fichier énorme, et ferait perdre la valeur des tests unitaires existants.

---

## 3. Structure des onglets (groupés par thème)

```
┌─ Identité & Marque ─┬─ Apparence ─┬─ Contenu ─┬─ Contact ─┬─ SEO ──────────┐
│ • Identité visuelle │ • Variations│ • Galerie │ • Contact │ • Réglages SEO │
│ • Langues           │   CSS       │           │           │ • Dashboard    │
│ • Réseaux sociaux   │             │           │           │ • Redirections │
│ • Pied de page      │             │           │           │                │
└─────────────────────┴─────────────┴───────────┴───────────┴────────────────┘
```

| `tab` (groupe) | `sub` (sous-onglet) | Source du rendu |
|----------------|---------------------|-----------------|
| `identite` | `banner` (défaut) | `ThemeSettingsPage` onglet banner |
| `identite` | `languages` | `ThemeSettingsPage` onglet languages |
| `identite` | `social` | `SocialAdminPage` (10 plateformes) |
| `identite` | `footer` | `ThemeSettingsPage` onglet footer |
| `apparence` | `variations` | `ThemeVariationPage` |
| `contenu` | `galerie` | `GalleryAdminPage` |
| `contact` | `contact` | `ThemeSettingsPage` onglet contact |
| `seo` | `reglages` | `ThemeSettingsPage` onglet seo |
| `seo` | `dashboard` | `SeoOverviewPage` |
| `seo` | `redirections` | `RedirectsPage` |

**Navigation :** barre `nav-tab-wrapper` WP standard pour les 5 onglets principaux,
puis une seconde barre de sous-onglets en dessous quand le groupe en contient
plusieurs (`identite`, `seo`). Onglet par défaut : `identite` / `banner`.

---

## 4. Menu WP, URLs et compatibilité

### Menu admin

- **Un seul** item sous **Apparence** : « Réglages du thème » →
  `themes.php?page=oli-theme-settings`.
- **Retrait** des 5 entrées de menu devenues redondantes :
  - sous Apparence : Réseaux sociaux, Galerie, Variations CSS ;
  - sous Outils : SEO Dashboard, Redirections.

### Compatibilité des anciennes URLs

Sur `admin_init`, si `$_GET['page']` correspond à un ancien slug, on effectue un
`wp_safe_redirect()` (301) vers le nouvel onglet équivalent :

| Ancien slug | Cible |
|-------------|-------|
| `oli-social-links` | `oli-theme-settings&tab=identite&sub=social` |
| `oli-gallery` | `oli-theme-settings&tab=contenu&sub=galerie` |
| `oli-theme-variations` | `oli-theme-settings&tab=apparence&sub=variations` |
| `oli-seo-dashboard` | `oli-theme-settings&tab=seo&sub=dashboard` |
| `oli-seo-redirects` | `oli-theme-settings&tab=seo&sub=redirections` |

Les paramètres conservés (ex. `edit`, `paged` de Redirections) sont propagés. Les
liens internes (boutons, redirections post-save) sont mis à jour vers les nouveaux
paramètres.

---

## 5. Stratégie de test (TDD)

- **`ThemeAdminPage`** :
  - enregistre exactement un `add_theme_page` avec le slug `oli-theme-settings` ;
  - résout `?tab`/`?sub` vers le bon module ;
  - applique le fallback `identite`/`banner` en l'absence de paramètres ou pour une
    valeur inconnue ;
  - vérifie `current_user_can(capability())` avant de déléguer.
- **Redirections de compat** : chaque ancien slug redirige vers la bonne cible, en
  propageant les paramètres additionnels.
- **Non-régression** : les tests existants des modules (save handlers, sanitize,
  repositories) **restent verts sans modification** — c'est le filet de sécurité de
  l'approche par délégation.

---

## 6. Hors périmètre (YAGNI)

- Pas de refonte visuelle des formulaires eux-mêmes.
- Pas de changement des options stockées, **sauf** suppression de l'option morte
  `oli_theme_settings[social]` (et de son code de sanitize/rendu associé).
- Pas de système de permissions par onglet au-delà de `manage_options`.
- Pas de modification des CPT, du front, ou du moteur de rendu Lunar.
