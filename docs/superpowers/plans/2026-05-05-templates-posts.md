# Templates & Posts/Pages Implementation Plan (oli-theme — Cycle 1, Plan 3/10)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the base Lunar template system (root layout, partials, page templates) and the generic Posts/Pages MVC module that converts WordPress requests into typed view-models rendered through Lunar. After this plan, the theme renders real pages, posts, archives, search and 404 with stylable HTML.

**Architecture:** A unique Lunar layout (`templates/layouts/base.html.tpl`) defines the HTML skeleton with overridable blocks (`head_extra`, `banner`, `before_main`, `main`, `after_main`, `footer_extra`). Each page template extends it. The `Posts` module exposes a generic `PostModel` reused for `page` and `post` post types. Two controllers (`PageController`, `PostController`) translate WP posts into immutable `PostEntity` DTOs and call `ViewRenderer`. The WordPress template hierarchy is bridged via 1-line `theme-bridge/*.php` files routing to the right controller. A minimal vanilla CSS layer (tokens + reset + base) makes the rendered HTML usable.

**Tech Stack:** Same as Plans 1-2 — PHP `^8.3`, WordPress 6.9+, Lunar Template (`yrbane/lunar-template`), PHPUnit 11 + Brain Monkey, PHPStan level 8, PHP-CS-Fixer (PSR-12).

**Reference spec:** `docs/superpowers/specs/2026-05-05-oli-theme-design.md` — sections 1.2-1.4 (architecture), 2.2 (Posts model), 3.1-3.7 (templates / vues / assets).

**Out of scope (later plans):** complete navigation menus (Plan Navigation), home carousel + slides CPT (Plan Slides), events CPT (Plan Events), full SEO module (Plan SEO), contact form (Plan Contact), Settings admin page (Plan Settings). The breadcrumbs partial in this plan is a **placeholder** rendering nothing on the front page; the proper `BreadcrumbsController` ships with Plan SEO.

---

## File Structure

This plan creates the following files (in execution order).

### Source (`src/Posts/`, namespace `OliTheme\Posts`)

- `src/Posts/PostEntity.php` — DTO immuable (`id`, `title`, `content`, `excerpt`, `slug`, `language`, `featuredImageUrl`, `permalink`, `publishedAt`, `author`)
- `src/Posts/PostModel.php` — modèle générique pour `page` et `post`
- `src/Posts/PageController.php` — controller pour pages WordPress
- `src/Posts/PostController.php` — controller pour posts standards (single + archive + search)
- `src/Posts/NotFoundController.php` — controller pour 404
- `src/Posts/PostsModule.php` — orchestrateur (template_include + enregistrement Container)

### Tests (`tests/Unit/Posts/`)

- `PostEntityTest.php`
- `PostModelTest.php`
- `PageControllerTest.php`
- `PostControllerTest.php`
- `NotFoundControllerTest.php`
- `PostsModuleTest.php`

### Tests d'intégration (`tests/Integration/`)

- `RenderEndToEndTest.php` — boot du thème + rendu d'une page WP simulée

### Templates Lunar (`templates/`)

- `templates/layouts/base.html.tpl`
- `templates/partials/header.html.tpl`
- `templates/partials/banner.html.tpl`
- `templates/partials/footer.html.tpl`
- `templates/partials/breadcrumbs.html.tpl` (placeholder)
- `templates/pages/page.html.tpl`
- `templates/pages/single-post.html.tpl`
- `templates/pages/archive-post.html.tpl`
- `templates/pages/search.html.tpl`
- `templates/pages/404.html.tpl`
- `templates/pages/front-page.html.tpl`

### Theme-bridge (`theme-bridge/`)

- `theme-bridge/page.php`
- `theme-bridge/single.php`
- `theme-bridge/archive.php`
- `theme-bridge/search.php`
- `theme-bridge/404.php`
- `theme-bridge/front-page.php`
- `theme-bridge/index.php` — déjà existant (Plan 1), il est mis à jour pour appeler `PostsModule::resolveFallback()`

### Assets (`assets/css/`)

- `assets/css/tokens.css`
- `assets/css/reset.css`
- `assets/css/base.css`
- `assets/css/main.css`

### Modifications

- `src/Theme.php` — branche `PostsModule` au boot ; expose le `Container` à `theme-bridge` via `Theme::container()`
- `src/Core/AssetManager.php` — `enqueueFront()` câble `main.css`
- `composer.json` — ajout (si nécessaire) de la couverture de `src/Posts/` dans phpunit (déjà couvert via paths génériques)

### Documentation

- `docs/templates.md` — guide développeur (arborescence Lunar, contrat de vue, helpers, BEM)
- `docs/decisions/0004-lunar-templates-and-bridge.md` — ADR (pourquoi un layout unique + bridge minimal)
- `CHANGELOG.md` — entrée `1.0.0-alpha.3`

---

## Conventions for every task (rappel)

- Code identifiers in **English**, PHPDoc and comments in **French**, commits in **French** (Conventional Commits : `feat(posts):`, `test(posts):`, `docs:`, `refactor:`, `chore(ci):`...).
- TDD strict (Red → Green → Refactor → Commit) per class.
- One commit per task minimum (split when a task contains a refactor sub-step).
- After each task : `composer ci` returns 0 (`XDEBUG_MODE=off composer ci` localement pour la rapidité).
- Tests Brain Monkey : chaque test isolé via `Monkey\setUp()` / `Monkey\tearDown()`.
- Lunar syntax (rappel) : `[[ var ]]` (escaped), `[[! var !]]` (raw), `[% block name %][% endblock %]`, `[% extends 'path' %]`, `[% include 'path' %]`, `[% if cond %][% endif %]`, `[# comment #]`, `##macro(args)##`.
- Pas de logique métier dans les templates : ils consomment des DTO et des scalaires.
- Pas d'appel direct aux fonctions WP (`get_post`, `the_content`...) dans les templates ni les controllers : passer par les models.

---

## Task 1: Working branch and warm-up

**Files:** none.

- [ ] **Step 1: Confirm working tree is clean and main is up to date**

```bash
cd /home/seb/Dev/olikalari.com
git status
git pull --ff-only origin main
```

Expected: `nothing to commit, working tree clean`. Branch at tag `v1.0.0-alpha.2-i18n` or later.

- [ ] **Step 2: Run the existing pipeline once to confirm baseline is green**

```bash
XDEBUG_MODE=off composer ci
```

Expected: `OK` from PHPUnit, no PHPStan errors, no PHP-CS-Fixer diffs.

- [ ] **Step 3: Create the directory skeleton**

```bash
mkdir -p src/Posts tests/Unit/Posts assets/css templates/partials templates/pages
```

`templates/layouts/` and `tests/Integration/` already exist.

- [ ] **Step 4: Commit the empty skeleton (with `.gitkeep`)**

```bash
touch src/Posts/.gitkeep tests/Unit/Posts/.gitkeep assets/css/.gitkeep
git add src/Posts/.gitkeep tests/Unit/Posts/.gitkeep assets/css/.gitkeep
git commit -m "chore(plan3): squelette de dossiers pour templates et posts"
```

---

## Task 2: `PostEntity` (DTO immuable)

**Files:**
- Create: `src/Posts/PostEntity.php`
- Test: `tests/Unit/Posts/PostEntityTest.php`

`PostEntity` is the immutable, framework-agnostic representation of a WP post or page. It carries everything the views need — never a `WP_Post` directly.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use DateTimeImmutable;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;
use PHPUnit\Framework\TestCase;

final class PostEntityTest extends TestCase
{
    public function testItExposesEveryConstructorPropertyAsReadonly(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $publishedAt = new DateTimeImmutable('2026-05-05T10:00:00+00:00');

        $entity = new PostEntity(
            id: 42,
            type: 'post',
            title: 'Bonjour',
            content: '<p>Hello</p>',
            excerpt: 'Short',
            slug: 'bonjour',
            language: $french,
            featuredImageUrl: 'https://example.com/img.jpg',
            featuredImageAlt: 'Une image',
            permalink: 'https://example.com/fr/bonjour',
            publishedAt: $publishedAt,
            updatedAt: null,
            author: 'Olivier',
        );

        self::assertSame(42, $entity->id);
        self::assertSame('post', $entity->type);
        self::assertSame('Bonjour', $entity->title);
        self::assertSame('<p>Hello</p>', $entity->content);
        self::assertSame('Short', $entity->excerpt);
        self::assertSame('bonjour', $entity->slug);
        self::assertSame($french, $entity->language);
        self::assertSame('https://example.com/img.jpg', $entity->featuredImageUrl);
        self::assertSame('Une image', $entity->featuredImageAlt);
        self::assertSame('https://example.com/fr/bonjour', $entity->permalink);
        self::assertSame($publishedAt, $entity->publishedAt);
        self::assertNull($entity->updatedAt);
        self::assertSame('Olivier', $entity->author);
    }

