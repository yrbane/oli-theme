<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gallery\Frontend;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Gallery\Frontend\VideoShortcode;
use OliTheme\Gallery\GalleryRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires du shortcode `[oli_video]` et du bloc Gutenberg
 * `oli/video` qui insèrent une vidéo YouTube responsive dans une page.
 *
 * @package OliTheme\Tests\Unit\Gallery\Frontend
 *
 * @since 1.6.0
 */
final class VideoShortcodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('esc_attr')->returnArg(1);
        Functions\when('esc_url')->returnArg(1);
        Functions\when('__')->returnArg(1);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Sans attribut `id`, le shortcode rend un placeholder explicite plutôt
     * qu'un rendu vide qui passerait inaperçu côté éditeur.
     */
    public function testShortcodeReturnsEmptyMessageWhenIdMissing(): void
    {
        $shortcode = new VideoShortcode(new GalleryRepository());

        $html = $shortcode->renderShortcode([]);

        self::assertStringContainsString('oli-video--empty', $html);
        self::assertStringContainsString('Aucune vidéo spécifiée', $html);
    }

    /**
     * Un attribut `id` avec une chaîne non-reconnue (ni 11 chars ni URL YT)
     * produit un message d'erreur — empêche l'embed d'un faux ID qui
     * afficherait une page d'erreur YouTube.
     */
    public function testShortcodeReturnsEmptyMessageWhenIdInvalid(): void
    {
        $shortcode = new VideoShortcode(new GalleryRepository());

        $html = $shortcode->renderShortcode(['id' => 'garbage']);

        self::assertStringContainsString('oli-video--empty', $html);
    }

    /**
     * Avec un ID YouTube brut (11 caractères), on rend un `<iframe>` dans un
     * wrapper responsive avec `aspect-ratio: 16/9` et l'URL nocookie pour
     * limiter le tracking.
     */
    public function testShortcodeRendersIframeFromRawId(): void
    {
        $shortcode = new VideoShortcode(new GalleryRepository());

        $html = $shortcode->renderShortcode(['id' => 'M85A64fB4Yo']);

        self::assertStringContainsString('<figure class="oli-video"', $html);
        self::assertStringContainsString('<iframe', $html);
        self::assertStringContainsString('https://www.youtube-nocookie.com/embed/M85A64fB4Yo', $html);
        self::assertStringContainsString('allowfullscreen', $html);
    }

    /**
     * L'ID est extrait des URLs YouTube classiques (watch?v=, youtu.be/).
     */
    public function testShortcodeExtractsIdFromYoutubeUrl(): void
    {
        $shortcode = new VideoShortcode(new GalleryRepository());

        $html = $shortcode->renderShortcode(['id' => 'https://youtu.be/dYQUEd9Em38']);

        self::assertStringContainsString('embed/dYQUEd9Em38', $html);
    }

    /**
     * L'attribut `caption` rend un `<figcaption>` sous la vidéo.
     */
    public function testShortcodeRendersCaptionWhenProvided(): void
    {
        $shortcode = new VideoShortcode(new GalleryRepository());

        $html = $shortcode->renderShortcode(['id' => 'M85A64fB4Yo', 'caption' => 'Démo Kalari']);

        self::assertStringContainsString('<figcaption class="oli-video__caption">Démo Kalari</figcaption>', $html);
    }

    /**
     * `autoplay="true"` ajoute `autoplay=1` à l'URL d'embed (et active mute
     * automatiquement — les navigateurs bloquent l'autoplay non muté).
     */
    public function testShortcodePropagatesAutoplayAttribute(): void
    {
        $shortcode = new VideoShortcode(new GalleryRepository());

        $html = $shortcode->renderShortcode(['id' => 'M85A64fB4Yo', 'autoplay' => 'true']);

        self::assertStringContainsString('autoplay=1', $html);
        self::assertStringContainsString('mute=1', $html);
    }

    /**
     * `aspect="4/3"` change l'aspect-ratio inline du wrapper. Permet d'insérer
     * des vidéos non-16:9 (verticales, carrées).
     */
    public function testShortcodePropagatesCustomAspectRatio(): void
    {
        $shortcode = new VideoShortcode(new GalleryRepository());

        $html = $shortcode->renderShortcode(['id' => 'M85A64fB4Yo', 'aspect' => '4/3']);

        self::assertStringContainsString('aspect-ratio:4/3', $html);
    }

    /**
     * Le bloc Gutenberg server-render appelle la même logique avec des
     * attributs typés (bool/string natifs au lieu de chaînes).
     */
    public function testBlockRendersWithTypedAttributes(): void
    {
        $shortcode = new VideoShortcode(new GalleryRepository());

        $html = $shortcode->renderBlock([
            'videoId'  => 'M85A64fB4Yo',
            'caption'  => '',
            'autoplay' => true,
            'aspect'   => '16/9',
        ]);

        self::assertStringContainsString('embed/M85A64fB4Yo', $html);
        self::assertStringContainsString('autoplay=1', $html);
    }
}
