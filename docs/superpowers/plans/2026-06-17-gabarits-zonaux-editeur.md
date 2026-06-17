# Gabarits zonaux : éditeur plein écran — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Pour un post à gabarit zonal, remplacer Gutenberg par un formulaire de zones en colonne principale, avec un rich-editor basique par zone texte.

**Architecture:** Filtre `use_block_editor_for_post` qui force l'éditeur classique sur les posts zonaux ; le formulaire de zones migre de la metabox sidebar vers le hook `edit_form_after_title` (colonne principale) et masque le champ de contenu natif ; les zones texte passent du `<textarea>` à `wp_editor()` toolbar restreinte. Save et rendu front inchangés.

**Tech Stack:** PHP 8.3, WordPress (hooks), PHPUnit 11, Brain/Monkey + Mockery.

## Global Constraints

- Namespace `OliTheme` ; code en anglais, commentaires en français.
- Conventional Commits en français. **Aucun** `Co-Authored-By: Claude`.
- DRY / SOLID / KISS / YAGNI.
- Le thème reste 100 % autonome : `tests/Unit/Core/SelfContainedThemeTest.php` doit rester vert.
- Noms de champs de zones **inchangés** : `oli_gabarit_zone[{id}][text|imageId|imageIdsCsv]`.
- `Gabarit::isZonal()` existe déjà (`src/Gabarits/Gabarit.php:50`) — ne pas le réécrire.
- Lancer les tests avec : `vendor/bin/phpunit` (ou `composer test` si défini).

---

### Task 1 : Désactiver Gutenberg pour les posts à gabarit zonal

**Files:**
- Modify: `src/Gabarits/GabaritModule.php`
- Test: `tests/Unit/Gabarits/GabaritModuleTest.php` (créer)

**Interfaces:**
- Consumes: `Gabarit::isZonal(): bool`, `GabaritResolver::forPost(int): ?Gabarit`.
- Produces: `GabaritModule::decideBlockEditor(bool $useBlockEditor, ?Gabarit $gabarit): bool` — décision pure ; renvoie `false` si `$gabarit` est zonal, sinon `$useBlockEditor` tel quel.

- [ ] **Step 1: Écrire le test qui échoue**

Créer `tests/Unit/Gabarits/GabaritModuleTest.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gabarits;

use Brain\Monkey;
use OliTheme\Container;
use OliTheme\Gabarits\Gabarit;
use OliTheme\Gabarits\GabaritModule;
use OliTheme\Gabarits\Zone;
use OliTheme\Gabarits\ZoneType;
use PHPUnit\Framework\TestCase;

final class GabaritModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    private function zonalGabarit(): Gabarit
    {
        return new Gabarit('triptyque', 'Triptyque', '', ['post'], '/s.css', null, false, '#000', [
            new Zone('intro', ZoneType::Text, 'Introduction'),
        ]);
    }

    private function cssOnlyGabarit(): Gabarit
    {
        return new Gabarit('magazine', 'Magazine', '', ['post'], '/s.css');
    }

    public function test_disables_block_editor_for_zonal_gabarit(): void
    {
        $module = new GabaritModule(new Container());
        self::assertFalse($module->decideBlockEditor(true, $this->zonalGabarit()));
    }

    public function test_keeps_block_editor_for_css_only_gabarit(): void
    {
        $module = new GabaritModule(new Container());
        self::assertTrue($module->decideBlockEditor(true, $this->cssOnlyGabarit()));
    }

    public function test_keeps_incoming_value_when_no_gabarit(): void
    {
        $module = new GabaritModule(new Container());
        self::assertTrue($module->decideBlockEditor(true, null));
        self::assertFalse($module->decideBlockEditor(false, null));
    }
}
```

- [ ] **Step 2: Lancer le test, vérifier l'échec**

Run: `vendor/bin/phpunit --filter GabaritModuleTest`
Expected: FAIL — `Error: Call to undefined method OliTheme\Gabarits\GabaritModule::decideBlockEditor()`.

- [ ] **Step 3: Implémenter la méthode + câbler le filtre**

Dans `src/Gabarits/GabaritModule.php`, ajouter la méthode publique (après `register()`) :

```php
    /**
     * Décide si l'éditeur de blocs (Gutenberg) reste actif pour un post.
     * Un gabarit zonal impose l'éditeur classique, seul contexte où
     * `wp_editor()` s'initialise correctement.
     */
    public function decideBlockEditor(bool $useBlockEditor, ?Gabarit $gabarit): bool
    {
        if ($gabarit !== null && $gabarit->isZonal()) {
            return false;
        }
        return $useBlockEditor;
    }
```

Et, à la fin de `register()` (avant la fermeture de la méthode), brancher le filtre :