    public function testItAcceptsMissingOptionalFields(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $entity = new PostEntity(
            id: 1,
            type: 'page',
            title: 'Accueil',
            content: '',
            excerpt: null,
            slug: 'accueil',
            language: $french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/fr/',
            publishedAt: new DateTimeImmutable('2026-01-01'),
            updatedAt: null,
            author: null,
        );

        self::assertNull($entity->excerpt);
        self::assertNull($entity->featuredImageUrl);
        self::assertNull($entity->featuredImageAlt);
        self::assertNull($entity->author);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter PostEntityTest
```

Expected: FAIL — `Class OliTheme\Posts\PostEntity not found`.

- [ ] **Step 3: Implement `src/Posts/PostEntity.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use DateTimeImmutable;
use OliTheme\I18n\Language;

/**
 * DTO immuable représentant un contenu WordPress (page, post, ou autre CPT).
 *
 * Contient uniquement des scalaires, des DTO ou des objets de valeur.
 * Aucun template ne reçoit jamais d'objet `WP_Post` ; tous passent par cette
 * entité afin de découpler la couche de vues de WordPress.
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final readonly class PostEntity
{
    /**
     * @param int                    $id               Identifiant WP du contenu.
     * @param string                 $type             Type WP (`post`, `page`, ...).
     * @param string                 $title            Titre brut, déjà décodé.
     * @param string                 $content          HTML rendu du contenu (déjà filtré WP).
     * @param string|null            $excerpt          Extrait HTML, ou null.
     * @param string                 $slug             Slug URL.
     * @param Language               $language         Langue résolue du contenu.
     * @param string|null            $featuredImageUrl URL de l'image à la une (taille `large`).
     * @param string|null            $featuredImageAlt Alt de l'image à la une.
     * @param string                 $permalink        URL canonique.
     * @param DateTimeImmutable      $publishedAt      Date de publication.
     * @param DateTimeImmutable|null $updatedAt        Date de dernière mise à jour.
     * @param string|null            $author           Nom d'affichage de l'auteur.
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public string $content,
        public ?string $excerpt,
        public string $slug,
        public Language $language,
        public ?string $featuredImageUrl,
        public ?string $featuredImageAlt,
        public string $permalink,
        public DateTimeImmutable $publishedAt,
        public ?DateTimeImmutable $updatedAt,
        public ?string $author,
    ) {
    }
}
```

- [ ] **Step 4: Run tests to verify pass**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter PostEntityTest
```

Expected: PASS (2 tests, 14 assertions).

- [ ] **Step 5: Run static analysis and CS**

```bash
XDEBUG_MODE=off composer analyse
XDEBUG_MODE=off composer cs
```

Expected: no errors, no diffs.

- [ ] **Step 6: Commit**

```bash
git add src/Posts/PostEntity.php tests/Unit/Posts/PostEntityTest.php
git commit -m "feat(posts): ajoute PostEntity (DTO immuable de contenu WP)"
```

---

## Task 3: `PostModel` (find / findBySlug / findByLanguage / getMeta)

**Files:**
- Create: `src/Posts/PostModel.php`
- Test: `tests/Unit/Posts/PostModelTest.php`

The model abstracts every WP read needed by `Page`/`Post` views. It is generic: no `if ($type === 'post')` branching beyond the `WP_Query` filters.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\LanguageResolver;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModel;
use PHPUnit\Framework\TestCase;
use stdClass;

final class PostModelTest extends TestCase
{
    private Language $french;
    private LanguageResolver $resolver;
    private LanguageRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $this->resolver = $this->createMock(LanguageResolver::class);
        $this->registry = $this->createMock(LanguageRegistry::class);

        $this->resolver->method('current')->willReturn($this->french);
        $this->registry->method('default')->willReturn($this->french);
        $this->registry->method('get')->willReturn($this->french);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFindReturnsNullWhenPostMissing(): void
    {
        Functions\when('get_post')->justReturn(null);

        $model = new PostModel($this->resolver, $this->registry);

        self::assertNull($model->find(123));
    }

    public function testFindBuildsEntityFromWpPost(): void
    {
        $post = $this->buildWpPost(
            id: 42,
            type: 'post',
            title: 'Hello',
            content: '<p>Body</p>',
            excerpt: 'Short',
            slug: 'hello',
            date: '2026-05-05 10:00:00',
            modified: '2026-05-06 12:00:00',
            author: 7,
        );

        Functions\when('get_post')->justReturn($post);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_permalink')->justReturn('https://example.com/fr/hello');
        Functions\when('get_the_post_thumbnail_url')->justReturn('https://cdn/img.jpg');
        Functions\when('get_post_thumbnail_id')->justReturn(99);
        Functions\when('get_post_meta')->justReturn('Une image');
        Functions\when('get_the_author_meta')->justReturn('Olivier');
        Functions\when('mysql2date')->returnArg(2);
        Functions\when('wp_get_object_terms')->justReturn([(object) ['slug' => 'fr']]);

        $model = new PostModel($this->resolver, $this->registry);
        $entity = $model->find(42);

        self::assertInstanceOf(PostEntity::class, $entity);
        self::assertSame(42, $entity->id);
        self::assertSame('post', $entity->type);
        self::assertSame('Hello', $entity->title);
        self::assertSame('<p>Body</p>', $entity->content);
        self::assertSame('Short', $entity->excerpt);
        self::assertSame('hello', $entity->slug);
        self::assertSame('https://example.com/fr/hello', $entity->permalink);
        self::assertSame('https://cdn/img.jpg', $entity->featuredImageUrl);
        self::assertSame('Une image', $entity->featuredImageAlt);
        self::assertSame('Olivier', $entity->author);
    }

    public function testFindByLanguageReturnsArrayOfEntities(): void
    {
        $first = $this->buildWpPost(1, 'post', 'A', 'A', 'a', 'a', '2026-01-01', null, 1);
        $second = $this->buildWpPost(2, 'post', 'B', 'B', 'b', 'b', '2026-01-02', null, 1);

        Functions\when('get_posts')->justReturn([$first, $second]);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_permalink')->justReturn('https://example.com/');
        Functions\when('get_the_post_thumbnail_url')->justReturn(false);
        Functions\when('get_post_thumbnail_id')->justReturn(0);
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_the_author_meta')->justReturn('Author');
        Functions\when('mysql2date')->returnArg(2);
        Functions\when('wp_get_object_terms')->justReturn([(object) ['slug' => 'fr']]);

        $model = new PostModel($this->resolver, $this->registry);
        $items = $model->findByLanguage($this->french, 5);

        self::assertCount(2, $items);
        self::assertContainsOnlyInstancesOf(PostEntity::class, $items);
    }

    public function testGetMetaReturnsDefaultWhenAbsent(): void
    {
        Functions\when('get_post_meta')->justReturn('');

        $model = new PostModel($this->resolver, $this->registry);

        self::assertSame('default', $model->getMeta(1, '_oli_seo_title', 'default'));
    }

    public function testGetMetaReturnsExistingValue(): void
    {
        Functions\when('get_post_meta')->justReturn('Custom title');

        $model = new PostModel($this->resolver, $this->registry);

        self::assertSame('Custom title', $model->getMeta(1, '_oli_seo_title', 'fallback'));
    }

    private function buildWpPost(
        int $id,
        string $type,
        string $title,
        string $content,
        string $excerpt,
        string $slug,
        string $date,
        ?string $modified,
        int $author,
    ): stdClass {
        $post = new stdClass();
        $post->ID = $id;
        $post->post_type = $type;
        $post->post_title = $title;
        $post->post_content = $content;
        $post->post_excerpt = $excerpt;
        $post->post_name = $slug;
        $post->post_date_gmt = $date;
        $post->post_modified_gmt = $modified ?? '';
        $post->post_author = $author;
        $post->post_status = 'publish';

        return $post;
    }
}
```

- [ ] **Step 2: Run test to confirm failure**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter PostModelTest
```

Expected: FAIL — `Class OliTheme\Posts\PostModel not found`.

- [ ] **Step 3: Implement `src/Posts/PostModel.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use DateTimeImmutable;
use DateTimeZone;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\LanguageResolver;
use WP_Post;

/**
 * Modèle générique des contenus pages/posts du thème.
 *
 * Convertit les `WP_Post` en `PostEntity` immuables et expose des méthodes
 * de récupération typées. Aucun appel WP ne fuit hors de cette classe.
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class PostModel
{
    public function __construct(
        private readonly LanguageResolver $resolver,
        private readonly LanguageRegistry $registry,
    ) {
    }

    /**
     * Récupère une entité par son identifiant WP.
     */
    public function find(int $id): ?PostEntity
    {
        $post = \get_post($id);
        if (! $post instanceof WP_Post && ! \is_object($post)) {
            return null;
        }
        if (! isset($post->ID)) {
            return null;
        }

        return $this->hydrate($post);
    }

    /**
     * Récupère une entité par son slug et sa langue.
     */
    public function findBySlug(string $slug, Language $language, string $type = 'post'): ?PostEntity
    {
        $posts = \get_posts([
            'name' => $slug,
            'post_type' => $type,
            'post_status' => 'publish',
            'numberposts' => 1,
            'tax_query' => [[
                'taxonomy' => 'language',
                'field' => 'slug',
                'terms' => $language->code,
            ]],
        ]);

        if (empty($posts)) {
            return null;
        }

        return $this->hydrate($posts[0]);
    }

    /**
     * @return PostEntity[]
     */
    public function findByLanguage(Language $language, int $limit = 10, string $type = 'post'): array
    {
        $posts = \get_posts([
            'post_type' => $type,
            'post_status' => 'publish',
            'numberposts' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => [[
                'taxonomy' => 'language',
                'field' => 'slug',
                'terms' => $language->code,
            ]],
        ]);

        return array_values(array_map(fn (object $p): PostEntity => $this->hydrate($p), $posts));
    }

    /**
     * Lit une meta sous-jacente avec valeur par défaut.
     */
    public function getMeta(int $id, string $key, mixed $default = null): mixed
    {
        $value = \get_post_meta($id, $key, true);
        if ($value === '' || $value === false || $value === null) {
            return $default;
        }

        return $value;
    }

    /**
     * @param object $post WP_Post-like (testable via stdClass).
     */
    private function hydrate(object $post): PostEntity
    {
        $rawContent = (string) ($post->post_content ?? '');
        $rawExcerpt = (string) ($post->post_excerpt ?? '');

        $renderedContent = (string) \apply_filters('the_content', $rawContent);
        $renderedExcerpt = $rawExcerpt !== ''
            ? (string) \apply_filters('the_excerpt', $rawExcerpt)
            : null;

        $thumbnailUrl = \get_the_post_thumbnail_url($post->ID ?? 0, 'large');
        $thumbnailUrl = is_string($thumbnailUrl) && $thumbnailUrl !== '' ? $thumbnailUrl : null;

        $thumbnailId = (int) \get_post_thumbnail_id($post->ID ?? 0);
        $thumbnailAlt = $thumbnailId > 0
            ? (string) \get_post_meta($thumbnailId, '_wp_attachment_image_alt', true)
            : '';
        $thumbnailAlt = $thumbnailAlt !== '' ? $thumbnailAlt : null;

        $author = isset($post->post_author)
            ? (string) \get_the_author_meta('display_name', (int) $post->post_author)
            : null;
        $author = is_string($author) && $author !== '' ? $author : null;

        return new PostEntity(
            id: (int) ($post->ID ?? 0),
            type: (string) ($post->post_type ?? 'post'),
            title: (string) ($post->post_title ?? ''),
            content: $renderedContent,
            excerpt: $renderedExcerpt,
            slug: (string) ($post->post_name ?? ''),
            language: $this->resolveLanguage($post),
            featuredImageUrl: $thumbnailUrl,
            featuredImageAlt: $thumbnailAlt,
            permalink: (string) \get_permalink((int) ($post->ID ?? 0)),
            publishedAt: $this->parseDate((string) ($post->post_date_gmt ?? '')),
            updatedAt: $this->parseOptionalDate((string) ($post->post_modified_gmt ?? '')),
            author: $author,
        );
    }

    private function resolveLanguage(object $post): Language
    {
        $terms = \wp_get_object_terms((int) ($post->ID ?? 0), 'language', ['fields' => 'all']);
        if (\is_array($terms) && ! empty($terms)) {
            $first = $terms[0];
            $code = (string) ($first->slug ?? '');
            $language = $this->registry->get($code);
            if ($language instanceof Language) {
                return $language;
            }
        }

        return $this->registry->default();
    }

    private function parseDate(string $value): DateTimeImmutable
    {
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return new DateTimeImmutable('@0', new DateTimeZone('UTC'));
        }

        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }

    private function parseOptionalDate(string $value): ?DateTimeImmutable
    {
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
```

- [ ] **Step 4: Run tests**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter PostModelTest
```

Expected: PASS (5 tests).

- [ ] **Step 5: Run analyse + cs**

```bash
XDEBUG_MODE=off composer analyse
XDEBUG_MODE=off composer cs
```

If PHPStan complains about `wp_get_object_terms` return shape, add the WP stubs already included via `php-stubs/wordpress-stubs`.

- [ ] **Step 6: Commit**

```bash
git add src/Posts/PostModel.php tests/Unit/Posts/PostModelTest.php
git commit -m "feat(posts): ajoute PostModel générique (find/findBySlug/findByLanguage/getMeta)"
```

---

## Task 4: `PageController` (singular page)

**Files:**
- Create: `src/Posts/PageController.php`
- Test: `tests/Unit/Posts/PageControllerTest.php`

The controller fetches the current post id, asks `PostModel`, builds a view-model array, and calls `ViewRenderer::render('pages/page', $viewModel)`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageSwitcherController;
use OliTheme\I18n\LanguageSwitcherViewModel;
use OliTheme\Posts\PageController;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModel;
use PHPUnit\Framework\TestCase;

final class PageControllerTest extends TestCase
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

    public function testItRendersPageTemplateWithEntity(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $entity = new PostEntity(
            id: 7,
            type: 'page',
            title: 'À propos',
            content: '<p>Bio</p>',
            excerpt: null,
            slug: 'a-propos',
            language: $french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/fr/a-propos',
            publishedAt: new DateTimeImmutable('2026-01-01', new DateTimeZone('UTC')),
            updatedAt: null,
            author: null,
        );

        $model = $this->createMock(PostModel::class);
        $model->method('find')->with(7)->willReturn($entity);

        $resolver = $this->createMock(LanguageResolver::class);
        $resolver->method('current')->willReturn($french);

        $switcherVm = new LanguageSwitcherViewModel(items: []);
        $switcher = $this->createMock(LanguageSwitcherController::class);
        $switcher->method('build')->willReturn($switcherVm);

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/page',
                self::callback(function (array $vm) use ($entity, $switcherVm): bool {
                    return $vm['post'] === $entity
                        && $vm['languageSwitcher'] === $switcherVm
                        && $vm['bodyClasses'] === 'page page-id-7 lang-fr';
                }),
            )
            ->willReturn('<html>page</html>');

        Functions\when('get_queried_object_id')->justReturn(7);

        $controller = new PageController($model, $resolver, $switcher, $renderer);

        self::assertSame('<html>page</html>', $controller->renderSingular());
    }

    public function testItRenders404WhenPostMissing(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $model = $this->createMock(PostModel::class);
        $model->method('find')->willReturn(null);

        $resolver = $this->createMock(LanguageResolver::class);
        $resolver->method('current')->willReturn($french);

        $switcher = $this->createMock(LanguageSwitcherController::class);
        $switcher->method('build')->willReturn(new LanguageSwitcherViewModel([]));

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with('pages/404', self::isType('array'))
            ->willReturn('<html>404</html>');

        Functions\when('get_queried_object_id')->justReturn(0);

        $controller = new PageController($model, $resolver, $switcher, $renderer);

        self::assertSame('<html>404</html>', $controller->renderSingular());
    }
}
```

- [ ] **Step 2: Run test (FAIL)**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter PageControllerTest
```

- [ ] **Step 3: Implement `src/Posts/PageController.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageSwitcherController;

/**
 * Controller pour le rendu d'une page WordPress (singular `page`).
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class PageController
{
    public function __construct(
        private readonly PostModel $posts,
        private readonly LanguageResolver $resolver,
        private readonly LanguageSwitcherController $switcher,
        private readonly RendererInterface $renderer,
    ) {
    }

    /**
     * Rend la page singulière courante. Retourne du HTML prêt à imprimer.
     */
    public function renderSingular(): string
    {
        $id = (int) \get_queried_object_id();
        $entity = $id > 0 ? $this->posts->find($id) : null;

        if (! $entity instanceof PostEntity) {
            return $this->renderer->render('pages/404', $this->buildBaseViewModel());
        }

        return $this->renderer->render('pages/page', $this->buildViewModel($entity));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildViewModel(PostEntity $entity): array
    {
        $base = $this->buildBaseViewModel();
        $base['post'] = $entity;
        $base['bodyClasses'] = sprintf(
            'page page-id-%d lang-%s',
            $entity->id,
            $entity->language->code,
        );

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBaseViewModel(): array
    {
        return [
            'lang' => $this->resolver->current(),
            'languageSwitcher' => $this->switcher->build(),
            'bodyClasses' => 'lang-' . $this->resolver->current()->code,
        ];
    }
}
```

- [ ] **Step 4: Run tests (PASS)**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter PageControllerTest
```

- [ ] **Step 5: Analyse + CS, then commit**

```bash
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add src/Posts/PageController.php tests/Unit/Posts/PageControllerTest.php
git commit -m "feat(posts): ajoute PageController (rendu singulier des pages)"
```

---

## Task 5: `PostController` (single + archive + search)

**Files:**
- Create: `src/Posts/PostController.php`
- Test: `tests/Unit/Posts/PostControllerTest.php`

The post controller exposes three render methods: `renderSingle`, `renderArchive`, `renderSearch`. Same view-model shape as `PageController`, but archives carry a list of entities and pagination metadata.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageSwitcherController;
use OliTheme\I18n\LanguageSwitcherViewModel;
use OliTheme\Posts\PostController;
use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModel;
use PHPUnit\Framework\TestCase;

final class PostControllerTest extends TestCase
{
    private Language $french;
    private PostModel $model;
    private LanguageResolver $resolver;
    private LanguageSwitcherController $switcher;
    private RendererInterface $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $this->model = $this->createMock(PostModel::class);
        $this->resolver = $this->createMock(LanguageResolver::class);
        $this->switcher = $this->createMock(LanguageSwitcherController::class);
        $this->renderer = $this->createMock(RendererInterface::class);

        $this->resolver->method('current')->willReturn($this->french);
        $this->switcher->method('build')->willReturn(new LanguageSwitcherViewModel([]));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRenderSingleUsesEntity(): void
    {
        $entity = $this->buildEntity(11, 'post', 'Hello');

        Functions\when('get_queried_object_id')->justReturn(11);
        $this->model->method('find')->with(11)->willReturn($entity);

        $this->renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/single-post',
                self::callback(static fn (array $vm): bool => $vm['post'] instanceof PostEntity && $vm['post']->id === 11),
            )
            ->willReturn('<html>single</html>');

        $controller = new PostController($this->model, $this->resolver, $this->switcher, $this->renderer);

        self::assertSame('<html>single</html>', $controller->renderSingle());
    }

    public function testRenderArchiveListsEntities(): void
    {
        $first = $this->buildEntity(1, 'post', 'A');
        $second = $this->buildEntity(2, 'post', 'B');

        $this->model
            ->method('findByLanguage')
            ->with($this->french, 10)
            ->willReturn([$first, $second]);

        $this->renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/archive-post',
                self::callback(static function (array $vm): bool {
                    if (! is_array($vm['posts'])) {
                        return false;
                    }
                    return count($vm['posts']) === 2 && $vm['posts'][0]->id === 1;
                }),
            )
            ->willReturn('<html>archive</html>');

        $controller = new PostController($this->model, $this->resolver, $this->switcher, $this->renderer);

        self::assertSame('<html>archive</html>', $controller->renderArchive());
    }

    public function testRenderSearchExposesQuery(): void
    {
        Functions\when('get_search_query')->justReturn('yoga');
        $this->model
            ->method('findByLanguage')
            ->willReturn([]);

        $this->renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/search',
                self::callback(static fn (array $vm): bool => $vm['query'] === 'yoga' && $vm['posts'] === []),
            )
            ->willReturn('<html>search</html>');

        $controller = new PostController($this->model, $this->resolver, $this->switcher, $this->renderer);

        self::assertSame('<html>search</html>', $controller->renderSearch());
    }

    private function buildEntity(int $id, string $type, string $title): PostEntity
    {
        return new PostEntity(
            id: $id,
            type: $type,
            title: $title,
            content: '<p>x</p>',
            excerpt: null,
            slug: strtolower($title),
            language: $this->french,
            featuredImageUrl: null,
            featuredImageAlt: null,
            permalink: 'https://example.com/' . $id,
            publishedAt: new DateTimeImmutable('2026-01-01'),
            updatedAt: null,
            author: null,
        );
    }
}
```

- [ ] **Step 2: FAIL run**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter PostControllerTest
```

- [ ] **Step 3: Implement `src/Posts/PostController.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageSwitcherController;

