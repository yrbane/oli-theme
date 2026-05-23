# Consolidation des pages d'administration — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rassembler les 6 pages d'admin du thème sur une page unique à onglets (`themes.php?page=oli-theme-settings`), groupées par thème, en éliminant le doublon « réseaux sociaux » mort.

**Architecture:** Page hôte `ThemeAdminPage` + registre d'onglets `AdminTabRegistry`. Chaque module publie ses onglets (contrat `AdminTabInterface`) dans le registre au lieu d'enregistrer sa propre page. La logique de sauvegarde (`admin_post_*`, `register_setting`) reste inchangée ; seuls le menu, la navigation et les URLs de redirection changent.

**Tech Stack:** PHP 8.3, WordPress 7.0, PHPUnit 11 + Brain Monkey, PHPStan niveau 8, conteneur DI maison.

Référence design : `docs/superpowers/specs/2026-05-19-admin-consolidation-design.md`

---

## File Structure

**Créés :**
- `src/Admin/AdminTabInterface.php` — contrat d'un sous-onglet (id, group, label, capability, renderPanel)
- `src/Admin/AdminTabRegistry.php` — collecte et ordonne les onglets par groupe
- `src/Admin/ThemeAdminPage.php` — page hôte : 1 menu, navigation groupes/sous-onglets, délégation
- `src/Admin/AdminGroups.php` — définition figée des 5 groupes principaux (ordre + libellés)
- `src/Admin/AdminModule.php` — DI + hooks `admin_menu` (1 page) et `admin_init` (redirections compat)
- `tests/Unit/Admin/AdminTabRegistryTest.php`
- `tests/Unit/Admin/ThemeAdminPageTest.php`
- `tests/Unit/Admin/AdminCompatRedirectTest.php`

**Modifiés :**
- `src/Settings/ThemeSettingsPage.php` — extraire `renderPanel(tab)`, exposer 5 onglets, supprimer code mort social
- `src/Settings/SettingsModule.php` — ne plus enregistrer de page, publier les onglets au registre
- `src/Social/SocialAdminPage.php` + `SocialModule.php` — implémenter `AdminTabInterface`, publier onglet
- `src/Gallery/GalleryAdminPage.php` + `GalleryModule.php` — idem
- `src/Appearance/ThemeVariationPage.php` + `AppearanceModule.php` — idem
- `src/Seo/Admin/SeoOverviewPage.php` + `src/Seo/Admin/RedirectsPage.php` + `SeoModule.php` — idem (groupe seo)
- `src/Theme.php:308` — enregistrer `AdminModule` en premier des modules admin

---

## Conventions de groupes/onglets

```
tab=identite  : sub=banner (défaut), languages, social, footer
tab=apparence : sub=variations
tab=contenu   : sub=galerie
tab=contact   : sub=contact
tab=seo       : sub=reglages, dashboard, redirections
```

Onglet par défaut global : `identite` / `banner`.

---

## Task 1: Contrat AdminTabInterface + AdminGroups

**Files:**
- Create: `src/Admin/AdminTabInterface.php`
- Create: `src/Admin/AdminGroups.php`

- [ ] **Step 1: Créer l'interface**

`src/Admin/AdminTabInterface.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Admin;

/**
 * Contrat d'un sous-onglet de la page de réglages unifiée du thème.
 *
 * Chaque module expose un ou plusieurs onglets ; la page hôte les collecte
 * via {@see AdminTabRegistry} et délègue le rendu du panneau actif.
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
interface AdminTabInterface
{
    /** Identifiant du sous-onglet (slug `sub`), ex. 'galerie'. */
    public function id(): string;

    /** Identifiant du groupe parent (slug `tab`), ex. 'contenu'. */
    public function group(): string;

    /** Libellé affiché dans la barre de sous-onglets. */
    public function label(): string;

    /** Capability WP requise pour voir/rendre l'onglet. */
    public function capability(): string;

    /** Imprime le contenu du panneau (sans le wrapper `.wrap` ni le `<h1>`). */
    public function renderPanel(): void;
}
```

- [ ] **Step 2: Créer la définition figée des groupes**

`src/Admin/AdminGroups.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Admin;

/**
 * Définition figée des 5 groupes principaux (onglets de premier niveau).
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
final class AdminGroups
{
    public const DEFAULT_GROUP = 'identite';

    /**
     * Groupes ordonnés : id => libellé.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'identite'  => __('Identité & Marque', 'oli-theme'),
            'apparence' => __('Apparence', 'oli-theme'),
            'contenu'   => __('Contenu', 'oli-theme'),
            'contact'   => __('Contact', 'oli-theme'),
            'seo'       => __('SEO', 'oli-theme'),
        ];
    }

    /** Vrai si l'id de groupe existe. */
    public static function exists(string $group): bool
    {
        return \array_key_exists($group, self::all());
    }
}
```

- [ ] **Step 3: Vérifier le lint**

Run: `./vendor/bin/php-cs-fixer fix src/Admin --dry-run --diff`
Expected: aucune correction requise (ou applique `--diff` puis sans `--dry-run`).

- [ ] **Step 4: Commit**

```bash
git add src/Admin/AdminTabInterface.php src/Admin/AdminGroups.php
git commit -m "feat(admin): contrat AdminTabInterface et définition des groupes"
```

---

## Task 2: AdminTabRegistry

**Files:**
- Create: `src/Admin/AdminTabRegistry.php`
- Test: `tests/Unit/Admin/AdminTabRegistryTest.php`

- [ ] **Step 1: Écrire le test qui échoue**

