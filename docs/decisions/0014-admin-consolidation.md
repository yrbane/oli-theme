# ADR 0014 — Consolidation des pages d'administration du thème

**Statut :** Accepté — Cycle 2

## Contexte

Le thème exposait **6 pages d'administration distinctes**, réparties dans deux
menus WordPress :

- **Apparence** : Identité du site (6 onglets internes), Réseaux sociaux,
  Galerie, Variations CSS.
- **Outils** : SEO Dashboard, Redirections.

Cette dispersion créait de la confusion et un **doublon** : l'onglet « Réseaux
sociaux » de la page « Identité du site » (option `oli_theme_settings[social]`,
5 réseaux, sans icônes) était du **code mort** — jamais lu au front — tandis que
la page « Réseaux sociaux » dédiée (`SocialAdminPage`, option `oli_social_links`,
10 plateformes + icônes SVG) était la seule réellement affichée par le footer.

## Décision

Rassembler les 6 pages sur **une seule page hôte** à onglets, accessible via
`themes.php?page=oli-theme-settings`, groupée par thème.

### Architecture : page hôte + registre d'onglets

1. **`Admin\AdminTabInterface`** : contrat d'un sous-onglet
   (`id`, `group`, `label`, `capability`, `renderPanel`).
2. **`Admin\AdminGroups`** : définition figée des 5 groupes de premier niveau
   (`identite`, `apparence`, `contenu`, `contact`, `seo`).
3. **`Admin\AdminTabRegistry`** : registre partagé (singleton dans le conteneur)
   où chaque module publie ses onglets sur `admin_menu` (priorité 10).
4. **`Admin\ThemeAdminPage`** : page hôte unique (enregistrée sur `admin_menu`
   priorité 20). Lit `?tab` (groupe) et `?sub` (sous-onglet), construit la
   navigation depuis le registre, vérifie la capability et délègue le rendu au
   `renderPanel()` de l'onglet actif.
5. **`Admin\AdminModule`** : câble le conteneur, le menu unique et la
   compatibilité des anciens slugs.

Chaque module existant (Settings, Social, Galerie, Variations, SEO, Redirections)
implémente `AdminTabInterface` (ou l'adaptateur `Settings\SettingsTab`) et déplace
son rendu dans `renderPanel()` — **sans wrapper `.wrap` ni `<h1>`** (fournis par
la page hôte). Les **handlers de sauvegarde restent inchangés** (`admin_post_*`,
Settings API `register_setting`) : seul le menu, la navigation et les URLs de
redirection changent.

### Groupes et onglets

| Groupe (`tab`) | Sous-onglets (`sub`) |
|----------------|----------------------|
| `identite` | banner *(défaut)*, languages, social, footer |
| `apparence` | variations |
| `contenu` | galerie |
| `contact` | contact |
| `seo` | reglages, dashboard, redirections |

### Compatibilité des anciens slugs

`Admin\LegacySlugRedirector` mappe chaque ancien slug vers son nouvel onglet, et
`AdminModule` redirige en **301** via le hook **`admin_page_access_denied`**
(et non `admin_init`). En effet, WordPress lève le 403 « page non autorisée »
dans `wp-admin/includes/menu.php` **avant** `admin_init` ; `admin_page_access_denied`
est le hook déclenché juste avant ce `wp_die`, seul point d'interception fiable.

## Conséquences

**Positives :**
- Un seul point d'entrée pour toute la configuration du thème.
- Suppression du doublon « réseaux sociaux » mort.
- Découplage propre : chaque module garde sa responsabilité (SRP) et publie son
  onglet via une interface — ajouter un onglet ne touche pas la page hôte.
- Anciennes URLs/bookmarks préservés via redirection 301.
- Logique de sauvegarde testée réutilisée sans modification.

**Négatives / compromis :**
- Le hook `admin_page_access_denied` est un point d'interception un peu
  inhabituel ; documenté en commentaire pour éviter une régression.
- Deux niveaux de navigation (groupe + sous-onglet) pour les groupes riches
  (`identite`, `seo`) — léger surcoût visuel, acceptable.

## Sécurité

- `current_user_can($tab->capability())` vérifié avant tout rendu de panneau.
- `sanitize_key()` sur `?tab`/`?sub`/`page`.
- Redirections via `wp_safe_redirect()` (liste blanche d'hôtes), cibles
  construites depuis une table figée — pas d'open redirect.
- Nonces et capabilities des formulaires de sauvegarde inchangés.

## Alternatives écartées

- **Laisser SEO Dashboard / Redirections sous Outils** : l'utilisateur voulait
  *tout* sur une page unique ; le groupe `seo` les regroupe avec les réglages SEO.
- **Tout fusionner dans une méga-classe** : violerait le SRP, fichier énorme,
  perte de la valeur des tests unitaires existants.
- **Hook `admin_init` pour les redirections** : trop tardif (le 403 est levé avant).