/**
 * Controller pour les posts standards (single, archive, recherche).
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class PostController
{
    public function __construct(
        private readonly PostModel $posts,
        private readonly LanguageResolver $resolver,
        private readonly LanguageSwitcherController $switcher,
        private readonly RendererInterface $renderer,
    ) {
    }

    public function renderSingle(): string
    {
        $id = (int) \get_queried_object_id();
        $entity = $id > 0 ? $this->posts->find($id) : null;

        if (! $entity instanceof PostEntity) {
            return $this->renderer->render('pages/404', $this->buildBaseViewModel());
        }

        $viewModel = $this->buildBaseViewModel();
        $viewModel['post'] = $entity;
        $viewModel['bodyClasses'] = sprintf(
            'single single-post post-id-%d lang-%s',
            $entity->id,
            $entity->language->code,
        );

        return $this->renderer->render('pages/single-post', $viewModel);
    }

    public function renderArchive(int $limit = 10): string
    {
        $current = $this->resolver->current();
        $items = $this->posts->findByLanguage($current, $limit);

        $viewModel = $this->buildBaseViewModel();
        $viewModel['posts'] = $items;
        $viewModel['archiveTitle'] = '';
        $viewModel['bodyClasses'] = 'archive archive-post lang-' . $current->code;

        return $this->renderer->render('pages/archive-post', $viewModel);
    }

    public function renderSearch(int $limit = 20): string
    {
        $current = $this->resolver->current();
        $query = (string) \get_search_query();
        $items = $query === '' ? [] : $this->posts->findByLanguage($current, $limit);

        $viewModel = $this->buildBaseViewModel();
        $viewModel['query'] = $query;
        $viewModel['posts'] = $items;
        $viewModel['bodyClasses'] = 'search lang-' . $current->code;

        return $this->renderer->render('pages/search', $viewModel);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBaseViewModel(): array
    {
        $current = $this->resolver->current();

        return [
            'lang' => $current,
            'languageSwitcher' => $this->switcher->build(),
            'bodyClasses' => 'lang-' . $current->code,
        ];
    }
}
```

- [ ] **Step 4: PASS run**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter PostControllerTest
```

- [ ] **Step 5: Analyse + CS + commit**

```bash
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add src/Posts/PostController.php tests/Unit/Posts/PostControllerTest.php
git commit -m "feat(posts): ajoute PostController (single, archive, recherche)"
```

---

## Task 6: `NotFoundController` (404 dédié)

**Files:**
- Create: `src/Posts/NotFoundController.php`
- Test: `tests/Unit/Posts/NotFoundControllerTest.php`

A dedicated controller for 404 keeps `Page`/`Post` controllers focused. It also lets the bridge call it directly without simulating a "missing post".

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageSwitcherController;
use OliTheme\I18n\LanguageSwitcherViewModel;
use OliTheme\Posts\NotFoundController;
use PHPUnit\Framework\TestCase;

final class NotFoundControllerTest extends TestCase
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

    public function testItRendersFourOhFourTemplate(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');

        $resolver = $this->createMock(LanguageResolver::class);
        $resolver->method('current')->willReturn($french);

        $switcher = $this->createMock(LanguageSwitcherController::class);
        $switcher->method('build')->willReturn(new LanguageSwitcherViewModel([]));

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects(self::once())
            ->method('render')
            ->with(
                'pages/404',
                self::callback(static fn (array $vm): bool => $vm['bodyClasses'] === 'error404 lang-fr'),
            )
            ->willReturn('<html>404</html>');

        $controller = new NotFoundController($resolver, $switcher, $renderer);

        self::assertSame('<html>404</html>', $controller->render());
    }
}
```

- [ ] **Step 2: FAIL run**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter NotFoundControllerTest
```

- [ ] **Step 3: Implement `src/Posts/NotFoundController.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageSwitcherController;

/**
 * Controller dédié au rendu 404.
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class NotFoundController
{
    public function __construct(
        private readonly LanguageResolver $resolver,
        private readonly LanguageSwitcherController $switcher,
        private readonly RendererInterface $renderer,
    ) {
    }

    public function render(): string
    {
        $current = $this->resolver->current();

        return $this->renderer->render('pages/404', [
            'lang' => $current,
            'languageSwitcher' => $this->switcher->build(),
            'bodyClasses' => 'error404 lang-' . $current->code,
        ]);
    }
}
```

- [ ] **Step 4: PASS run + commit**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter NotFoundControllerTest
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add src/Posts/NotFoundController.php tests/Unit/Posts/NotFoundControllerTest.php
git commit -m "feat(posts): ajoute NotFoundController (rendu 404)"
```

---

## Task 7: `PostsModule` (orchestrateur)

**Files:**
- Create: `src/Posts/PostsModule.php`
- Test: `tests/Unit/Posts/PostsModuleTest.php`

`PostsModule` registers controllers in the Container so the theme-bridge files can resolve them. It does **not** hook `template_include` (each `theme-bridge/*.php` is the authoritative entry point — simpler and easier to debug than a single dispatcher).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use OliTheme\Container;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageSwitcherController;
use OliTheme\Posts\NotFoundController;
use OliTheme\Posts\PageController;
use OliTheme\Posts\PostController;
use OliTheme\Posts\PostModel;
use OliTheme\Posts\PostsModule;
use PHPUnit\Framework\TestCase;

final class PostsModuleTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->container = new Container();

        $this->container->set(RendererInterface::class, $this->createMock(RendererInterface::class));
        $this->container->set(LanguageResolver::class, $this->createMock(LanguageResolver::class));
        $this->container->set(LanguageRegistry::class, $this->createMock(LanguageRegistry::class));
        $this->container->set(LanguageSwitcherController::class, $this->createMock(LanguageSwitcherController::class));
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRegistersAllPostsServices(): void
    {
        $module = new PostsModule($this->container);
        $module->register();

        self::assertInstanceOf(PostModel::class, $this->container->get(PostModel::class));
        self::assertInstanceOf(PageController::class, $this->container->get(PageController::class));
        self::assertInstanceOf(PostController::class, $this->container->get(PostController::class));
        self::assertInstanceOf(NotFoundController::class, $this->container->get(NotFoundController::class));
    }

    public function testRegisterIsIdempotent(): void
    {
        $module = new PostsModule($this->container);
        $module->register();
        $module->register();

        self::assertInstanceOf(PostModel::class, $this->container->get(PostModel::class));
    }
}
```

- [ ] **Step 2: FAIL run**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter PostsModuleTest
```

- [ ] **Step 3: Implement `src/Posts/PostsModule.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageSwitcherController;

/**
 * Module Posts : enregistre le modèle générique et les controllers
 * page/post/404 dans le container, sans s'accrocher directement aux hooks
 * WordPress (les théma-bridges du dossier theme-bridge/ y répondent).
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class PostsModule implements ModuleInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        $container = $this->container;

        if (! $container->has(PostModel::class)) {
            $container->factory(
                PostModel::class,
                static fn (Container $c): PostModel => new PostModel(
                    $c->get(LanguageResolver::class),
                    $c->get(LanguageRegistry::class),
                ),
            );
        }

        if (! $container->has(PageController::class)) {
            $container->factory(
                PageController::class,
                static fn (Container $c): PageController => new PageController(
                    $c->get(PostModel::class),
                    $c->get(LanguageResolver::class),
                    $c->get(LanguageSwitcherController::class),
                    $c->get(RendererInterface::class),
                ),
            );
        }

        if (! $container->has(PostController::class)) {
            $container->factory(
                PostController::class,
                static fn (Container $c): PostController => new PostController(
                    $c->get(PostModel::class),
                    $c->get(LanguageResolver::class),
                    $c->get(LanguageSwitcherController::class),
                    $c->get(RendererInterface::class),
                ),
            );
        }

        if (! $container->has(NotFoundController::class)) {
            $container->factory(
                NotFoundController::class,
                static fn (Container $c): NotFoundController => new NotFoundController(
                    $c->get(LanguageResolver::class),
                    $c->get(LanguageSwitcherController::class),
                    $c->get(RendererInterface::class),
                ),
            );
        }
    }
}
```

> **Note:** if `Container::has()` doesn't exist yet, use `try { $container->get(...); } catch { ... }` or add `has()`. The Plan 1 implementation already has `has()` — verify with `grep -n 'function has' src/Container.php`. If absent, add `public function has(string $id): bool { return isset($this->factories[$id]) || isset($this->instances[$id]); }` and a tiny test in `ContainerTest`, then commit separately as `refactor(container): expose has()`.

- [ ] **Step 4: PASS run**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter PostsModuleTest
```

- [ ] **Step 5: Analyse + CS + commit**

```bash
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add src/Posts/PostsModule.php tests/Unit/Posts/PostsModuleTest.php
git commit -m "feat(posts): ajoute PostsModule (enregistrement des services posts)"
```

---

## Task 8: `templates/layouts/base.html.tpl` (root layout)

**Files:**
- Create: `templates/layouts/base.html.tpl`
- Test: extends an existing integration test or adds an inline assertion in `RenderEndToEndTest` (Task 21)

For Lunar templates we don't write PHPUnit tests directly per file (they are pure markup). They are validated through `RenderEndToEndTest` and visual inspection. Each task still verifies the file via `XDEBUG_MODE=off composer test:integration` (smoke).

- [ ] **Step 1: Create `templates/layouts/base.html.tpl`**

```html
[# Layout racine du thème oli-theme.
   Variables attendues:
     - lang             (Language)              langue courante
     - bodyClasses      (string)                classes <body>
     - languageSwitcher (LanguageSwitcherViewModel)
   Variables globales (injectées par ViewRenderer):
     - wpHead, wpFooter, siteName, siteUrl, homeUrl, themeUri, currentYear, charset
   Blocs surchargeables: head_extra, banner, before_main, main, after_main, footer_extra
#]
<!DOCTYPE html>
<html lang="[[ lang.code ]]" dir="[[ lang.direction ]]">
<head>
    <meta charset="[[ charset ]]">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>[[ siteName ]]</title>
    [[! wpHead !]]
    [% block head_extra %][% endblock %]
</head>
<body class="[[ bodyClasses ]]">
    <a class="skip-link" href="#main">Aller au contenu</a>
    [% block banner %]
        [% include 'partials/header.html.tpl' %]
    [% endblock %]
    [% block before_main %][% endblock %]
    <main id="main" class="site-main">
        [% block main %][% endblock %]
    </main>
    [% block after_main %][% endblock %]
    [% include 'partials/footer.html.tpl' %]
    [[! wpFooter !]]
    [% block footer_extra %][% endblock %]
</body>
</html>
```

- [ ] **Step 2: Smoke check via existing renderer test**

Add `tests/Unit/Core/ViewRendererBaseLayoutTest.php` if not yet covered:

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Core\ViewRenderer;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageSwitcherViewModel;
use PHPUnit\Framework\TestCase;

final class ViewRendererBaseLayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('wp_head')->justReturn('');
        Functions\when('wp_footer')->justReturn('');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('get_bloginfo')->justReturn('Oli');
        Functions\when('get_template_directory_uri')->justReturn('https://example.com/wp-content/themes/oli-theme');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testBaseLayoutRendersHtmlSkeleton(): void
    {
        $renderer = new ViewRenderer(__DIR__ . '/../../../templates');
        $renderer->setDefaultVariables([
            'wpHead' => '',
            'wpFooter' => '',
            'siteName' => 'Oli',
            'siteUrl' => 'https://example.com',
            'homeUrl' => 'https://example.com',
            'themeUri' => 'https://example.com/wp-content/themes/oli-theme',
            'currentYear' => '2026',
            'charset' => 'UTF-8',
        ]);

        $html = $renderer->render('layouts/base', [
            'lang' => new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr'),
            'bodyClasses' => 'home',
            'languageSwitcher' => new LanguageSwitcherViewModel([]),
        ]);

        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<html lang="fr" dir="ltr">', $html);
        self::assertStringContainsString('<body class="home">', $html);
        self::assertStringContainsString('<main id="main"', $html);
    }
}
```

- [ ] **Step 3: Run test**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter ViewRendererBaseLayoutTest
```

If it fails because `partials/header.html.tpl` or `partials/footer.html.tpl` don't exist yet, add empty placeholders:

```bash
echo '[# placeholder header #]' > templates/partials/header.html.tpl
echo '[# placeholder footer #]' > templates/partials/footer.html.tpl
```

Re-run; expected PASS.

- [ ] **Step 4: Commit**

```bash
git add templates/layouts/base.html.tpl templates/partials/header.html.tpl templates/partials/footer.html.tpl tests/Unit/Core/ViewRendererBaseLayoutTest.php
git commit -m "feat(templates): ajoute le layout racine Lunar et placeholders partials"
```

---

## Task 9: `partials/header.html.tpl` + `banner.html.tpl`

**Files:**
- Modify: `templates/partials/header.html.tpl`
- Create: `templates/partials/banner.html.tpl`

The header includes the banner (a hero image with site name link) and the language switcher.

- [ ] **Step 1: Write `templates/partials/banner.html.tpl`**

```html
[# Bannière du site (image + titre cliquable).
   Variables attendues:
     - homeUrl  (string)
     - siteName (string)
#]
<div class="banner" data-banner>
    <a class="banner__home" href="[[ homeUrl ]]" aria-label="[[ siteName ]] — accueil">
        <span class="banner__title">[[ siteName ]]</span>
    </a>
</div>
```

- [ ] **Step 2: Replace `templates/partials/header.html.tpl`**

```html
[# Header du thème.
   Variables attendues:
     - lang             (Language)
     - languageSwitcher (LanguageSwitcherViewModel)
   Variables globales: homeUrl, siteName.
#]
<header class="site-header" role="banner">
    [% include 'partials/banner.html.tpl' %]
    <nav class="site-nav" aria-label="Menu principal">
        [# Le menu principal sera injecté par le module Navigation (plan ultérieur).
           Pour l'instant, un lien d'accueil minimal. #]
        <ul class="site-nav__list">
            <li class="site-nav__item">
                <a class="site-nav__link" href="[[ homeUrl ]]">Accueil</a>
            </li>
        </ul>
    </nav>
    [% if languageSwitcher.items %]
        <ul class="language-switcher" aria-label="Changer de langue">
            [% for item in languageSwitcher.items %]
                <li class="language-switcher__item[% if item.isCurrent %] language-switcher__item--current[% endif %]">
                    <a href="[[ item.url ]]" hreflang="[[ item.code ]]" lang="[[ item.code ]]">
                        [[ item.label ]]
                    </a>
                </li>
            [% endfor %]
        </ul>
    [% endif %]
</header>
```

- [ ] **Step 3: Re-run the base layout test**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter ViewRendererBaseLayoutTest
```

Expected: PASS (the test still passes — layout includes the header without crashing).

- [ ] **Step 4: Commit**

```bash
git add templates/partials/header.html.tpl templates/partials/banner.html.tpl
git commit -m "feat(templates): header avec bannière, switcher de langue et nav minimale"
```

---

## Task 10: `partials/footer.html.tpl`

**Files:**
- Modify: `templates/partials/footer.html.tpl`

A minimal accessible footer. The proper, Settings-driven 3-column footer ships in Plan Settings.

- [ ] **Step 1: Replace `templates/partials/footer.html.tpl`**

```html
[# Pied de page minimal (la version pleine — mentions/réseaux/mini-menu —
   sera produite par le module Settings dans un plan ultérieur).
   Variables globales: siteName, currentYear.
#]
<footer class="site-footer" role="contentinfo">
    <div class="site-footer__inner">
        <p class="site-footer__copy">© [[ currentYear ]] [[ siteName ]]. Tous droits réservés.</p>
    </div>
</footer>
```

- [ ] **Step 2: Run integration tests**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter ViewRendererBaseLayoutTest
```

Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add templates/partials/footer.html.tpl
git commit -m "feat(templates): pied de page minimal avec copyright dynamique"
```

---

## Task 11: `partials/breadcrumbs.html.tpl` (placeholder)

**Files:**
- Create: `templates/partials/breadcrumbs.html.tpl`

A placeholder — `crumbs` is undefined for now; the partial protects itself.

- [ ] **Step 1: Create `templates/partials/breadcrumbs.html.tpl`**

```html
[# Fil d'Ariane.
   Sera alimenté par BreadcrumbsController dans le module SEO (plan ultérieur).
   Variable attendue: crumbs (array<{label, url, isCurrent}>) — peut être absente.
#]
[% if crumbs %]
<nav class="breadcrumbs" aria-label="Fil d'Ariane">
    <ol class="breadcrumbs__list">
        [% for crumb in crumbs %]
            <li class="breadcrumbs__item">
                [% if crumb.isCurrent %]
                    <span aria-current="page">[[ crumb.label ]]</span>
                [% else %]
                    <a href="[[ crumb.url ]]">[[ crumb.label ]]</a>
                [% endif %]
            </li>
        [% endfor %]
    </ol>
</nav>
[% endif %]
```

- [ ] **Step 2: Commit**

```bash
git add templates/partials/breadcrumbs.html.tpl
git commit -m "feat(templates): partial breadcrumbs (placeholder en attendant SEO)"
```

---

## Task 12: `pages/page.html.tpl`

**Files:**
- Create: `templates/pages/page.html.tpl`

Singular page template. Receives `post: PostEntity`.

- [ ] **Step 1: Create the file**

```html
[# Template de page singulière.
   Variables attendues: post (PostEntity), lang (Language), languageSwitcher.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <article class="page page--[[ post.slug ]]" lang="[[ post.language.code ]]">
        <header class="page__header">
            <h1 class="page__title">[[ post.title ]]</h1>
        </header>
        [% if post.featuredImageUrl %]
            <figure class="page__featured">
                <img
                    class="page__featured-image"
                    src="[[ post.featuredImageUrl ]]"
                    alt="[[ post.featuredImageAlt ]]"
                    loading="lazy">
            </figure>
        [% endif %]
        <div class="page__content">
            [[! post.content !]]
        </div>
    </article>
[% endblock %]
```

- [ ] **Step 2: Add `tests/Unit/Posts/PageTemplateRenderTest.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Core\ViewRenderer;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageSwitcherViewModel;
use OliTheme\Posts\PostEntity;
use PHPUnit\Framework\TestCase;

final class PageTemplateRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('wp_head')->justReturn('');
        Functions\when('wp_footer')->justReturn('');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('get_bloginfo')->justReturn('Oli');
        Functions\when('get_template_directory_uri')->justReturn('https://example.com');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRendersPageWithEntity(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $entity = new PostEntity(
            id: 7,
            type: 'page',
            title: 'À propos',
            content: '<p>Bio</p>',
            excerpt: null,
            slug: 'a-propos',
            language: $french,
            featuredImageUrl: 'https://example.com/img.jpg',
            featuredImageAlt: 'Photo',
            permalink: 'https://example.com/fr/a-propos',
            publishedAt: new DateTimeImmutable('2026-01-01'),
            updatedAt: null,
            author: null,
        );

        $renderer = new ViewRenderer(__DIR__ . '/../../../templates');
        $renderer->setDefaultVariables([
            'wpHead' => '', 'wpFooter' => '',
            'siteName' => 'Oli', 'siteUrl' => 'https://example.com', 'homeUrl' => 'https://example.com',
            'themeUri' => 'https://example.com', 'currentYear' => '2026', 'charset' => 'UTF-8',
        ]);

        $html = $renderer->render('pages/page', [
            'post' => $entity,
            'lang' => $french,
            'languageSwitcher' => new LanguageSwitcherViewModel([]),
            'bodyClasses' => 'page',
        ]);

        self::assertStringContainsString('À propos', $html);
        self::assertStringContainsString('<p>Bio</p>', $html);
        self::assertStringContainsString('https://example.com/img.jpg', $html);
        self::assertStringContainsString('lang="fr"', $html);
    }
}
```

- [ ] **Step 3: Run + commit**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter PageTemplateRenderTest
git add templates/pages/page.html.tpl tests/Unit/Posts/PageTemplateRenderTest.php
git commit -m "feat(templates): page singulière (page.html.tpl) + test de rendu"
```

---

## Task 13: `pages/single-post.html.tpl`

**Files:**
- Create: `templates/pages/single-post.html.tpl`

- [ ] **Step 1: Create file**

```html
[# Template d'article singulier.
   Variables attendues: post (PostEntity), lang, languageSwitcher.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <article class="post post--single post--id-[[ post.id ]]" lang="[[ post.language.code ]]">
        <header class="post__header">
            <h1 class="post__title">[[ post.title ]]</h1>
            <p class="post__meta">
                <time datetime="[[ post.publishedAt.format('c') ]]">
                    [[ post.publishedAt.format('d/m/Y') ]]
                </time>
                [% if post.author %]
                    <span class="post__author">— [[ post.author ]]</span>
                [% endif %]
            </p>
        </header>
        [% if post.featuredImageUrl %]
            <figure class="post__featured">
                <img class="post__featured-image"
                     src="[[ post.featuredImageUrl ]]"
                     alt="[[ post.featuredImageAlt ]]"
                     loading="lazy">
            </figure>
        [% endif %]
        <div class="post__content">
            [[! post.content !]]
        </div>
    </article>
[% endblock %]
```

- [ ] **Step 2: Add a small render test (mirror of Task 12) — file `tests/Unit/Posts/SinglePostTemplateRenderTest.php`**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Core\ViewRenderer;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageSwitcherViewModel;
use OliTheme\Posts\PostEntity;
use PHPUnit\Framework\TestCase;

final class SinglePostTemplateRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('wp_head')->justReturn('');
        Functions\when('wp_footer')->justReturn('');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRendersPostMetadata(): void
    {
        $french = new Language('fr', 'Français', 'Français', '🇫🇷', 'fr_FR', 'ltr');
        $entity = new PostEntity(
            id: 11, type: 'post', title: 'Hello', content: '<p>x</p>', excerpt: null,
            slug: 'hello', language: $french,
            featuredImageUrl: null, featuredImageAlt: null,
            permalink: 'https://example.com/fr/hello',
            publishedAt: new DateTimeImmutable('2026-05-05'),
            updatedAt: null, author: 'Olivier',
        );

        $renderer = new ViewRenderer(__DIR__ . '/../../../templates');
        $renderer->setDefaultVariables([
            'wpHead' => '', 'wpFooter' => '',
            'siteName' => 'Oli', 'siteUrl' => 'https://example.com', 'homeUrl' => 'https://example.com',
            'themeUri' => 'https://example.com', 'currentYear' => '2026', 'charset' => 'UTF-8',
        ]);

        $html = $renderer->render('pages/single-post', [
            'post' => $entity,
            'lang' => $french,
            'languageSwitcher' => new LanguageSwitcherViewModel([]),
            'bodyClasses' => 'single',
        ]);

        self::assertStringContainsString('05/05/2026', $html);
        self::assertStringContainsString('Olivier', $html);
        self::assertStringContainsString('Hello', $html);
    }
}
```

- [ ] **Step 3: Run + commit**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter SinglePostTemplateRenderTest
git add templates/pages/single-post.html.tpl tests/Unit/Posts/SinglePostTemplateRenderTest.php
git commit -m "feat(templates): article singulier (single-post.html.tpl) + test"
```

---

## Task 14: `pages/archive-post.html.tpl`

**Files:**
- Create: `templates/pages/archive-post.html.tpl`

- [ ] **Step 1: Create file**

```html
[# Template d'archive des posts.
   Variables: posts (PostEntity[]), archiveTitle (string), lang, languageSwitcher.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% include 'partials/breadcrumbs.html.tpl' %]
    <section class="archive archive-post">
        <header class="archive__header">
            <h1 class="archive__title">
                [% if archiveTitle %][[ archiveTitle ]][% else %]Actualités[% endif %]
            </h1>
        </header>
        [% if posts %]
            <ul class="archive__list">
                [% for post in posts %]
                    <li class="archive__item">
                        <article class="post post--card">
                            <h2 class="post__title">
                                <a href="[[ post.permalink ]]">[[ post.title ]]</a>
                            </h2>
                            <p class="post__meta">
                                <time datetime="[[ post.publishedAt.format('c') ]]">
                                    [[ post.publishedAt.format('d/m/Y') ]]
                                </time>
                            </p>
                            [% if post.excerpt %]
                                <div class="post__excerpt">[[! post.excerpt !]]</div>
                            [% endif %]
                        </article>
                    </li>
                [% endfor %]
            </ul>
        [% else %]
            <p class="archive__empty">Aucune publication pour le moment.</p>
        [% endif %]
    </section>
[% endblock %]
```

- [ ] **Step 2: Run integration smoke (re-runs full unit suite)**

```bash
XDEBUG_MODE=off composer test
```

Expected: green.

- [ ] **Step 3: Commit**

```bash
git add templates/pages/archive-post.html.tpl
git commit -m "feat(templates): archive des posts (archive-post.html.tpl)"
```

---

## Task 15: `pages/search.html.tpl`

**Files:**
- Create: `templates/pages/search.html.tpl`

- [ ] **Step 1: Create file**

```html
[# Template de page de résultats de recherche.
   Variables: query (string), posts (PostEntity[]), lang, languageSwitcher.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    <section class="search-results">
        <header class="search-results__header">
            <h1 class="search-results__title">
                Résultats pour
                <em>[[ query ]]</em>
            </h1>
        </header>
        <form class="search-form" role="search" method="get" action="[[ homeUrl ]]">
            <label class="search-form__label" for="search-input">Rechercher</label>
            <input id="search-input" class="search-form__input" type="search" name="s" value="[[ query ]]">
            <button class="search-form__submit" type="submit">Rechercher</button>
        </form>
        [% if posts %]
            <ul class="search-results__list">
                [% for post in posts %]
                    <li class="search-results__item">
                        <a href="[[ post.permalink ]]">[[ post.title ]]</a>
                    </li>
                [% endfor %]
            </ul>
        [% else %]
            <p class="search-results__empty">Aucun résultat.</p>
        [% endif %]
    </section>
[% endblock %]
```

- [ ] **Step 2: Commit**

```bash
git add templates/pages/search.html.tpl
git commit -m "feat(templates): page de recherche (search.html.tpl)"
```

---

## Task 16: `pages/404.html.tpl` and `front-page.html.tpl`

**Files:**
- Create: `templates/pages/404.html.tpl`
- Create: `templates/pages/front-page.html.tpl`

- [ ] **Step 1: Create `templates/pages/404.html.tpl`**

```html
[# Template 404 — variables: lang, languageSwitcher, homeUrl. #]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    <section class="error-404">
        <h1 class="error-404__title">Page introuvable</h1>
        <p class="error-404__text">La page que vous recherchez n'existe pas (ou plus).</p>
        <p>
            <a class="btn btn--primary" href="[[ homeUrl ]]">Retour à l'accueil</a>
        </p>
    </section>
[% endblock %]
```

- [ ] **Step 2: Create `templates/pages/front-page.html.tpl`**

```html
[# Template de page d'accueil.
   En attendant le carrousel (Plan Slides) et le SEO complet, cette page
   réutilise simplement le template `pages/page.html.tpl` lorsqu'une page
   d'accueil statique est définie. Si aucune page d'accueil statique n'est
   configurée, on délègue à l'archive des posts. Ce template sert de
   garde-fou minimal.
   Variables: post (PostEntity|null), posts (PostEntity[]|null), lang, languageSwitcher.
#]
[% extends 'layouts/base.html.tpl' %]

[% block main %]
    [% if post %]
        <article class="page page--front" lang="[[ post.language.code ]]">
            <h1 class="page__title">[[ post.title ]]</h1>
            <div class="page__content">[[! post.content !]]</div>
        </article>
    [% else %]
        <section class="front front--default">
            <h1 class="front__title">[[ siteName ]]</h1>
            <p class="front__lead">Bienvenue.</p>
        </section>
    [% endif %]
[% endblock %]
```

- [ ] **Step 3: Run full test suite + commit**

```bash
XDEBUG_MODE=off composer test
git add templates/pages/404.html.tpl templates/pages/front-page.html.tpl
git commit -m "feat(templates): pages 404 et accueil (front-page) minimales"
```

---

## Task 17: `theme-bridge/*.php` files

**Files:**
- Create: `theme-bridge/page.php`, `theme-bridge/single.php`, `theme-bridge/archive.php`, `theme-bridge/search.php`, `theme-bridge/404.php`, `theme-bridge/front-page.php`
- Modify: `theme-bridge/index.php` (existing) — fallback to `PostController::renderArchive()` instead of the static "site en construction"

Each bridge is **one logical line** : grab the container, resolve the controller, echo the result.

- [ ] **Step 1: Add `Theme::container()` accessor (if absent)**

Check existing file:

```bash
grep -n 'public static function container' src/Theme.php
```

If missing, edit `src/Theme.php` to add (alongside `boot()`) :

```php
/**
 * Expose le conteneur applicatif aux fichiers theme-bridge.
 *
 * @throws \RuntimeException si le thème n'a pas été démarré.
 */
