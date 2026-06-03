# Auto-documentation in-admin — Plan d'implémentation

> **Pour les workers agentiques :** SOUS-COMPÉTENCE REQUISE — `superpowers:subagent-driven-development`.

**Goal :** Donner à Olivier toutes les informations sur l'utilisation du thème **directement dans l'admin WordPress**, sans dépendance à de la documentation externe.

**Architecture :** Un nouvel onglet `Aide` dans `/wp-admin/themes.php?page=oli-theme-settings&tab=aide` regroupe des guides Markdown versionnés Git (`docs/admin/*.md`) rendus en HTML par un convertisseur minimal interne. Chaque champ des onglets de réglages reçoit une bulle d'aide « ? » qui pointe vers le guide pertinent (lien `?tab=aide&guide=<id>#<anchor>`).

**Tech stack :** PHP 8.3, WordPress hooks/Settings API, Lunar template engine, PHPUnit 11 + Brain Monkey, PHPStan level 8.

---

## File Structure

- **Créer**
  - `src/Help/HelpGuide.php` — value object immuable (id, title, file, related_settings).
  - `src/Help/HelpRegistry.php` — registre des guides disponibles.
  - `src/Help/MarkdownRenderer.php` — convertisseur minimal MD → HTML (headings, lists, paragraphs, code, links, bold/italic).
  - `src/Help/HelpAdminPage.php` — `AdminTabInterface`, rend l'index ou un guide.
  - `src/Help/HelpModule.php` — `ModuleInterface`, enregistre la page.
  - `templates/admin/help-index.html.tpl` — liste des guides.
  - `templates/admin/help-guide.html.tpl` — un guide rendu.
  - `docs/admin/index.md`, `identite.md`, `banniere.md`, `slides.md`, `typo.md`, `galerie.md`, `menu.md`, `traductions.md`, `footer.md`, `apparence.md`, `seo.md`, `redirections.md`, `social.md`, `contenu.md` — contenus.
  - `tests/Unit/Help/MarkdownRendererTest.php`, `HelpRegistryTest.php`, `HelpAdminPageTest.php`, `HelpModuleTest.php`.
  - `assets/css/admin-help.css` — style de la zone d'aide + bulle « ? ».
- **Modifier**
  - `src/Theme.php` — enregistrer `HelpModule`.
  - `src/Settings/SettingsTab.php` — accepter un paramètre `help_guide` par champ et rendre la bulle « ? ».
  - `CHANGELOG.md` — entrée dédiée.
  - `docs/settings.md` — pointer vers l'aide in-admin.

---

## Task 1 — MarkdownRenderer minimaliste (TDD)

**Files :**
- Créer : `src/Help/MarkdownRenderer.php`
- Tester : `tests/Unit/Help/MarkdownRendererTest.php`

- [ ] **Step 1 :** écrire les tests couvrant : titres `#`, `##`, `###`, paragraphes, listes `- item`, listes `1. item`, gras `**x**`, italique `_x_`, code inline `` `x` ``, blocs de code ` ```...``` `, liens `[t](u)`. Inclure un cas avec caractères spéciaux à échapper.
- [ ] **Step 2 :** lancer `phpunit tests/Unit/Help/MarkdownRendererTest.php` → tous les tests doivent échouer (classe inexistante).
- [ ] **Step 3 :** implémenter `MarkdownRenderer::render(string $md): string` minimal, sans dépendance, échappement via `htmlspecialchars` avant injection des balises.
- [ ] **Step 4 :** lancer les tests → tous passent.
- [ ] **Step 5 :** commit `feat(help): MarkdownRenderer minimal pour l'aide in-admin`.

---

## Task 2 — HelpGuide + HelpRegistry (TDD)

**Files :**
- Créer : `src/Help/HelpGuide.php`, `src/Help/HelpRegistry.php`
- Tester : `tests/Unit/Help/HelpRegistryTest.php`

- [ ] **Step 1 :** tests : `HelpRegistry::all()` retourne la liste, `byId('banniere')` retourne le `HelpGuide` correspondant, `byId('inconnu')` retourne `null`.
- [ ] **Step 2 :** lancer → fail.
- [ ] **Step 3 :** implémenter `HelpGuide` (readonly props : `id`, `title`, `summary`, `file`) + `HelpRegistry` qui hardcode la liste (id → titre → fichier MD relatif au thème).
- [ ] **Step 4 :** tests passent.
- [ ] **Step 5 :** commit `feat(help): HelpGuide + HelpRegistry`.

---

## Task 3 — Contenu Markdown des guides

**Files :** créer `docs/admin/*.md` (14 fichiers listés ci-dessus).

