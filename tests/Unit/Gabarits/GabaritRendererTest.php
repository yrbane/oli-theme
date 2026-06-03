<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gabarits;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Gabarits\Gabarit;
use OliTheme\Gabarits\GabaritRenderer;
use OliTheme\Gabarits\Zone;
use OliTheme\Gabarits\ZoneContent;
use OliTheme\Gabarits\ZoneType;
use PHPUnit\Framework\TestCase;

final class GabaritRendererTest extends TestCase
{
    private string $tmp = '';

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('wp_kses_post')->returnArg(1);
        Functions\when('wp_get_attachment_image')->alias(fn (int $id) => '<img data-id="' . $id . '">');
        $this->tmp = sys_get_temp_dir() . '/oli-gabarit-render-' . uniqid('', true);
        mkdir($this->tmp, 0o777, true);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        if (is_dir($this->tmp)) {
            foreach ((array) scandir($this->tmp) as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    @unlink($this->tmp . '/' . $entry);
                }
            }
            @rmdir($this->tmp);
        }
        parent::tearDown();
    }

    private function makeGabarit(string $template): Gabarit
    {
        $path = $this->tmp . '/template.html.tpl';
        file_put_contents($path, $template);
        return new Gabarit(
            id: 'test',
            name: 'Test',
            description: '',
            supports: ['post'],
            cssPath: '/style.css',
            zones: [
                new Zone('intro',   ZoneType::Text,    'Intro'),
                new Zone('hero',    ZoneType::Image,   'Image'),
                new Zone('gallery', ZoneType::Gallery, 'Galerie'),
            ],
            templateFsPath: $path,
        );
    }

    public function test_returns_empty_when_no_custom_template(): void
    {
        $g = new Gabarit(id: 'x', name: 'X', description: '', supports: ['post'], cssPath: '/x.css');
        self::assertSame('', (new GabaritRenderer())->render($g, []));
    }

    public function test_renders_text_zone_via_wp_kses(): void
    {
        $g = $this->makeGabarit('<section>[[<?= $zones["intro"] ?? "" ?>]]</section>');
        $html = (new GabaritRenderer())->render($g, [
            'intro' => new ZoneContent(ZoneType::Text, text: '<p>Bonjour</p>'),
        ]);
        self::assertSame('<section>[[<p>Bonjour</p>]]</section>', $html);
    }

    public function test_renders_image_zone(): void
    {
        $g = $this->makeGabarit('<div><?= $zones["hero"] ?></div>');
        $html = (new GabaritRenderer())->render($g, [
            'hero' => new ZoneContent(ZoneType::Image, imageId: 42),
        ]);
        self::assertSame('<div><img data-id="42"></div>', $html);
    }

    public function test_renders_gallery_zone(): void
    {
        $g = $this->makeGabarit('<?= $zones["gallery"] ?>');
        $html = (new GabaritRenderer())->render($g, [
            'gallery' => new ZoneContent(ZoneType::Gallery, imageIds: [10, 20]),
        ]);
        self::assertStringContainsString('data-id="10"', $html);
        self::assertStringContainsString('data-id="20"', $html);
        self::assertStringContainsString('oli-zone-gallery', $html);
    }

    public function test_empty_zones_produce_empty_output(): void
    {
        $g = $this->makeGabarit('<?= $zones["intro"] ?? "[empty]" ?>');
        $html = (new GabaritRenderer())->render($g, [
            'intro' => new ZoneContent(ZoneType::Text, text: ''),
        ]);
        self::assertSame('', $html);
    }
}