public static function container(): Container
{
    if (! self::$container instanceof Container) {
        throw new \RuntimeException('Theme is not booted. Call Theme::boot() first.');
    }
    return self::$container;
}
```

Add a unit test in `tests/Unit/ThemeTest.php`:

```php
public function testContainerThrowsWhenNotBooted(): void
{
    \OliTheme\Theme::reset();

    $this->expectException(\RuntimeException::class);
    \OliTheme\Theme::container();
}

public function testContainerReturnsBootedContainer(): void
{
    \OliTheme\Theme::reset();
    \OliTheme\Theme::boot(__DIR__);

    self::assertInstanceOf(\OliTheme\Container::class, \OliTheme\Theme::container());
}
```

Run: `XDEBUG_MODE=off vendor/bin/phpunit --filter ThemeTest`. Adjust until green.

Commit:

```bash
git add src/Theme.php tests/Unit/ThemeTest.php
git commit -m "feat(theme): expose Theme::container() pour les pontages"
```

- [ ] **Step 2: Create `theme-bridge/page.php`**

```php
<?php

/**
 * Pontage WordPress → PageController pour les pages WP.
 *
 * @package OliTheme
 *
 * @since 1.0.0
 */

declare(strict_types=1);

use OliTheme\Posts\PageController;
use OliTheme\Theme;