`tests/Unit/Admin/AdminTabRegistryTest.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Admin\AdminTabInterface;
use OliTheme\Admin\AdminTabRegistry;
use PHPUnit\Framework\TestCase;

final class AdminTabRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg(1);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    private function tab(string $id, string $group, string $label): AdminTabInterface
    {
        return new class ($id, $group, $label) implements AdminTabInterface {
            public function __construct(
                private string $id,
                private string $group,
                private string $label,
            ) {
            }
            public function id(): string { return $this->id; }
            public function group(): string { return $this->group; }
            public function label(): string { return $this->label; }
            public function capability(): string { return 'manage_options'; }
            public function renderPanel(): void { echo $this->id; }
        };
    }

    public function testAddAndRetrieveByGroup(): void
    {
        $registry = new AdminTabRegistry();
        $registry->add($this->tab('galerie', 'contenu', 'Galerie'));
        $registry->add($this->tab('banner', 'identite', 'Identité visuelle'));

        $contenu = $registry->forGroup('contenu');
        self::assertCount(1, $contenu);
        self::assertSame('galerie', $contenu[0]->id());
    }

    public function testFindReturnsExactTab(): void
    {
        $registry = new AdminTabRegistry();
        $registry->add($this->tab('dashboard', 'seo', 'Dashboard'));

        self::assertSame('dashboard', $registry->find('seo', 'dashboard')?->id());
        self::assertNull($registry->find('seo', 'inconnu'));
    }

    public function testFirstOfGroupReturnsInsertionOrder(): void
    {
        $registry = new AdminTabRegistry();
        $registry->add($this->tab('reglages', 'seo', 'Réglages SEO'));
        $registry->add($this->tab('dashboard', 'seo', 'Dashboard'));

        self::assertSame('reglages', $registry->firstOfGroup('seo')?->id());
    }

    public function testGroupsWithTabsKeepsAdminGroupsOrder(): void
    {
        $registry = new AdminTabRegistry();
        $registry->add($this->tab('galerie', 'contenu', 'Galerie'));
        $registry->add($this->tab('banner', 'identite', 'Identité visuelle'));

        // 'identite' précède 'contenu' dans AdminGroups::all().
        self::assertSame(['identite', 'contenu'], array_keys($registry->groupsWithTabs()));
    }
}
```

- [ ] **Step 2: Lancer le test → échoue**

Run: `./vendor/bin/phpunit tests/Unit/Admin/AdminTabRegistryTest.php`
Expected: FAIL — `Class "OliTheme\Admin\AdminTabRegistry" not found`.

- [ ] **Step 3: Implémenter le registre**

`src/Admin/AdminTabRegistry.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Admin;

/**
 * Registre des onglets de la page de réglages unifiée.
 *
 * Les modules y publient leurs onglets ; la page hôte interroge le registre
 * pour construire la navigation et déléguer le rendu.
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
final class AdminTabRegistry
{
    /** @var list<AdminTabInterface> */
    private array $tabs = [];

    public function add(AdminTabInterface $tab): void
    {
        $this->tabs[] = $tab;
    }

    /**
     * Onglets d'un groupe, dans l'ordre d'insertion.
     *
     * @return list<AdminTabInterface>
     */
    public function forGroup(string $group): array
    {
        return array_values(array_filter(
            $this->tabs,
            static fn (AdminTabInterface $t): bool => $t->group() === $group,
        ));
    }

    public function find(string $group, string $id): ?AdminTabInterface
    {
        foreach ($this->tabs as $tab) {
            if ($tab->group() === $group && $tab->id() === $id) {
                return $tab;
            }
        }
        return null;
    }

    public function firstOfGroup(string $group): ?AdminTabInterface
    {
        return $this->forGroup($group)[0] ?? null;
    }

    /**
     * Groupes contenant au moins un onglet, dans l'ordre d'AdminGroups.
     *
     * @return array<string, string> id => libellé
     */
    public function groupsWithTabs(): array
    {
        $result = [];
        foreach (AdminGroups::all() as $id => $label) {
            if ($this->forGroup($id) !== []) {
                $result[$id] = $label;
            }
        }
        return $result;
    }
}
```

- [ ] **Step 4: Lancer le test → passe**

Run: `./vendor/bin/phpunit tests/Unit/Admin/AdminTabRegistryTest.php`
Expected: OK (4 tests).

- [ ] **Step 5: Commit**

```bash
git add src/Admin/AdminTabRegistry.php tests/Unit/Admin/AdminTabRegistryTest.php
git commit -m "feat(admin): registre d'onglets AdminTabRegistry (TDD)"
```

---

## Task 3: Page hôte ThemeAdminPage

**Files:**
- Create: `src/Admin/ThemeAdminPage.php`
- Test: `tests/Unit/Admin/ThemeAdminPageTest.php`

- [ ] **Step 1: Écrire le test qui échoue**

`tests/Unit/Admin/ThemeAdminPageTest.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Admin\AdminTabInterface;
use OliTheme\Admin\AdminTabRegistry;
use OliTheme\Admin\ThemeAdminPage;
use PHPUnit\Framework\TestCase;

final class ThemeAdminPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg(1);
        Functions\when('esc_attr')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('esc_url')->returnArg(1);
        Functions\when('sanitize_key')->returnArg(1);
        Functions\when('admin_url')->alias(static fn (string $p = ''): string => 'http://x/wp-admin/' . $p);
        Functions\when('add_query_arg')->alias(static fn (array $a, string $u): string => $u . '?' . http_build_query($a));
        Functions\when('current_user_can')->justReturn(true);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    private function tab(string $id, string $group, string $marker): AdminTabInterface
    {
        return new class ($id, $group, $marker) implements AdminTabInterface {
            public function __construct(
                private string $id,
                private string $group,
                private string $marker,
            ) {
            }
            public function id(): string { return $this->id; }
            public function group(): string { return $this->group; }
            public function label(): string { return ucfirst($this->id); }
            public function capability(): string { return 'manage_options'; }
            public function renderPanel(): void { echo '[' . $this->marker . ']'; }
        };
    }

    private function registryWithTabs(): AdminTabRegistry
    {
        $r = new AdminTabRegistry();
        $r->add($this->tab('banner', 'identite', 'BANNER'));
        $r->add($this->tab('galerie', 'contenu', 'GALERIE'));
        return $r;
    }

    public function testRendersDefaultTabWhenNoParams(): void
    {
        $page = new ThemeAdminPage($this->registryWithTabs());
        $_GET = [];

        ob_start();
        $page->render();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('[BANNER]', $html);
        self::assertStringNotContainsString('[GALERIE]', $html);
    }

    public function testRendersRequestedTabAndSub(): void
    {
        $page = new ThemeAdminPage($this->registryWithTabs());
        $_GET = ['tab' => 'contenu', 'sub' => 'galerie'];

        ob_start();
        $page->render();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('[GALERIE]', $html);
    }

    public function testFallsBackToDefaultForUnknownGroup(): void
    {
        $page = new ThemeAdminPage($this->registryWithTabs());
        $_GET = ['tab' => 'inconnu'];

        ob_start();
        $page->render();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('[BANNER]', $html);
    }

    public function testDeniesWhenCapabilityMissing(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        $page = new ThemeAdminPage($this->registryWithTabs());
        $_GET = ['tab' => 'contenu', 'sub' => 'galerie'];

        ob_start();
        $page->render();
        $html = (string) ob_get_clean();

        self::assertStringNotContainsString('[GALERIE]', $html);
    }
}
```