```php
        // Gabarit zonal → éditeur classique (requis pour wp_editor par zone).
        add_filter('use_block_editor_for_post', function ($use, $post) use ($c): bool {
            $gabarit = ($post instanceof \WP_Post)
                ? $c->get(GabaritResolver::class)->forPost((int) $post->ID)
                : null;
            return $this->decideBlockEditor((bool) $use, $gabarit);
        }, 10, 2);
```

- [ ] **Step 4: Lancer le test, vérifier le succès**

Run: `vendor/bin/phpunit --filter GabaritModuleTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add src/Gabarits/GabaritModule.php tests/Unit/Gabarits/GabaritModuleTest.php
git commit -m "feat(gabarits): éditeur classique forcé pour les gabarits zonaux"
```

---

### Task 2 : Formulaire de zones en colonne principale (au lieu de la sidebar)

**Files:**
- Modify: `src/Gabarits/Admin/GabaritMetabox.php`
- Test: `tests/Unit/Gabarits/GabaritMetaboxTest.php` (créer)

**Interfaces:**
- Consumes: `GabaritRegistryInterface::byId(string): ?Gabarit`, `ZoneContentRepository::load(int): array`, `Gabarit::isZonal()`, `Gabarit->zones`, `Gabarit->name`.
- Produces: `GabaritMetabox::renderZoneForm(\WP_Post $post): void` — hooké sur `edit_form_after_title` ; n'émet rien si le gabarit du post n'est pas zonal, sinon émet le formulaire des zones et masque `#postdivrich`.

- [ ] **Step 1: Écrire le test qui échoue**

Créer `tests/Unit/Gabarits/GabaritMetaboxTest.php` :

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gabarits;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Gabarits\Admin\GabaritMetabox;
use OliTheme\Gabarits\Gabarit;
use OliTheme\Gabarits\GabaritRegistryInterface;
use OliTheme\Gabarits\Zone;
use OliTheme\Gabarits\ZoneContentRepository;
use OliTheme\Gabarits\ZoneType;
use PHPUnit\Framework\TestCase;

final class GabaritMetaboxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('esc_html')->returnArg(1);
        Functions\when('esc_attr')->returnArg(1);
        Functions\when('esc_textarea')->returnArg(1);
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('__')->returnArg(1);
        Functions\when('wp_get_attachment_image')->justReturn('');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    private function post(int $id): \WP_Post
    {
        $post = $this->getMockBuilder(\stdClass::class)->getMock();
        // WP_Post est final-like ; on simule via un objet anonyme typé.
        $p = new \WP_Post();
        $p->ID = $id;
        $p->post_type = 'post';
        return $p;
    }

    public function test_renders_nothing_for_non_zonal_post(): void
    {
        Functions\when('get_post_meta')->justReturn('magazine');
        $registry = $this->createMock(GabaritRegistryInterface::class);
        $registry->method('byId')->willReturn(
            new Gabarit('magazine', 'Magazine', '', ['post'], '/s.css'),
        );
        $box = new GabaritMetabox($registry, new ZoneContentRepository());

        ob_start();
        $box->renderZoneForm($this->post(7));
        self::assertSame('', ob_get_clean());
    }

    public function test_renders_zone_label_for_zonal_post(): void
    {
        Functions\when('get_post_meta')->alias(
            fn (int $id, string $key) => $key === '_oli_gabarit' ? 'triptyque' : '',
        );
        $registry = $this->createMock(GabaritRegistryInterface::class);
        $registry->method('byId')->willReturn(
            new Gabarit('triptyque', 'Triptyque', '', ['post'], '/s.css', null, false, '#000', [
                new Zone('intro', ZoneType::Text, 'Introduction'),
            ]),
        );
        $box = new GabaritMetabox($registry, new ZoneContentRepository());

        ob_start();
        $box->renderZoneForm($this->post(7));
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Introduction', $html);
        self::assertStringContainsString('#postdivrich', $html);
    }
}
```

> Note : `\WP_Post` est fourni par le stub de test du thème (voir `tests/bootstrap.php`). Si la classe n'existe pas en contexte de test, ajouter en tête du test : `if (!class_exists('WP_Post')) { class WP_Post { public int $ID = 0; public string $post_type = 'post'; } }`. Le `test_renders_zone_label_for_zonal_post` exige aussi que `wp_editor` soit défini ; ajouter `Functions\when('wp_editor')->justReturn(null);` dans `setUp` (la zone texte l'appelle après Task 3 ; inoffensif avant).

- [ ] **Step 2: Lancer le test, vérifier l'échec**

Run: `vendor/bin/phpunit --filter GabaritMetaboxTest`
Expected: FAIL — `Call to undefined method ...::renderZoneForm()`.

- [ ] **Step 3: Implémenter — déplacer le rendu des zones**

Dans `src/Gabarits/Admin/GabaritMetabox.php` :

(a) Dans `register()`, ajouter le hook (après la ligne `add_action('admin_enqueue_scripts', ...)`) :

```php
        add_action('edit_form_after_title', [$this, 'renderZoneForm']);