echo Theme::container()->get(PageController::class)->renderSingular();
```

- [ ] **Step 3: Create `theme-bridge/single.php`**

```php
<?php

/**
 * Pontage WordPress → PostController pour les posts singuliers.
 *
 * @package OliTheme
 */

declare(strict_types=1);

use OliTheme\Posts\PostController;
use OliTheme\Theme;

echo Theme::container()->get(PostController::class)->renderSingle();
```

- [ ] **Step 4: Create `theme-bridge/archive.php`**

```php
<?php

/**
 * Pontage WordPress → PostController pour les archives.
 *
 * @package OliTheme
 */

declare(strict_types=1);

use OliTheme\Posts\PostController;
use OliTheme\Theme;

echo Theme::container()->get(PostController::class)->renderArchive();
```

- [ ] **Step 5: Create `theme-bridge/search.php`**

```php
<?php

/**
 * Pontage WordPress → PostController pour la recherche.
 *
 * @package OliTheme
 */

declare(strict_types=1);

use OliTheme\Posts\PostController;
use OliTheme\Theme;

echo Theme::container()->get(PostController::class)->renderSearch();
```

- [ ] **Step 6: Create `theme-bridge/404.php`**

```php
<?php

/**
 * Pontage WordPress → NotFoundController pour la 404.
 *
 * @package OliTheme
 */

declare(strict_types=1);

use OliTheme\Posts\NotFoundController;
use OliTheme\Theme;

echo Theme::container()->get(NotFoundController::class)->render();
```

- [ ] **Step 7: Create `theme-bridge/front-page.php`**

For the front page, we keep it dead simple : if a static page is set as the front page, `get_queried_object_id()` is non-zero and we delegate to `PageController`; otherwise we delegate to `PostController::renderArchive()` (latest news).

```php
<?php

/**
 * Pontage WordPress → page d'accueil.
 *
 * Si un article static est défini comme page d'accueil, on rend la page
 * comme une page WP standard. Sinon, on affiche l'archive des actualités.
 *
 * @package OliTheme
 */

declare(strict_types=1);

use OliTheme\Posts\PageController;
use OliTheme\Posts\PostController;
use OliTheme\Theme;

if (\get_queried_object_id() > 0) {
    echo Theme::container()->get(PageController::class)->renderSingular();
} else {
    echo Theme::container()->get(PostController::class)->renderArchive();
}
```

- [ ] **Step 8: Update `theme-bridge/index.php`**

Replace its previous "site en construction" content with the same delegation as `archive.php`.

```php
<?php

/**
 * Pontage WordPress générique (fallback).
 *
 * Délègue à `PostController::renderArchive()` afin d'éviter une page vide
 * lorsque WordPress ne trouve pas de template plus spécifique.
 *
 * @package OliTheme
 */

declare(strict_types=1);

use OliTheme\Posts\PostController;
use OliTheme\Theme;

echo Theme::container()->get(PostController::class)->renderArchive();
```

- [ ] **Step 9: PHPStan + CS + commit**

```bash
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add theme-bridge/
git commit -m "feat(bridge): pontages WP → controllers (page/single/archive/search/404/front)"
```

> If PHPStan can't analyse `theme-bridge/*.php` because they are not autoloaded, add `theme-bridge/` to `phpstan.neon` `paths` and add a stub for `Theme::container()` if needed (already exists once Step 1 is done).

---

## Task 18: Assets CSS (`tokens.css`, `reset.css`, `base.css`, `main.css`)

**Files:**
- Create: `assets/css/tokens.css`
- Create: `assets/css/reset.css`
- Create: `assets/css/base.css`
- Create: `assets/css/main.css`

Vanilla CSS, no build, BEM. Tokens drive colors/spacing/typography.

- [ ] **Step 1: Create `assets/css/tokens.css`**

```css
/* Design tokens du thème oli-theme.
   Variables CSS partagées pour couleurs, typographies, espacements,
   layout et transitions. Surchargées par site via Settings (plan ultérieur). */