- [ ] **Step 2: Lancer le test → échoue**

Run: `./vendor/bin/phpunit tests/Unit/Admin/ThemeAdminPageTest.php`
Expected: FAIL — `Class "OliTheme\Admin\ThemeAdminPage" not found`.

- [ ] **Step 3: Implémenter la page hôte**

`src/Admin/ThemeAdminPage.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Admin;

/**
 * Page hôte unifiée des réglages du thème (`themes.php?page=oli-theme-settings`).
 *
 * Lit `?tab` (groupe) et `?sub` (sous-onglet), construit la navigation à partir
 * du {@see AdminTabRegistry} et délègue le rendu du panneau actif.
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
final class ThemeAdminPage
{
    public const PAGE_SLUG = 'oli-theme-settings';

    public function __construct(private readonly AdminTabRegistry $registry)
    {
    }

    public function register(): void
    {
        add_theme_page(
            __('Réglages du thème', 'oli-theme'),
            __('Réglages du thème', 'oli-theme'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
        );
    }

    public function render(): void
    {
        $group = $this->resolveGroup();
        $tab   = $this->resolveTab($group);

        echo '<div class="wrap oli-theme-admin">';
        echo '<h1>' . esc_html(__('Réglages du thème', 'oli-theme')) . '</h1>';

        $this->renderGroupNav($group);

        if ($tab !== null && \count($this->registry->forGroup($group)) > 1) {
            $this->renderSubNav($group, $tab);
        }

        if ($tab === null) {
            echo '</div>';
            return;
        }

        if (!current_user_can($tab->capability())) {
            echo '<p>' . esc_html(__('Accès refusé.', 'oli-theme')) . '</p></div>';
            return;
        }

        echo '<div class="oli-theme-admin__panel">';
        $tab->renderPanel();
        echo '</div></div>';
    }

    private function resolveGroup(): string
    {
        $group = isset($_GET['tab']) && \is_string($_GET['tab'])
            ? sanitize_key((string) $_GET['tab'])
            : AdminGroups::DEFAULT_GROUP;

        if (!AdminGroups::exists($group) || $this->registry->forGroup($group) === []) {
            return AdminGroups::DEFAULT_GROUP;
        }
        return $group;
    }

    private function resolveTab(string $group): ?AdminTabInterface
    {
        $sub = isset($_GET['sub']) && \is_string($_GET['sub'])
            ? sanitize_key((string) $_GET['sub'])
            : '';

        if ($sub !== '') {
            $tab = $this->registry->find($group, $sub);
            if ($tab !== null) {
                return $tab;
            }
        }
        return $this->registry->firstOfGroup($group);
    }

    private function renderGroupNav(string $active): void
    {
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($this->registry->groupsWithTabs() as $id => $label) {
            $url   = add_query_arg(['page' => self::PAGE_SLUG, 'tab' => $id], admin_url('themes.php'));
            $class = 'nav-tab' . ($id === $active ? ' nav-tab-active' : '');
            printf(
                '<a href="%s" class="%s">%s</a>',
                esc_url($url),
                esc_attr($class),
                esc_html($label),
            );
        }
        echo '</h2>';
    }

    private function renderSubNav(string $group, AdminTabInterface $active): void
    {
        echo '<ul class="subsubsub" style="margin:0.5rem 0 1rem;">';
        $tabs  = $this->registry->forGroup($group);
        $last  = \count($tabs) - 1;
        foreach ($tabs as $i => $tab) {
            $url     = add_query_arg(
                ['page' => self::PAGE_SLUG, 'tab' => $group, 'sub' => $tab->id()],
                admin_url('themes.php'),
            );
            $current = $tab->id() === $active->id() ? ' class="current"' : '';
            $sep     = $i < $last ? ' | ' : '';
            printf(
                '<li><a href="%s"%s>%s</a>%s</li>',
                esc_url($url),
                $current,
                esc_html($tab->label()),
                $sep,
            );
        }
        echo '</ul>';
    }
}
```

- [ ] **Step 4: Lancer le test → passe**

Run: `./vendor/bin/phpunit tests/Unit/Admin/ThemeAdminPageTest.php`
Expected: OK (4 tests).

- [ ] **Step 5: Commit**

```bash
git add src/Admin/ThemeAdminPage.php tests/Unit/Admin/ThemeAdminPageTest.php
git commit -m "feat(admin): page hôte ThemeAdminPage à onglets groupés (TDD)"
```

---

## Task 4: AdminModule (menu unique + bootstrap registre)