- [ ] **Step 1 :** rédiger chaque guide en français, avec un titre `#`, un résumé, des sections `##`, listes pratiques, captures d'écran textuelles ou pas à pas. Couvrir :
  - `banniere.md` : dimensions, ratio, comportement responsive, où l'uploader.
  - `slides.md` : créer une slide, choisir l'image mise en avant, fallback Picsum.
  - `typo.md` : où régler tailles titres/textes (renvoyer vers l'issue #11).
  - `galerie.md` : ajouter photos, légende, vue actuelle (renvoyer vers l'issue #12 pour le redesign à venir).
  - `menu.md` : créer/éditer le menu, longueur des titres (renvoyer vers #10).
  - `traductions.md` : `_oli_translation_group`, audit des traductions, créer brouillons.
  - `footer.md` : réseaux sociaux + futur logo/texte (renvoyer vers #13).
  - `identite.md`, `apparence.md`, `seo.md`, `redirections.md`, `social.md`, `contenu.md` : panoramas pratiques.
  - `index.md` : page d'accueil de l'aide listant les guides.
- [ ] **Step 2 :** commit `docs(admin): contenu des guides in-admin (Markdown)`.

---

## Task 4 — HelpAdminPage + templates (TDD)

**Files :**
- Créer : `src/Help/HelpAdminPage.php`, `templates/admin/help-index.html.tpl`, `templates/admin/help-guide.html.tpl`
- Tester : `tests/Unit/Help/HelpAdminPageTest.php`

- [ ] **Step 1 :** tests : `id()` = `'aide'`, `group()` = `AdminGroups::OUTILS` (ou propre groupe), `title()` = `'Aide'`, `render()` produit le HTML attendu pour l'index (sans `?guide=`) et pour un guide donné (avec `?guide=banniere`).
- [ ] **Step 2 :** lancer → fail.
- [ ] **Step 3 :** implémenter `HelpAdminPage` qui implémente `AdminTabInterface`, lit `$_GET['guide']`, récupère le `HelpGuide` via `HelpRegistry`, charge le `.md` via `file_get_contents`, le rend via `MarkdownRenderer`, encapsule dans le template Lunar.
- [ ] **Step 4 :** créer les templates Lunar (`[[ guides ]]`, `[[ guide_html|raw ]]`, fil d'Ariane « Retour à l'index »).
- [ ] **Step 5 :** tests passent.
- [ ] **Step 6 :** commit `feat(help): page admin Aide + templates`.

---

## Task 5 — HelpModule + wiring Theme.php (TDD)

**Files :**
- Créer : `src/Help/HelpModule.php`
- Modifier : `src/Theme.php`
- Tester : `tests/Unit/Help/HelpModuleTest.php`

- [ ] **Step 1 :** tests : `register()` ajoute `HelpAdminPage` au registre via `AdminTabRegistry`. Vérifier via Brain Monkey que l'action `init` (ou équivalent) reçoit la closure attendue.
- [ ] **Step 2 :** lancer → fail.
- [ ] **Step 3 :** implémenter `HelpModule` (factories, register dans le registre).
- [ ] **Step 4 :** ajouter `(new \OliTheme\Help\HelpModule($container))->register();` dans `Theme.php`.
- [ ] **Step 5 :** tests passent. Run `composer test` complet.
- [ ] **Step 6 :** commit `feat(help): HelpModule + enregistrement Theme`.

---

## Task 6 — Bulle d'aide contextuelle sur les champs de réglages

**Files :**
- Modifier : `src/Settings/SettingsTab.php`
- Créer : `assets/css/admin-help.css`
- Modifier : `src/Core/AssetManager.php` (enqueue admin CSS sur la page de réglages).

- [ ] **Step 1 :** étendre la signature de l'enregistrement des champs pour accepter `help_guide` (string) et `help_anchor` (string optionnel).
- [ ] **Step 2 :** quand `help_guide` est fourni, append `<a class="oli-help-bubble" href="?page=oli-theme-settings&tab=aide&guide={id}#{anchor}" title="Aide" aria-label="Voir l'aide">?</a>` après le label/description.
- [ ] **Step 3 :** assigner `help_guide` aux champs clés : bannière → `banniere`, slogan → `identite`, médias logo → `identite`, langues → `traductions`, etc.
- [ ] **Step 4 :** styler `.oli-help-bubble` (pastille ronde, fond couleur thème, hover).
- [ ] **Step 5 :** QA visuelle dans `/wp-admin/themes.php?page=oli-theme-settings`.
- [ ] **Step 6 :** commit `feat(help): bulle d'aide contextuelle sur les champs`.

---

## Task 7 — CHANGELOG + push

- [ ] **Step 1 :** ajouter une entrée dans `CHANGELOG.md` (« Auto-documentation in-admin »).
- [ ] **Step 2 :** mettre à jour `docs/settings.md` pour renvoyer vers l'aide in-admin.
- [ ] **Step 3 :** commit `docs: CHANGELOG auto-documentation admin`.
- [ ] **Step 4 :** `git push origin main`.

---

## Self-Review

- ✅ Spec coverage : les 8 issues ouvertes (#6–#13) sont toutes mentionnées dans au moins un guide Markdown.
- ✅ Placeholders : aucun.
- ✅ Types cohérents : `HelpGuide` immuable, `HelpRegistry` retourne `?HelpGuide`, `MarkdownRenderer::render(string): string`.

## Exécution

Mode **Subagent-Driven Development** (séquentiel, review entre tâches).