```

(b) Dans `render()`, **supprimer** le bloc d'édition des zones (actuellement lignes ~112-122) :

```php
        // Édition des zones du gabarit sélectionné.
        if ($selected !== null && $selected->isZonal()) {
            $contents = $this->zones->load($post->ID);
            echo '<hr style="margin:1rem 0;">';
            echo '<h3 style="margin:0 0 0.5rem;font-size:1rem;">' . esc_html__('Zones du gabarit', 'oli-theme') . '</h3>';
            echo '<p style="color:#50575e;margin:0 0 1rem;">' . esc_html__('Renseignez chaque zone. Les zones vides ne seront pas affichées.', 'oli-theme') . '</p>';
            foreach ($selected->zones as $zone) {
                $content = $contents[$zone->id] ?? new ZoneContent($zone->type);
                $this->renderZone($zone, $content);
            }
        }
```

La metabox sidebar ne garde donc que : sélecteur + description + notice `oli-gabarit-save-hint` + script de hint + lien galerie.

(c) Ajouter la nouvelle méthode publique :

```php
    /**
     * Rend le formulaire d'édition des zones dans la colonne principale
     * (hook `edit_form_after_title`). N'émet rien si le gabarit du post
     * n'est pas zonal. Masque le champ de contenu natif : pour un gabarit
     * zonal, ce sont les zones qui constituent la surface d'édition.
     */
    public function renderZoneForm(\WP_Post $post): void
    {
        $current  = (string) get_post_meta($post->ID, GabaritResolver::POSTMETA, true);
        $selected = $current !== '' ? $this->registry->byId($current) : null;
        if ($selected === null || !$selected->isZonal()) {
            return;
        }

        echo '<style>#postdivrich{display:none;}</style>';
        $contents = $this->zones->load($post->ID);
        echo '<div class="oli-zone-form" style="margin:1.5rem 0;">';
        echo '<h2 style="margin:0 0 0.25rem;">' . esc_html($selected->name) . '</h2>';
        echo '<p style="color:#50575e;margin:0 0 1rem;">' . esc_html__('Renseignez chaque zone. Les zones vides ne seront pas affichées.', 'oli-theme') . '</p>';
        foreach ($selected->zones as $zone) {
            $content = $contents[$zone->id] ?? new ZoneContent($zone->type);
            $this->renderZone($zone, $content);
        }
        echo '</div>';
    }
```

- [ ] **Step 4: Lancer le test, vérifier le succès**

Run: `vendor/bin/phpunit --filter GabaritMetaboxTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add src/Gabarits/Admin/GabaritMetabox.php tests/Unit/Gabarits/GabaritMetaboxTest.php
git commit -m "feat(gabarits): formulaire de zones en colonne principale, contenu natif masqué"
```

---

### Task 3 : Zone texte en rich-editor basique (`wp_editor`)

**Files:**
- Modify: `src/Gabarits/Admin/GabaritMetabox.php` (méthode `renderZone`, cas `ZoneType::Text`)
- Test: `tests/Unit/Gabarits/GabaritMetaboxTest.php` (ajouter un test)

**Interfaces:**
- Consumes: `wp_editor($content, $editorId, $settings)` (WordPress).
- Produces: rendu d'une zone texte via `wp_editor` avec `media_buttons => false`, `quicktags => false`, `tinymce.toolbar1 => 'bold,italic,bullist,numlist,link,unlink'`, `textarea_name => 'oli_gabarit_zone[{id}][text]'`, `textarea_rows => 8`.

- [ ] **Step 1: Écrire le test qui échoue**

Ajouter dans `tests/Unit/Gabarits/GabaritMetaboxTest.php` (et `use Mockery;` en tête) :

```php
    public function test_text_zone_uses_restricted_wp_editor(): void
    {
        Functions\when('get_post_meta')->alias(
            fn (int $id, string $key) => $key === '_oli_gabarit' ? 'triptyque' : '',
        );
        $registry = $this->createMock(GabaritRegistryInterface::class);
        $registry->method('byId')->willReturn(
            new Gabarit('triptyque', 'Triptyque', '', ['post'], '/s.css', null, false, '#000', [
                new Zone('intro', ZoneType::Text, 'Introduction'),
            ]),
        );
        $box = new GabaritMetabox($registry, new ZoneContentRepository());

        Functions\expect('wp_editor')->once()->with(
            '',
            'oli_zone_intro',
            \Mockery::on(static function (array $s): bool {
                return ($s['media_buttons'] ?? null) === false
                    && ($s['quicktags'] ?? null) === false
                    && ($s['textarea_name'] ?? '') === 'oli_gabarit_zone[intro][text]'
                    && ($s['tinymce']['toolbar1'] ?? '') === 'bold,italic,bullist,numlist,link,unlink';
            }),
        );

        ob_start();
        $box->renderZoneForm($this->post(7));
        ob_get_clean();
    }