:root {
    --color-primary: #c47a30;
    --color-secondary: #2a2a2a;
    --color-bg: #fdfaf3;
    --color-text: #1c1c1c;
    --color-muted: #6b6b6b;
    --color-link: #1f5b8d;
    --color-link-hover: #103a5b;

    --font-sans: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    --font-serif: Georgia, "Times New Roman", serif;
    --font-size-base: 1rem;
    --line-height-base: 1.6;

    --space-1: 0.25rem;
    --space-2: 0.5rem;
    --space-3: 0.75rem;
    --space-4: 1rem;
    --space-5: 1.5rem;
    --space-6: 2rem;
    --space-7: 3rem;

    --container-max: 80rem;
    --container-narrow: 48rem;

    --transition-fast: 120ms ease;
    --transition-base: 220ms ease;
}
```

- [ ] **Step 2: Create `assets/css/reset.css`**

```css
/* Reset minimal accessible. */
*, *::before, *::after { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }
body { margin: 0; }
img, picture, svg, video { display: block; max-width: 100%; height: auto; }
button, input, select, textarea { font: inherit; color: inherit; }
ul, ol { padding: 0; }
:focus-visible { outline: 2px solid var(--color-primary); outline-offset: 2px; }
.skip-link {
    position: absolute; left: -9999px; top: 0;
    background: var(--color-secondary); color: #fff; padding: var(--space-3);
}
.skip-link:focus { left: 0; }
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: 0ms !important; transition-duration: 0ms !important; }
}
```

- [ ] **Step 3: Create `assets/css/base.css`**

```css
/* Typographie et structure de base. */
body {
    font-family: var(--font-sans);
    font-size: var(--font-size-base);
    line-height: var(--line-height-base);
    color: var(--color-text);
    background: var(--color-bg);
}
a { color: var(--color-link); text-decoration: underline; }
a:hover, a:focus-visible { color: var(--color-link-hover); }
h1, h2, h3, h4 { font-family: var(--font-serif); line-height: 1.2; }
.site-main { max-width: var(--container-max); margin-inline: auto; padding: var(--space-5) var(--space-4); }
.site-header { padding: var(--space-4); border-bottom: 1px solid #00000010; }
.site-footer { padding: var(--space-5) var(--space-4); border-top: 1px solid #00000010; text-align: center; }
.banner__home { font-family: var(--font-serif); font-size: 1.5rem; text-decoration: none; color: inherit; }
.site-nav__list { list-style: none; display: flex; gap: var(--space-4); margin: var(--space-3) 0 0; }
.language-switcher { list-style: none; display: flex; gap: var(--space-3); margin: var(--space-3) 0 0; }
.language-switcher__item--current a { font-weight: 700; }
.archive__list, .search-results__list { list-style: none; display: grid; gap: var(--space-5); }
.btn {
    display: inline-block; padding: var(--space-3) var(--space-5);
    background: var(--color-primary); color: #fff; text-decoration: none;
    border-radius: 0.25rem; transition: background var(--transition-fast);
}
.btn:hover { background: var(--color-link-hover); }
@media (min-width: 48rem) {
    .site-header { display: flex; align-items: center; justify-content: space-between; gap: var(--space-5); }
    .site-nav__list { margin-top: 0; }
}
```

- [ ] **Step 4: Create `assets/css/main.css`**

```css
/* Point d'entrée CSS du thème. Importe tous les modules vanilla. */
@import url('./tokens.css');
@import url('./reset.css');
@import url('./base.css');
```

- [ ] **Step 5: Commit**

```bash
git add assets/css/
git commit -m "feat(assets): CSS de base (tokens, reset, base, main) en BEM vanilla"
```

---

## Task 19: `AssetManager::enqueueFront` câble `main.css`

**Files:**
- Modify: `src/Core/AssetManager.php`
- Modify: `tests/Unit/Core/AssetManagerTest.php`

Plan 1 created `AssetManager` with stubs. Now we wire `main.css` for real.

- [ ] **Step 1: Read existing test**

```bash
grep -n 'enqueueFront' src/Core/AssetManager.php tests/Unit/Core/AssetManagerTest.php
```

- [ ] **Step 2: Add a new test asserting the wp_enqueue_style call**

In `tests/Unit/Core/AssetManagerTest.php`, add:

```php
public function testEnqueueFrontEnqueuesMainCssWithFilemtimeVersion(): void
{
    $themePath = sys_get_temp_dir() . '/oli-theme-' . uniqid();
    mkdir($themePath . '/assets/css', 0777, true);
    file_put_contents($themePath . '/assets/css/main.css', '/* test */');
    $expectedVersion = (string) filemtime($themePath . '/assets/css/main.css');

    $captured = [];
    Functions\when('wp_enqueue_style')->alias(static function (string $handle, string $src, array $deps, string $ver) use (&$captured): void {
        $captured = [$handle, $src, $deps, $ver];
    });

    $manager = new AssetManager($themePath, 'https://example.com/wp-content/themes/oli-theme');
    $manager->enqueueFront();

    self::assertSame('oli-theme', $captured[0]);
    self::assertSame('https://example.com/wp-content/themes/oli-theme/assets/css/main.css', $captured[1]);
    self::assertSame([], $captured[2]);
    self::assertSame($expectedVersion, $captured[3]);

    unlink($themePath . '/assets/css/main.css');
    rmdir($themePath . '/assets/css');
    rmdir($themePath . '/assets');
    rmdir($themePath);
}
```

- [ ] **Step 3: Update `src/Core/AssetManager.php`** to fulfill the contract

If `enqueueFront` is currently empty or stubbed, set it to:

```php
public function enqueueFront(): void
{
    \wp_enqueue_style(
        'oli-theme',
        $this->themeUri . '/assets/css/main.css',
        [],
        $this->version('assets/css/main.css'),
    );
}

private function version(string $relativePath): string
{
    $absolute = $this->themePath . '/' . $relativePath;
    if (file_exists($absolute)) {
        $mtime = filemtime($absolute);
        if ($mtime !== false) {
            return (string) $mtime;
        }
    }

    return '1.0.0';
}
```

> Verify the constructor signature stores `$themePath` and `$themeUri`. If the existing constructor uses different property names, adapt accordingly — but keep the public contract `enqueueFront(): void` unchanged.

- [ ] **Step 4: Run + commit**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --filter AssetManagerTest
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add src/Core/AssetManager.php tests/Unit/Core/AssetManagerTest.php
git commit -m "feat(core): AssetManager::enqueueFront câble main.css avec versioning filemtime"
```

---

## Task 20: Wire `PostsModule` in `Theme::boot()`

**Files:**
- Modify: `src/Theme.php`
- Modify: `tests/Unit/ThemeTest.php`

- [ ] **Step 1: Update test in `ThemeTest.php`**

Add:

```php
public function testBootRegistersPostsModule(): void
{
    \OliTheme\Theme::reset();
    \OliTheme\Theme::boot(__DIR__);

    $container = \OliTheme\Theme::container();

    self::assertTrue($container->has(\OliTheme\Posts\PostModel::class));
    self::assertTrue($container->has(\OliTheme\Posts\PageController::class));
    self::assertTrue($container->has(\OliTheme\Posts\PostController::class));
    self::assertTrue($container->has(\OliTheme\Posts\NotFoundController::class));
}
```

- [ ] **Step 2: Update `src/Theme.php` `registerCoreHooks()`**

Add (next to the existing `(new I18nModule(...))->register()`):

```php
(new \OliTheme\Posts\PostsModule($container))->register();
```

- [ ] **Step 3: Run all tests + commit**

```bash
XDEBUG_MODE=off composer test
XDEBUG_MODE=off composer analyse && XDEBUG_MODE=off composer cs
git add src/Theme.php tests/Unit/ThemeTest.php
git commit -m "feat(theme): branche PostsModule au boot"
```

---

## Task 21: End-to-end integration test

**Files:**
- Create: `tests/Integration/RenderEndToEndTest.php`

Validates that, given a fully booted theme container, calling `PageController::renderSingular()` returns HTML containing the expected post title, layout, and language attributes — without touching any real WordPress install (Brain Monkey stubs).

- [ ] **Step 1: Create file**

```php
<?php

declare(strict_types=1);

namespace OliTheme\Tests\Integration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Posts\PageController;
use OliTheme\Theme;
use PHPUnit\Framework\TestCase;
use stdClass;

final class RenderEndToEndTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Theme::reset();
    }

    protected function tearDown(): void
    {
        Theme::reset();
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFullyBootedThemeRendersSingularPage(): void
    {
        $themePath = dirname(__DIR__, 2);

        Functions\when('wp_head')->justReturn('');
        Functions\when('wp_footer')->justReturn('');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('get_bloginfo')->justReturn('Oli');
        Functions\when('get_template_directory_uri')->justReturn('https://example.com/wp-content/themes/oli-theme');
        Functions\when('get_option')->justReturn(['enabled' => ['fr'], 'default' => 'fr']);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_permalink')->justReturn('https://example.com/fr/about');
        Functions\when('get_the_post_thumbnail_url')->justReturn(false);
        Functions\when('get_post_thumbnail_id')->justReturn(0);
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_the_author_meta')->justReturn('');
        Functions\when('mysql2date')->returnArg(2);
        Functions\when('wp_get_object_terms')->justReturn([(object) ['slug' => 'fr']]);

        $post = new stdClass();
        $post->ID = 99;
        $post->post_type = 'page';
        $post->post_title = 'À propos';
        $post->post_content = '<p>Bio</p>';
        $post->post_excerpt = '';
        $post->post_name = 'about';
        $post->post_date_gmt = '2026-05-05 10:00:00';
        $post->post_modified_gmt = '';
        $post->post_author = 1;
        $post->post_status = 'publish';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_queried_object_id')->justReturn(99);

        Theme::boot($themePath);
        $controller = Theme::container()->get(PageController::class);

        self::assertInstanceOf(PageController::class, $controller);
        $html = $controller->renderSingular();

        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('À propos', $html);
        self::assertStringContainsString('<p>Bio</p>', $html);
        self::assertStringContainsString('lang="fr"', $html);
    }
}
```

- [ ] **Step 2: Run integration tests**

```bash
XDEBUG_MODE=off vendor/bin/phpunit --testsuite=integration
```

Expected: PASS.

- [ ] **Step 3: Run full pipeline**

```bash
XDEBUG_MODE=off composer ci
```

Expected: green.

- [ ] **Step 4: Commit**

```bash
git add tests/Integration/RenderEndToEndTest.php
git commit -m "test(integration): rendu end-to-end d'une page singulière"
```

---

## Task 22: Documentation (`docs/templates.md` + ADR 0004)

**Files:**
- Create: `docs/templates.md`
- Create: `docs/decisions/0004-lunar-templates-and-bridge.md`

- [ ] **Step 1: Create `docs/templates.md`**

```markdown
# Templates

Le thème oli-theme utilise [Lunar Template](https://github.com/yrbane/lunar-template) comme moteur de vues. Les templates vivent dans `templates/` et sont strictement passifs : ils consomment des DTO, jamais des objets WordPress.

## Arborescence

- `templates/layouts/base.html.tpl` — layout racine, blocs surchargeables :
  `head_extra`, `banner`, `before_main`, `main`, `after_main`, `footer_extra`.
- `templates/partials/` — fragments réutilisables (`header`, `banner`, `footer`,
  `breadcrumbs`, `language-switcher`).
- `templates/pages/` — un fichier par type de rendu (page, single-post,
  archive-post, search, 404, front-page). Chaque fichier `extends`
  `layouts/base.html.tpl` et remplit le bloc `main`.

## Variables globales injectées

`ViewRenderer` injecte automatiquement :

| Variable        | Type     | Description                                           |
|-----------------|----------|-------------------------------------------------------|
| `wpHead`        | string   | Sortie capturée de `wp_head()`                        |
| `wpFooter`      | string   | Sortie capturée de `wp_footer()`                      |
| `lang`          | Language | Langue courante (`code`, `direction`, `nativeLabel`)  |
| `siteName`      | string   | Nom du site (`get_bloginfo('name')`)                  |
| `siteUrl`       | string   | URL du site (`home_url()`)                            |
| `homeUrl`       | string   | Alias de `siteUrl`                                    |
| `themeUri`      | string   | URL du thème actif                                    |
| `currentYear`   | string   | Année courante (`date('Y')`)                          |
| `charset`       | string   | Charset (`get_bloginfo('charset')`)                   |

## Contrat de vue

Une vue ne reçoit jamais de `WP_Post`. Elle reçoit :

- des **scalaires** (string, int, bool),
- des **arrays** (typés via PHPDoc dans le controller),
- des **DTO immuables** (`PostEntity`, `LanguageSwitcherViewModel`, `Language`).

Si une vue a besoin d'une donnée non disponible, c'est le **controller** qui doit l'ajouter au view-model — jamais la vue qui appelle WordPress.

## Convention BEM

Toutes les classes CSS suivent BEM : `.block`, `.block__element`, `.block--modifier`. Voir `assets/css/base.css` pour des exemples.

## Helpers

- `[[ var ]]` — interpolation échappée (HTML safe).
- `[[! var !]]` — interpolation brute (utiliser uniquement pour du HTML déjà filtré par WordPress).
- `[% if cond %][% endif %]`, `[% for x in xs %][% endfor %]` — contrôle.
- `[% extends 'layouts/base.html.tpl' %]` + `[% block main %][% endblock %]` — héritage.
- `[% include 'partials/header.html.tpl' %]` — inclusion.

## Ajouter un nouveau template

1. Créer `templates/pages/mon-template.html.tpl` qui `extends` `layouts/base.html.tpl`.
2. Créer un controller dans `src/<Module>/MyController.php` qui construit le view-model.
3. Créer un fichier `theme-bridge/<wp-template>.php` qui invoque le controller.
4. Écrire un test PHPUnit (rendu + assertions sur le HTML produit).
```

- [ ] **Step 2: Create `docs/decisions/0004-lunar-templates-and-bridge.md`**

```markdown
# ADR 0004 — Layout Lunar unique + bridge minimal

**Statut :** accepté
**Date :** 2026-05-05
**Contexte :** Plan 3 — Templates et Posts/Pages.

## Décision

1. **Un seul layout racine** : `templates/layouts/base.html.tpl`. Chaque template de page l'`extends`.
2. **Pontage WordPress minimal** : un fichier PHP par entrée de la WP template hierarchy (`page.php`, `single.php`, `archive.php`, `search.php`, `404.php`, `front-page.php`, `index.php`). Chaque pontage tient en une ligne logique : `echo Theme::container()->get(<Controller>::class)->render*()`.

## Alternatives rejetées

- **Plusieurs layouts (`layouts/full.html.tpl`, `layouts/narrow.html.tpl`)** : reporté — pas de besoin avéré au cycle 1, ajoute de la complexité avant qu'elle soit utile (YAGNI).
- **Dispatcher unique via `template_include`** : fonctionnel mais opaque — multiplie les hooks à mocker dans les tests, et les pontages PHP individuels sont plus lisibles pour les développeurs WordPress.

## Conséquences

- ✅ Lecture immédiate : "quel template WP rend `single.php` ? → `theme-bridge/single.php` → `PostController::renderSingle()`".
- ✅ Tests unitaires faciles : on instancie le controller et on vérifie le HTML.
- ✅ Évolutivité : pour ajouter un type d'écran (par exemple `single-oli_event.php`), on ajoute un fichier de pontage et un controller.
- ❌ Léger duplicata entre pontages (pattern `echo Theme::container()->get(...)`) — accepté pour la lisibilité.
```

- [ ] **Step 3: Commit**

```bash
git add docs/templates.md docs/decisions/0004-lunar-templates-and-bridge.md
git commit -m "docs: guide templates Lunar + ADR 0004 (layout unique + bridge minimal)"
```

---

## Task 23: Changelog `1.0.0-alpha.3` + tag + push

**Files:**
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Add entry to `CHANGELOG.md`**

Insert (just under `## [Unreleased]`) :

```markdown
## [1.0.0-alpha.3] - 2026-05-05

### Added (Plan 3 — Templates & Posts/Pages)

- `Posts\PostEntity` — DTO immuable du contenu WP.
- `Posts\PostModel` — modèle générique (find, findBySlug, findByLanguage, getMeta).
- `Posts\PageController` — rendu singulier des pages.
- `Posts\PostController` — rendu single, archive et recherche des posts.
- `Posts\NotFoundController` — rendu 404.
- `Posts\PostsModule` — enregistrement des services posts dans le container.
- Layout racine Lunar `templates/layouts/base.html.tpl` + partials (`header`,
  `banner`, `footer`, `breadcrumbs`).
- Templates de pages : `page`, `single-post`, `archive-post`, `search`, `404`,
  `front-page`.
- Pontages WordPress : `theme-bridge/{page,single,archive,search,404,front-page,index}.php`.
- `Core\AssetManager::enqueueFront` câble `main.css` avec versioning `filemtime`.
- `Theme::container()` exposé pour les pontages.
- ADR 0004 — layout Lunar unique + bridge minimal.
- Guide développeur `docs/templates.md`.
- Couche CSS de base : `tokens`, `reset`, `base`, `main`.
- Test d'intégration end-to-end (`tests/Integration/RenderEndToEndTest.php`).
```

- [ ] **Step 2: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs: changelog 1.0.0-alpha.3 (livraison Plan 3 - Templates & Posts/Pages)"
```

- [ ] **Step 3: Final pipeline check**

```bash
XDEBUG_MODE=off composer ci
```

Expected: green.

- [ ] **Step 4: Tag and push**

```bash
git tag -a v1.0.0-alpha.3-templates -m "Release 1.0.0-alpha.3 — Plan 3 (Templates & Posts/Pages)"
git push origin main
git push origin v1.0.0-alpha.3-templates
```

---

## Definition of Done — Plan 3 (Templates & Posts/Pages)

This plan is **done** when ALL of the following are true :

1. ✅ `composer ci` returns 0 (`cs` + `phpstan` + all unit + integration tests pass)
2. ✅ Coverage ≥ 90 % on `src/Posts/`
3. ✅ All 23 tasks committed
4. ✅ Tag `v1.0.0-alpha.3-templates` posed
5. ✅ Pushed to `origin/main`
6. ✅ CI workflow green on GitHub Actions for PHP 8.3 / 8.4 / 8.5
7. ✅ ADR 0004 + `docs/templates.md` present
8. ✅ `PostsModule` registered in `Theme::boot()` and resolves all Posts services from the Container
9. ✅ Theme renders a real page, post, archive, search and 404 in WordPress 6.9+ (manual smoke test : visit `/`, `/sample-page/`, `/?s=test`, `/inexistant`)

When all 9 boxes are ticked, **Plan 4 (Navigation menus)** can start.

---

## Self-Review

### 1. Spec coverage (Plan 3 — Templates & Posts/Pages)

| Spec section | Couvert ? | Tâches |
|--------------|-----------|--------|
| 1.2 Arborescence templates/, src/Posts/, theme-bridge/, assets/ | ✅ | T1, T8-T18 |
| 1.4 Pattern de module (`PostsModule implements ModuleInterface`) | ✅ | T7 |
| 2.2 PostModel générique + PostEntity DTO | ✅ | T2, T3 |
| 3.1 Hiérarchie d'héritage Lunar (`base` + blocs) | ✅ | T8 |
| 3.2 Contrat de vue (DTO/scalaires uniquement) | ✅ | T2, T3, T4, T5, ADR 0004 |
| 3.3 Template Resolver (pontages WP) | ✅ | T17 |
| 3.4 Partials et composants (header, banner, footer, breadcrumbs) | ✅ | T9-T11 |
| 3.5 CSS BEM avec tokens | ✅ | T18 |
| 3.7 AssetManager::enqueueFront avec filemtime | ✅ | T19 |
| 3.8 Performance/A11y (skip-link, prefers-reduced-motion, lazy images) | ✅ | T8, T18 |

### 2. Placeholder scan

Aucun "TODO", "TBD", ni "implement later" laissé sans code attaché. Le partial `breadcrumbs.html.tpl` est explicitement documenté comme placeholder en attendant Plan SEO — c'est une décision, pas un trou. Le menu de navigation principal est volontairement réduit à un lien d'accueil et documenté ainsi.

### 3. Type consistency

- `PostEntity` props : `id, type, title, content, excerpt, slug, language, featuredImageUrl, featuredImageAlt, permalink, publishedAt, updatedAt, author` — utilisées identiquement dans T2, T3, T4, T5, T6, T12, T13.
- `PostModel::find/findBySlug/findByLanguage/getMeta` — signatures cohérentes T3 ↔ T7 ↔ T20 ↔ T21.
- `PageController::renderSingular` / `PostController::renderSingle/renderArchive/renderSearch` / `NotFoundController::render` — toutes utilisées telles que définies dans les pontages T17.
- `Theme::container()` — défini en T17 step 1, utilisé dans tous les pontages T17 step 2-7.
- `RendererInterface::render($name, $vars)` — utilisé tel que défini en Plan 1, retournant `string`.

### 4. Scope

Plan 3 = Templates Lunar + module Posts/Pages générique + pontages WP + CSS de base. Aucune fonctionnalité Navigation/Slides/Events/SEO/Contact/Settings n'a fuité — bien découpé. Livre un thème qui rend de vraies pages, posts, archives, recherche et 404.

---

## Next Step

Plan 4 (Navigation menus) — `MenuItemEntity`, `MenuModel`, `MenuWalker` BEM, `MenuController` (primary + footer par langue), tests. Le partial `nav-desktop.html.tpl` / `nav-mobile.html.tpl` viendra avec le module, ainsi qu'un script JS `menu-mobile.js`.
