# ADR 0010 — Settings API native (vs Customizer / ACF / Carbon Fields)

**Statut :** accepté
**Date :** 2026-05-06
**Contexte :** Plan 9 — Module Settings.

## Décision

Implémenter la page d'options du thème via la **Settings API native de WordPress** (`register_setting`, `add_settings_section`, `add_settings_field`, `do_settings_sections`), avec persistance dans une **option unique** `oli_theme_settings` (array sérialisé).

## Périmètre

- 6 sous-bags immuables : `BannerSettings`, `FooterSettings`, `SocialSettings`, `LanguagesSettings`, `ContactSettings`, `SeoSettings`.
- 1 DTO agrégateur `SettingsBag` avec factory `::default()`.
- 1 model `ThemeSettingsModel` (+ Interface) avec `get/set/all`.
- 1 page admin `ThemeSettingsPage` sous `Apparence > Identité du site` avec onglets.
- 1 module `SettingsModule` orchestrant la DI + les hooks `admin_menu` / `admin_init`.
- Template Lunar `admin/settings-page.html.tpl` (wrapper minimal autour du HTML produit par la Settings API).

## Alternatives rejetées

### Customizer (`add_action('customize_register')`)

- ✅ Preview live, intégré au Customizer.
- ❌ UX figée (panneau latéral étroit, peu adapté à 6 sections riches).
- ❌ Customizer marqué obsolète par WordPress (priorité au Site Editor / FSE).
- ❌ Couplage à `WP_Customize_Manager` peu testable.

### ACF (Advanced Custom Fields) — Options Page

- ✅ UI admin riche (drag-drop, repeater, fichier picker).
- ❌ Plugin tiers (gratuit en partie, ACF Pro pour Repeater) — incompatible avec la direction zéro-dépendance du thème (ADR 0008, 0009).
- ❌ Stockage par champ (multiplie les `options` rows en BDD) — fragile à la migration.

### Carbon Fields

- ✅ Open source, pas de plugin requis (composer).
- ❌ Surface API étendue, équivalent ACF mais sans la maturité.
- ❌ Couplage à leur DSL pour les champs.

### Page admin custom (sans Settings API)

- ✅ Contrôle total sur le HTML.
- ❌ Re-développement du nonce, sanitize, merge — déjà fournis par la Settings API.
- ❌ Plus de code à tester pour un gain marginal.

## Conséquences

### Avantages

- ✅ **Zéro dépendance plugin** — cohérent avec ADR 0006-0009.
- ✅ **Option unique** `oli_theme_settings` — une seule lecture en BDD, atomique.
- ✅ **Hydratation typée** : `SettingsBag::default()` garantit que les controllers en aval reçoivent toujours un objet complet, même sur fresh install.
- ✅ **Settings API native** : nonce + sanitize + redirect post-save gérés par WordPress.
- ✅ **Tabs URL-driven** (`?tab=banner`) — bookmarkable, accessible.
- ✅ **Tests** : DTOs (6 tests) + model (5 tests) + page (3 tests) + module (2 tests) + intégration (1 test) = 17 tests dédiés.

### Inconvénients

- ❌ **Rendu des champs basique** au cycle 1 : les `add_settings_field` produisent du HTML standard (input/select). Les UI riches (uploader média WP, drag-drop des langues, color picker) sont **reportées à un cycle 2**.
- ❌ **Pas de live preview** comme avec le Customizer — l'éditeur doit Save pour voir le résultat. Acceptable pour un site PME.
- ❌ **`do_settings_sections` produit du HTML directement** (pas de retour string) — capturé via `ob_start()` puis injecté dans le template Lunar. Légère friction architecturale acceptée pour bénéficier de l'écosystème WP.

## Liens

- Spec : `docs/superpowers/specs/2026-05-05-oli-theme-design.md` § 4.3.
- Implémentation : `src/Settings/` (~10 classes + 9 tests).
- Doc : `docs/settings.md`.