**Files:**
- Create: `src/Admin/AdminModule.php`
- Modify: `src/Theme.php:308` (zone d'enregistrement des modules)

- [ ] **Step 1: Implémenter le module**

`src/Admin/AdminModule.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Admin;

use OliTheme\Container;

/**
 * Module d'administration unifiée : enregistre la page hôte unique et le
 * registre d'onglets partagé entre modules.
 *
 * Doit être enregistré AVANT les modules qui publient des onglets, afin que le
 * registre existe dans le conteneur au moment où ils s'y abonnent.
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
final class AdminModule
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        if (!$this->container->has(AdminTabRegistry::class)) {
            $this->container->factory(
                AdminTabRegistry::class,
                static fn (): AdminTabRegistry => new AdminTabRegistry(),
            );
        }

        if (!$this->container->has(ThemeAdminPage::class)) {
            $this->container->factory(
                ThemeAdminPage::class,
                static fn (Container $c): ThemeAdminPage => new ThemeAdminPage(
                    $c->get(AdminTabRegistry::class),
                ),
            );
        }

        add_action('admin_menu', function (): void {
            $page = $this->container->get(ThemeAdminPage::class);
            \assert($page instanceof ThemeAdminPage);
            $page->register();
        }, 20);
    }
}
```

> Note : le conteneur maison expose `factory()` et `has()` (cf. `src/Settings/SettingsModule.php:43-46`). Le hook `admin_menu` est en priorité 20 pour passer après l'abonnement des onglets par les autres modules (priorité 10).

- [ ] **Step 2: Brancher le module dans Theme.php**

Dans `src/Theme.php`, juste avant la ligne 308 (`(new \OliTheme\Settings\SettingsModule($container))->register();`), insérer :

```php
        (new \OliTheme\Admin\AdminModule($container))->register();
```

- [ ] **Step 3: Vérifier que rien n'est cassé (page hôte vide pour l'instant)**

Run: `./vendor/bin/phpunit tests/Unit/Admin`
Expected: OK (tous les tests Admin passent).

- [ ] **Step 4: Commit**

```bash
git add src/Admin/AdminModule.php src/Theme.php
git commit -m "feat(admin): AdminModule enregistre la page hôte et le registre"
```

---

## Task 5: ThemeSettingsPage → onglets délégables + retrait du code mort social

**Files:**
- Modify: `src/Settings/ThemeSettingsPage.php`
- Modify: `src/Settings/SettingsModule.php`
- Create: `src/Settings/SettingsTab.php`

**Contexte :** `ThemeSettingsPage::render()` (lignes 96-120) enveloppe `settings_fields` + `do_settings_sections(PAGE_SLUG . '-' . $tab)` dans un template. On extrait le rendu d'un onglet donné dans une méthode publique `renderPanelFor(string $tab)`, puis on crée un adaptateur `SettingsTab` (un par onglet conservé) implémentant `AdminTabInterface`.

- [ ] **Step 1: Extraire `renderPanelFor` dans ThemeSettingsPage**

Remplacer le corps de `render()` (lignes 96-120) par une délégation et ajouter `renderPanelFor` :

```php
    public function render(): void
    {
        $activeTab = isset($_GET['tab']) && \is_string($_GET['tab'])
            ? sanitize_key((string) $_GET['tab'])
            : self::DEFAULT_TAB;

        if (!\in_array($activeTab, $this->tabIds(), true)) {
            $activeTab = self::DEFAULT_TAB;
        }

        $this->renderPanelFor($activeTab);
    }

    /**
     * Imprime le formulaire Settings API d'un onglet donné, sans wrapper de page.
     * Appelé par la page hôte unifiée via l'adaptateur {@see SettingsTab}.
     */
    public function renderPanelFor(string $tab): void
    {
        if (!\in_array($tab, $this->tabIds(), true)) {
            $tab = self::DEFAULT_TAB;
        }

        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);
        do_settings_sections(self::PAGE_SLUG . '-' . $tab);
        printf('<input type="hidden" name="_oli_active_tab" value="%s" />', esc_attr($tab));
        submit_button();
        echo '</form>';
    }
```

> `action="options.php"` est la cible standard de la Settings API (le `register_setting` reste inchangé). On supprime l'usage du template `admin/settings-page.html` ici car la navigation est désormais gérée par la page hôte.

- [ ] **Step 2: Supprimer le code mort « social »**

Dans `src/Settings/ThemeSettingsPage.php` :
- supprimer l'appel à `registerSocialFields(...)` dans `registerSettings()` (chercher `registerSocialFields` autour des lignes 63-95) ;
- supprimer la méthode `registerSocialFields()` (lignes ~307-333) ;
- supprimer la méthode `sanitizeSocial()` (lignes ~334-348) ;
- dans `sanitize()` (lignes 130-160), supprimer le bloc :
  ```php
          if (isset($input['social']) && \is_array($input['social'])) {
              $clean['social'] = $this->sanitizeSocial($input['social']);
          }
  ```
- dans `tabIds()` / `tabsFor()` (lignes ~786-814), retirer l'entrée `social`.

- [ ] **Step 3: Ajuster les tests existants de ThemeSettingsPage**

Run: `./vendor/bin/phpunit tests/Unit/Settings`
Expected (avant ajustement) : FAIL sur les assertions liées à l'onglet `social`.

Ouvrir le fichier de test (`tests/Unit/Settings/ThemeSettingsPageTest.php`), retirer/adapter les cas qui vérifient l'onglet `social`, l'appel `registerSocialFields`, ou la clé `social` dans `sanitize()`. Relancer :

Run: `./vendor/bin/phpunit tests/Unit/Settings`
Expected: OK.

- [ ] **Step 4: Créer l'adaptateur SettingsTab**

`src/Settings/SettingsTab.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Settings;

use OliTheme\Admin\AdminTabInterface;

/**
 * Adaptateur exposant un onglet de {@see ThemeSettingsPage} (banner, languages,
 * footer, contact, seo) comme sous-onglet de la page de réglages unifiée.
 *
 * @package OliTheme\Settings
 *
 * @since 1.1.0
 */
final class SettingsTab implements AdminTabInterface
{
    public function __construct(
        private readonly ThemeSettingsPage $page,
        private readonly string $settingsTab,
        private readonly string $group,
        private readonly string $id,
        private readonly string $label,
    ) {
    }

    public function id(): string { return $this->id; }
    public function group(): string { return $this->group; }
    public function label(): string { return $this->label; }
    public function capability(): string { return 'manage_options'; }

    public function renderPanel(): void
    {
        $this->page->renderPanelFor($this->settingsTab);
    }
}
```

- [ ] **Step 5: Publier les onglets dans SettingsModule**

Dans `src/Settings/SettingsModule.php`, le hook `admin_menu` (lignes ~53-57) appelle aujourd'hui `$page->register()`. Remplacer cet enregistrement de page par l'abonnement des onglets au registre, en priorité 10 :

