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
        $post = new \WP_Post();
        $post->ID = $id;
        $post->post_type = 'post';
        return $post;
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
