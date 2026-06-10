<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MediaFolders\Frontend;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\MediaFolders\Frontend\FolderGalleryShortcode;
use OliTheme\MediaFolders\MediaFolderQuery;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires du shortcode `[oli_folder_gallery]` et du bloc Gutenberg
 * `oli/folder-gallery` qui partagent le même code de rendu.
 *
 * @package OliTheme\Tests\Unit\MediaFolders\Frontend
 *
 * @since 1.6.0
 */
final class FolderGalleryShortcodeTest extends TestCase
{
    /** @var array<int, array<string, mixed>>|null Capturé par les stubs `get_posts`. */
    private ?array $capturedGetPostsArgs = null;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('esc_attr')->returnArg(1);
        Functions\when('esc_url')->returnArg(1);
        Functions\when('__')->returnArg(1);
        Functions\when('taxonomy_exists')->justReturn(true);
        Functions\when('wp_get_attachment_image_url')
            ->alias(static fn (int $id, string $size = 'large'): string => "https://e/img-{$id}-{$size}.jpg");
        Functions\when('wp_get_attachment_image_srcset')
            ->alias(static fn (int $id): string => "https://e/img-{$id}-300.jpg 300w");
        Functions\when('get_post_meta')->justReturn('Alt');
        $this->capturedGetPostsArgs = null;
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Sans attribut `folder`, le shortcode retourne un message explicite —
     * pas une exception, pas un rendu vide qui passerait inaperçu côté éditeur.
     */
    public function testShortcodeReturnsEmptyMessageWhenFolderMissing(): void
    {
        Functions\when('get_posts')->justReturn([]);
        $shortcode = new FolderGalleryShortcode(new MediaFolderQuery());

        $html = $shortcode->renderShortcode([]);

        self::assertStringContainsString('oli-folder-gallery--empty', $html);
        self::assertStringContainsString('Aucun dossier spécifié', $html);
    }

    /**
     * Avec un slug pointant vers un dossier inexistant ou vide, message
     * explicite mentionnant le slug pour faciliter le debug côté éditeur.
     */
    public function testShortcodeReturnsEmptyMessageWhenFolderHasNoPhoto(): void
    {
        Functions\when('get_posts')->justReturn([]);
        $shortcode = new FolderGalleryShortcode(new MediaFolderQuery());

        $html = $shortcode->renderShortcode(['folder' => 'inconnu']);

        self::assertStringContainsString('oli-folder-gallery--empty', $html);
        self::assertStringContainsString('« inconnu »', $html);
    }

    /**
     * Avec un dossier non vide, on rend une `<section>` listant chaque photo
     * en `<li>` avec lien vers l'image originale et srcset si dispo.
     */
    public function testShortcodeRendersGallerySectionWithPhotos(): void
    {
        Functions\when('get_posts')->justReturn([
            (object) ['ID' => 1, 'post_excerpt' => 'Légende 1', 'post_title' => 'Titre 1'],
            (object) ['ID' => 2, 'post_excerpt' => '', 'post_title' => 'Titre 2'],
        ]);
        $shortcode = new FolderGalleryShortcode(new MediaFolderQuery());

        $html = $shortcode->renderShortcode(['folder' => 'voyages']);

        self::assertStringContainsString('<section class="oli-folder-gallery"', $html);
        self::assertStringContainsString('data-oli-folder="voyages"', $html);
        self::assertStringContainsString('href="https://e/img-1-large.jpg"', $html);
        self::assertStringContainsString('srcset="https://e/img-1-300.jpg 300w"', $html);
        self::assertStringContainsString('Légende 1', $html);
        self::assertSame(2, substr_count($html, 'oli-folder-gallery__item'));
    }

    /**
     * L'attribut optionnel `title` rend un `<h3>` au-dessus de la grille.
     */
    public function testShortcodeRendersTitleWhenProvided(): void
    {
        Functions\when('get_posts')->justReturn([
            (object) ['ID' => 1, 'post_excerpt' => '', 'post_title' => ''],
        ]);
        $shortcode = new FolderGalleryShortcode(new MediaFolderQuery());

        $html = $shortcode->renderShortcode(['folder' => 'voyages', 'title' => 'Mes voyages']);

        self::assertStringContainsString('<h3 class="oli-folder-gallery__title">Mes voyages</h3>', $html);
    }

    /**
     * `children="false"` se propage au query en `include_children = false`.
     */
    public function testShortcodePropagatesChildrenFalseAttribute(): void
    {
        Functions\when('get_posts')->alias(function (array $args): array {
            $this->capturedGetPostsArgs = $args;

            return [];
        });

        $shortcode = new FolderGalleryShortcode(new MediaFolderQuery());
        $shortcode->renderShortcode(['folder' => 'voyages', 'children' => 'false']);

        self::assertNotNull($this->capturedGetPostsArgs);
        self::assertFalse($this->capturedGetPostsArgs['tax_query'][0]['include_children']);
    }

    /**
     * `limit="5"` se propage au query en numberposts=5.
     */
    public function testShortcodePropagatesLimitAttribute(): void
    {
        Functions\when('get_posts')->alias(function (array $args): array {
            $this->capturedGetPostsArgs = $args;

            return [];
        });

        $shortcode = new FolderGalleryShortcode(new MediaFolderQuery());
        $shortcode->renderShortcode(['folder' => 'voyages', 'limit' => '5']);

        self::assertNotNull($this->capturedGetPostsArgs);
        self::assertSame(5, $this->capturedGetPostsArgs['numberposts']);
    }

    /**
     * Le bloc Gutenberg server-render reçoit des attributs typés (booléens
     * et int natifs) et appelle la même logique que le shortcode.
     */
    public function testBlockRendersWithTypedAttributes(): void
    {
        Functions\when('get_posts')->alias(function (array $args): array {
            $this->capturedGetPostsArgs = $args;

            return [];
        });

        $shortcode = new FolderGalleryShortcode(new MediaFolderQuery());
        $shortcode->renderBlock([
            'folder'          => 'voyages',
            'includeChildren' => false,
            'limit'           => 10,
            'title'           => '',
        ]);

        self::assertNotNull($this->capturedGetPostsArgs);
        self::assertSame(10, $this->capturedGetPostsArgs['numberposts']);
        self::assertFalse($this->capturedGetPostsArgs['tax_query'][0]['include_children']);
    }
}