```php
        add_action('admin_menu', function (): void {
            $page = $this->container->get(ThemeSettingsPage::class);
            \assert($page instanceof ThemeSettingsPage);

            $registry = $this->container->get(\OliTheme\Admin\AdminTabRegistry::class);
            \assert($registry instanceof \OliTheme\Admin\AdminTabRegistry);

            $registry->add(new SettingsTab($page, 'banner',    'identite', 'banner',    __('Identité visuelle', 'oli-theme')));
            $registry->add(new SettingsTab($page, 'languages', 'identite', 'languages', __('Langues', 'oli-theme')));
            $registry->add(new SettingsTab($page, 'footer',    'identite', 'footer',    __('Pied de page', 'oli-theme')));
            $registry->add(new SettingsTab($page, 'contact',   'contact',  'contact',   __('Contact', 'oli-theme')));
            $registry->add(new SettingsTab($page, 'seo',       'seo',      'reglages',  __('Réglages SEO', 'oli-theme')));
        }, 10);
```

> Le `register_setting` reste enregistré par ailleurs (méthode `registerSettings()` sur le hook `admin_init`) — on ne le touche pas. On retire seulement l'`add_theme_page`.

- [ ] **Step 6: Lancer toute la suite**

Run: `./vendor/bin/phpunit tests/Unit`
Expected: OK (hors 5 erreurs ThemeTest préexistantes liées à `/tmp/templates`).

- [ ] **Step 7: Commit**

```bash
git add src/Settings/ThemeSettingsPage.php src/Settings/SettingsModule.php src/Settings/SettingsTab.php tests/Unit/Settings/ThemeSettingsPageTest.php
git commit -m "refactor(admin): onglets Identité délégués + suppression du social mort"
```

---

## Task 6: SocialAdminPage → onglet

**Files:**
- Modify: `src/Social/SocialAdminPage.php`
- Modify: `src/Social/SocialModule.php`

- [ ] **Step 1: Extraire `renderPanel` et implémenter l'interface**

Dans `src/Social/SocialAdminPage.php` :
- ajouter `use OliTheme\Admin\AdminTabInterface;` et `implements AdminTabInterface` sur la classe ;
- ajouter les méthodes du contrat :
  ```php
      public function id(): string { return 'social'; }
      public function group(): string { return 'identite'; }
      public function label(): string { return __('Réseaux sociaux', 'oli-theme'); }
      public function capability(): string { return 'manage_options'; }
  ```