```

> Retirer le `Functions\when('wp_editor')->justReturn(null);` de `setUp` si présent : ce test pose sa propre attente sur `wp_editor`. Garder le stub `justReturn` uniquement dans les autres tests qui touchent une zone texte. Pour éviter le conflit, déplacer le stub `wp_editor` du `setUp` vers chaque test qui n'en vérifie pas les arguments (`test_renders_zone_label_for_zonal_post`).

- [ ] **Step 2: Lancer le test, vérifier l'échec**

Run: `vendor/bin/phpunit --filter test_text_zone_uses_restricted_wp_editor`
Expected: FAIL — `wp_editor` n'est pas appelé (la zone texte rend encore un `<textarea>`).

- [ ] **Step 3: Implémenter — remplacer le textarea par `wp_editor`**

Dans `renderZone()`, remplacer le cas `ZoneType::Text` :

```php
            case ZoneType::Text:
                printf(
                    '<textarea name="%s[text]" rows="5" style="width:100%%;">%s</textarea>',
                    esc_attr($name),
                    esc_textarea($content->text),
                );
                break;
```

par :

```php
            case ZoneType::Text:
                // `oli_gabarit_zone[{id}]` → identifiant TinyMCE valide.
                $editorId = 'oli_zone_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($zone->id));
                wp_editor($content->text, $editorId, [
                    'textarea_name' => $name . '[text]',
                    'media_buttons' => false,
                    'quicktags'     => false,
                    'textarea_rows' => 8,
                    'tinymce'       => [
                        'toolbar1' => 'bold,italic,bullist,numlist,link,unlink',
                        'toolbar2' => '',
                    ],
                ]);
                break;
```

- [ ] **Step 4: Lancer le test, vérifier le succès**

Run: `vendor/bin/phpunit --filter GabaritMetaboxTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add src/Gabarits/Admin/GabaritMetabox.php tests/Unit/Gabarits/GabaritMetaboxTest.php
git commit -m "feat(gabarits): zone texte en éditeur enrichi basique (wp_editor)"
```

---

### Task 4 : Vérification globale + validation manuelle

**Files:** aucun (vérification).

- [ ] **Step 1: Suite complète verte**

Run: `vendor/bin/phpunit`
Expected: PASS, aucun test en échec. En particulier `tests/Unit/Core/SelfContainedThemeTest.php` vert.

- [ ] **Step 2: Analyse statique (si configurée)**

Run: `vendor/bin/phpstan analyse` (ignorer si la cible n'existe pas)
Expected: aucune nouvelle erreur sur `GabaritModule` / `GabaritMetabox`.

- [ ] **Step 3: Validation manuelle navigateur** (site dev sur http://localhost:8080)

Scénario :
1. Éditer une page, choisir le gabarit **Triptyque** (sidebar « Gabarit & zones »), enregistrer.
2. Au rechargement : Gutenberg a disparu, le champ contenu natif est masqué, le formulaire de zones (Introduction en éditeur enrichi B/I/listes/lien, Image héros avec picker, etc.) s'affiche sous le titre.
3. Saisir du texte enrichi + une image, enregistrer, vérifier le rendu front.
4. Repasser le gabarit sur **— Défaut —** (ou un gabarit CSS pur), enregistrer : Gutenberg revient normalement.

- [ ] **Step 4: Commit éventuel** (si un ajustement CSS/markup est nécessaire après validation)

```bash
git add -A
git commit -m "fix(gabarits): ajustements après validation navigateur"
```

---

## Auto-revue du plan

- **Couverture de la spec :** §1 détection zonale → existe déjà (constraint). §2 bascule éditeur → Task 1. §3 masquage contenu natif → Task 2 (style `#postdivrich`). §4 formulaire colonne principale + wp_editor → Tasks 2 & 3. §5 sélecteur sidebar conservé → Task 2 (render() conserve le sélecteur). §6 save/rendu inchangés → aucune modification de `handleSave`/`GabaritRenderer` (constraint « noms de champs inchangés »). Tests §1-5 → couverts par les tests des Tasks 1-3 + Task 4.
- **Placeholders :** aucun.
- **Cohérence des types :** `decideBlockEditor(bool, ?Gabarit): bool`, `renderZoneForm(\WP_Post): void`, `wp_editor(string, string, array)` — cohérents entre tâches.