- renommer le corps de `render()` : déplacer tout le contenu **intérieur** du `<div class="wrap oli-social-admin">` (c.-à-d. à partir de la `<div class="notice...">` jusqu'à juste avant `</div>` fermant `.wrap`, lignes ~47-88) dans une nouvelle méthode `renderPanel(): void`. Conserver en tête de `renderPanel()` le traitement du save et le calcul de `$values`/`$iconsBaseUri` (lignes 37-45). Retirer le `<div class="wrap">` et le `<h1>` (fournis par la page hôte) ;
- supprimer la méthode `register()` (l'`add_theme_page`).

Squelette résultant :

```php
    public function renderPanel(): void
    {
        if (!empty($_POST['oli_social_save'])) {
            $this->handleSave();
        }

        $values = $this->repo->all();
        $iconsBaseUri = \function_exists('get_template_directory_uri')
            ? rtrim((string) get_template_directory_uri(), '/') . '/assets/img/icons/social'
            : '/assets/img/icons/social';
        ?>
        <div class="notice notice-info inline" style="margin:1rem 0;padding:0.75rem 1rem;">
            <!-- ... bloc notice inchangé ... -->
        </div>
        <form method="post" action="">
            <?php wp_nonce_field('oli_social_save', '_oli_social_nonce'); ?>
            <input type="hidden" name="oli_social_save" value="1">
            <table class="form-table" role="presentation"><tbody>
                <!-- ... boucle PLATFORMS inchangée ... -->
            </tbody></table>
            <?php submit_button(); ?>
        </form>
        <?php
    }
```

> `action=""` poste sur la page courante (hôte), et `handleSave()` lit `$_POST` — comportement inchangé. Le `handleSave()` redirige déjà ou recharge ; la mise à jour de l'URL de redirection est traitée en Task 11.

- [ ] **Step 2: Publier l'onglet dans SocialModule**

Dans `src/Social/SocialModule.php`, remplacer le hook `admin_menu` qui appelait `$page->register()` par :

```php
        add_action('admin_menu', function (): void {
            $registry = $this->container->get(\OliTheme\Admin\AdminTabRegistry::class);
            \assert($registry instanceof \OliTheme\Admin\AdminTabRegistry);
            $page = $this->container->get(SocialAdminPage::class);
            \assert($page instanceof SocialAdminPage);
            $registry->add($page);
        }, 10);
```

> Le `handleSave()` est appelé depuis `renderPanel()` ; aucun hook `admin_post` à conserver ici (le save est inline via `$_POST`).

- [ ] **Step 3: Lancer les tests Social**

Run: `./vendor/bin/phpunit tests/Unit/Social`
Expected: OK (adapter les tests qui appelaient `render()` → `renderPanel()` ou qui vérifiaient `add_theme_page`).

- [ ] **Step 4: Commit**

```bash
git add src/Social/SocialAdminPage.php src/Social/SocialModule.php tests/Unit/Social
git commit -m "refactor(admin): SocialAdminPage devient un onglet (identite/social)"
```

---

## Task 7: GalleryAdminPage → onglet

**Files:**
- Modify: `src/Gallery/GalleryAdminPage.php`
- Modify: `src/Gallery/GalleryModule.php`

- [ ] **Step 1: Extraire `renderPanel` et implémenter l'interface**

Dans `src/Gallery/GalleryAdminPage.php` :
- `use OliTheme\Admin\AdminTabInterface;` + `implements AdminTabInterface` ;
- méthodes du contrat :
  ```php
      public function id(): string { return 'galerie'; }
      public function group(): string { return 'contenu'; }
      public function label(): string { return __('Galerie', 'oli-theme'); }
      public function capability(): string { return 'manage_options'; }
  ```
- déplacer le contenu intérieur de `<div class="wrap oli-gallery-admin">` (à partir de la `<div class="notice">` jusqu'avant le `</div>` fermant `.wrap`) dans `renderPanel()`. Conserver en tête de `renderPanel()` : `wp_enqueue_media()`, le traitement du save (`if (!empty($_POST['oli_gallery_save'])) ...`) et les lectures `$photos/$channel/$videos`. Retirer le `<div class="wrap">` et le `<h1>` ;
- supprimer `register()`.

- [ ] **Step 2: Publier l'onglet dans GalleryModule**

Dans `src/Gallery/GalleryModule.php`, remplacer le hook `admin_menu` (lignes ~52+) par :

```php
        add_action('admin_menu', function (): void {
            $registry = $c->get(\OliTheme\Admin\AdminTabRegistry::class);
            \assert($registry instanceof \OliTheme\Admin\AdminTabRegistry);
            $page = $c->get(GalleryAdminPage::class);
            \assert($page instanceof GalleryAdminPage);
            $registry->add($page);
        }, 10);
```

> Adapter `$c`/`$this->container` selon la variable de closure existante dans `GalleryModule`.

- [ ] **Step 3: Lancer les tests Gallery**

Run: `./vendor/bin/phpunit tests/Unit/Gallery`
Expected: OK (adapter `render()` → `renderPanel()` dans les tests).

- [ ] **Step 4: Commit**

```bash
git add src/Gallery/GalleryAdminPage.php src/Gallery/GalleryModule.php tests/Unit/Gallery
git commit -m "refactor(admin): GalleryAdminPage devient un onglet (contenu/galerie)"
```

---

## Task 8: ThemeVariationPage → onglet

**Files:**
- Modify: `src/Appearance/ThemeVariationPage.php`
- Modify: `src/Appearance/AppearanceModule.php`

- [ ] **Step 1: Extraire `renderPanel` et implémenter l'interface**

Dans `src/Appearance/ThemeVariationPage.php` :
- `use OliTheme\Admin\AdminTabInterface;` + `implements AdminTabInterface` ;
- méthodes du contrat :
  ```php
      public function id(): string { return 'variations'; }
      public function group(): string { return 'apparence'; }
      public function label(): string { return __('Variations CSS', 'oli-theme'); }
      public function capability(): string { return 'manage_options'; }
  ```
- déplacer le contenu intérieur de `render()` (lignes ~117+) dans `renderPanel()`, en retirant `<div class="wrap">` et `<h1>` ;
- supprimer `register()` (l'`add_theme_page`). **Conserver `registerSettings()`** (hook `admin_init`, lignes ~55+) inchangé.

- [ ] **Step 2: Publier l'onglet dans AppearanceModule**

Dans `src/Appearance/AppearanceModule.php`, là où `ThemeVariationPage::register()` était branché sur `admin_menu`, remplacer par l'abonnement au registre :

```php
        add_action('admin_menu', function (): void {
            $registry = $this->container->get(\OliTheme\Admin\AdminTabRegistry::class);
            \assert($registry instanceof \OliTheme\Admin\AdminTabRegistry);
            $page = $this->container->get(ThemeVariationPage::class);
            \assert($page instanceof ThemeVariationPage);
            $registry->add($page);
        }, 10);
```

- [ ] **Step 3: Lancer les tests Appearance**

Run: `./vendor/bin/phpunit tests/Unit/Appearance`
Expected: OK (adapter `render()` → `renderPanel()`).

- [ ] **Step 4: Commit**

```bash
git add src/Appearance/ThemeVariationPage.php src/Appearance/AppearanceModule.php tests/Unit/Appearance
git commit -m "refactor(admin): ThemeVariationPage devient un onglet (apparence/variations)"
```

---

## Task 9: SeoOverviewPage + RedirectsPage → onglets (groupe seo)

**Files:**
- Modify: `src/Seo/Admin/SeoOverviewPage.php`
- Modify: `src/Seo/Admin/RedirectsPage.php`
- Modify: `src/Seo/SeoModule.php`

- [ ] **Step 1: SeoOverviewPage → onglet**

Dans `src/Seo/Admin/SeoOverviewPage.php` :
- `use OliTheme\Admin\AdminTabInterface;` + `implements AdminTabInterface` ;
- méthodes du contrat :
  ```php
      public function id(): string { return 'dashboard'; }
      public function group(): string { return 'seo'; }
      public function label(): string { return __('Dashboard', 'oli-theme'); }
      public function capability(): string { return 'manage_options'; }
  ```
- déplacer le contenu intérieur de `render()` (lignes ~65+) dans `renderPanel()`, retirer `<div class="wrap">` + `<h1>` ;
- supprimer `register()` (l'`add_management_page`). **Conserver `registerActions()`** (hook `admin_post_oli_seo_export_csv`, lignes ~60-62) inchangé — l'export CSV reste un endpoint admin-post.

- [ ] **Step 2: RedirectsPage → onglet**

Dans `src/Seo/Admin/RedirectsPage.php` :
- `use OliTheme\Admin\AdminTabInterface;` + `implements AdminTabInterface` ;
- méthodes du contrat :
  ```php
      public function id(): string { return 'redirections'; }
      public function group(): string { return 'seo'; }
      public function label(): string { return __('Redirections', 'oli-theme'); }
      public function capability(): string { return 'manage_options'; }
  ```
- déplacer le contenu intérieur de `render()` (lignes ~65+) dans `renderPanel()`, retirer `<div class="wrap">` + `<h1>` ;
- supprimer `register()` (l'`add_management_page`). **Conserver `registerActions()`** (hooks `admin_post_oli_redirect_save` / `_delete`, lignes ~59-62) inchangé ;
- les URLs `edit_url`/`delete_url` (lignes ~98-103) construites avec `['page' => self::PAGE_SLUG, ...]` seront mises à jour en Task 12.

- [ ] **Step 3: Publier les deux onglets dans SeoModule**

Dans `src/Seo/SeoModule.php`, là où `SeoOverviewPage::register()` et `RedirectsPage::register()` étaient branchés sur `admin_menu`, remplacer par :

```php
        add_action('admin_menu', function (): void {
            $registry = $this->container->get(\OliTheme\Admin\AdminTabRegistry::class);
            \assert($registry instanceof \OliTheme\Admin\AdminTabRegistry);

            $reglages = $this->container->get(\OliTheme\Settings\ThemeSettingsPage::class);
            // L'onglet "reglages" (SEO global) est déjà publié par SettingsModule.

            $overview = $this->container->get(SeoOverviewPage::class);
            \assert($overview instanceof SeoOverviewPage);
            $registry->add($overview);

            $redirects = $this->container->get(RedirectsPage::class);
            \assert($redirects instanceof RedirectsPage);
            $registry->add($redirects);
        }, 10);
```

> L'ordre du groupe `seo` est : `reglages` (publié par SettingsModule), puis `dashboard`, puis `redirections`. L'ordre d'insertion dépend de l'ordre d'enregistrement des modules dans `Theme.php` (Settings avant Seo) — vérifier que `SettingsModule` est enregistré avant `SeoModule` (c'est le cas : lignes 308 et 312). Retirer la ligne `$reglages` si inutilisée (elle n'est là que pour documenter l'ordre).

- [ ] **Step 4: Lancer les tests Seo**

Run: `./vendor/bin/phpunit tests/Unit/Seo`
Expected: OK (adapter `render()` → `renderPanel()` et retrait `add_management_page` dans les tests).

- [ ] **Step 5: Commit**

```bash
git add src/Seo/Admin/SeoOverviewPage.php src/Seo/Admin/RedirectsPage.php src/Seo/SeoModule.php tests/Unit/Seo
git commit -m "refactor(admin): SEO Dashboard et Redirections deviennent des onglets (groupe seo)"
```

---

## Task 10: Redirections de compatibilité des anciens slugs

**Files:**
- Modify: `src/Admin/AdminModule.php`
- Create: `src/Admin/LegacySlugRedirector.php`
- Test: `tests/Unit/Admin/AdminCompatRedirectTest.php`

- [ ] **Step 1: Écrire le test qui échoue**

`tests/Unit/Admin/AdminCompatRedirectTest.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Admin\LegacySlugRedirector;
use PHPUnit\Framework\TestCase;

final class AdminCompatRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('sanitize_key')->returnArg(1);
        Functions\when('admin_url')->alias(static fn (string $p = ''): string => 'http://x/wp-admin/' . $p);
        Functions\when('add_query_arg')->alias(static fn (array $a, string $u): string => $u . '?' . http_build_query($a));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testMapsSocialSlugToTab(): void
    {
        $target = (new LegacySlugRedirector())->targetFor('oli-social-links', []);
        self::assertNotNull($target);
        self::assertStringContainsString('page=oli-theme-settings', $target);
        self::assertStringContainsString('tab=identite', $target);
        self::assertStringContainsString('sub=social', $target);
    }

    public function testMapsRedirectsSlugAndKeepsExtraParams(): void
    {
        $target = (new LegacySlugRedirector())->targetFor('oli-seo-redirects', ['paged' => '2']);
        self::assertNotNull($target);
        self::assertStringContainsString('tab=seo', $target);
        self::assertStringContainsString('sub=redirections', $target);
        self::assertStringContainsString('paged=2', $target);
    }

    public function testReturnsNullForUnknownSlug(): void
    {
        self::assertNull((new LegacySlugRedirector())->targetFor('some-other-page', []));
    }
}
```

- [ ] **Step 2: Lancer → échoue**

Run: `./vendor/bin/phpunit tests/Unit/Admin/AdminCompatRedirectTest.php`
Expected: FAIL — `Class "OliTheme\Admin\LegacySlugRedirector" not found`.

- [ ] **Step 3: Implémenter le redirecteur**

`src/Admin/LegacySlugRedirector.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Admin;

/**
 * Calcule l'URL cible vers la page de réglages unifiée pour un ancien slug
 * d'admin (compatibilité des liens et bookmarks après consolidation).
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
final class LegacySlugRedirector
{
    /** @var array<string, array{tab: string, sub: string}> */
    private const MAP = [
        'oli-social-links'     => ['tab' => 'identite',  'sub' => 'social'],
        'oli-gallery'          => ['tab' => 'contenu',   'sub' => 'galerie'],
        'oli-theme-variations' => ['tab' => 'apparence', 'sub' => 'variations'],
        'oli-seo-dashboard'    => ['tab' => 'seo',       'sub' => 'dashboard'],
        'oli-seo-redirects'    => ['tab' => 'seo',       'sub' => 'redirections'],
    ];

    /**
     * URL cible pour un ancien slug, ou null si le slug n'est pas concerné.
     *
     * @param array<string, scalar> $extra Paramètres GET additionnels à propager
     *                                      (ex. `edit`, `paged`), hors `page`.
     */
    public function targetFor(string $slug, array $extra): ?string
    {
        if (!isset(self::MAP[$slug])) {
            return null;
        }

        $args = [
            'page' => ThemeAdminPage::PAGE_SLUG,
            'tab'  => self::MAP[$slug]['tab'],
            'sub'  => self::MAP[$slug]['sub'],
        ];
        unset($extra['page']);
        $args += $extra;

        return add_query_arg($args, admin_url('themes.php'));
    }
}
```

- [ ] **Step 4: Lancer → passe**

Run: `./vendor/bin/phpunit tests/Unit/Admin/AdminCompatRedirectTest.php`
Expected: OK (3 tests).

- [ ] **Step 5: Brancher la redirection dans AdminModule**

Dans `src/Admin/AdminModule.php`, ajouter à la fin de `register()` :

```php
        add_action('admin_init', function (): void {
            if (!isset($_GET['page']) || !\is_string($_GET['page'])) {
                return;
            }
            $slug  = sanitize_key((string) $_GET['page']);
            $extra = array_map(
                static fn ($v): string => \is_scalar($v) ? (string) $v : '',
                $_GET,
            );
            $target = (new LegacySlugRedirector())->targetFor($slug, $extra);
            if ($target !== null) {
                wp_safe_redirect($target, 301);
                exit;
            }
        });
```

- [ ] **Step 6: Lancer la suite Admin**

Run: `./vendor/bin/phpunit tests/Unit/Admin`
Expected: OK.

- [ ] **Step 7: Commit**

```bash
git add src/Admin/LegacySlugRedirector.php src/Admin/AdminModule.php tests/Unit/Admin/AdminCompatRedirectTest.php
git commit -m "feat(admin): redirection 301 des anciens slugs vers les onglets (TDD)"
```

---

## Task 11: URLs post-save vers le bon onglet

**Files:**
- Modify: `src/Social/SocialAdminPage.php` (handleSave redirect)
- Modify: `src/Gallery/GalleryAdminPage.php` (handleSave redirect)
- Modify: `src/Seo/Admin/RedirectsPage.php` (handleSave/handleDelete redirect)

- [ ] **Step 1: Repérer les redirections post-save**

Run: `grep -n "wp_safe_redirect\|wp_redirect\|add_query_arg" src/Social/SocialAdminPage.php src/Gallery/GalleryAdminPage.php src/Seo/Admin/RedirectsPage.php`
Expected: liste des cibles utilisant les anciens slugs.

- [ ] **Step 2: Mettre à jour chaque cible**

Pour chaque redirection construite avec l'ancien `self::PAGE_SLUG`, remplacer par les paramètres de l'onglet correspondant. Exemples :

- Social (`handleSave`) → après save, recharger l'onglet :
  ```php
  wp_safe_redirect(add_query_arg(
      ['page' => 'oli-theme-settings', 'tab' => 'identite', 'sub' => 'social', 'updated' => '1'],
      admin_url('themes.php'),
  ));
  exit;
  ```
- Redirections (`handleSave`/`handleDelete`) :
  ```php
  wp_safe_redirect(add_query_arg(
      ['page' => 'oli-theme-settings', 'tab' => 'seo', 'sub' => 'redirections'],
      admin_url('themes.php'),
  ));
  exit;
  ```
- Galerie : si `handleSave` ne redirige pas (rendu inline), aucune modification — sinon appliquer le même schéma avec `tab=contenu&sub=galerie`.

Mettre aussi à jour les liens internes `edit_url`/`delete_url` de `RedirectsPage` (lignes ~98-103) :
```php
'edit_url' => add_query_arg(
    ['page' => 'oli-theme-settings', 'tab' => 'seo', 'sub' => 'redirections', 'edit' => $r->id],
    admin_url('themes.php'),
),
```
(idem `delete_url` en conservant le nonce).

- [ ] **Step 3: Lancer les suites concernées**

Run: `./vendor/bin/phpunit tests/Unit/Social tests/Unit/Gallery tests/Unit/Seo`
Expected: OK (adapter les assertions d'URL de redirection dans les tests).

- [ ] **Step 4: Commit**

```bash
git add src/Social/SocialAdminPage.php src/Gallery/GalleryAdminPage.php src/Seo/Admin/RedirectsPage.php tests/Unit/Social tests/Unit/Gallery tests/Unit/Seo
git commit -m "fix(admin): redirections post-save et liens internes vers les onglets"
```

---

## Task 12: Vérification end-to-end + qualité

**Files:** aucun (vérification).

- [ ] **Step 1: Suite complète + analyse statique**

Run: `./vendor/bin/phpunit tests/Unit && ./vendor/bin/phpstan analyse src --level=8`
Expected: tests OK (hors 5 erreurs ThemeTest préexistantes `/tmp/templates`) ; PHPStan clean.

- [ ] **Step 2: CS-Fixer**

Run: `./vendor/bin/php-cs-fixer fix src --dry-run --diff`
Expected: aucune correction (sinon lancer sans `--dry-run` et committer).

- [ ] **Step 3: Déployer sur le site de dev**

```bash
rsync -a --delete /home/seb/Dev/olikalari.com/src/ /home/seb/Dev/Wordpress/wp-local/wp-content/themes/oli-theme/src/
docker exec wp-local-wordpress-1 chown -R www-data:www-data /var/www/html/wp-content/themes/oli-theme/src
```

- [ ] **Step 4: Vérifier en navigateur (via ctx_execute fetch)**

Vérifier que :
- `themes.php?page=oli-theme-settings` affiche les 5 onglets principaux + le sous-onglet par défaut `banner` ;
- chaque ancien slug (`oli-social-links`, `oli-gallery`, `oli-theme-variations`, `oli-seo-dashboard`, `oli-seo-redirects`) redirige (301) vers le bon onglet ;
- le menu Apparence ne contient plus qu'un seul item « Réglages du thème » ; le menu Outils ne contient plus SEO/Redirections ;
- une sauvegarde sur chaque onglet (Identité, Réseaux sociaux, Galerie, Variations, SEO réglages, Redirections) persiste et revient au bon onglet.

- [ ] **Step 5: Mettre à jour la doc + ADR**

Ajouter un ADR `docs/decisions/0014-admin-consolidation.md` résumant la décision (page hôte + registre + groupes), et mettre à jour `docs/settings.md` (nouvelle navigation à onglets groupés). Mettre à jour le `CHANGELOG.md`.

- [ ] **Step 6: Commit final + push**

```bash
git add docs/ CHANGELOG.md
git commit -m "docs(admin): ADR 0014 + doc navigation unifiée des réglages"
git push origin main
```

---

## Notes d'exécution

- **Ordre des modules** (`Theme.php`) : `AdminModule` doit être enregistré avant Settings/Social/Gallery/Appearance/Seo pour que `AdminTabRegistry` existe dans le conteneur. Hook `admin_menu` : modules en priorité 10 (publient les onglets), page hôte en priorité 20 (lit le registre).
- **Filet de sécurité** : à chaque tâche, les tests des modules concernés doivent rester verts. Si un test échoue à cause du retrait d'`add_theme_page`/`render()`, l'adapter (c'est attendu), pas contourner.
- **5 erreurs préexistantes** `ThemeTest` (`Base path does not exist: /tmp/templates`) ne sont pas liées à ce travail — ne pas tenter de les corriger ici.
