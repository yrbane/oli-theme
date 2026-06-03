<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gabarits;

use OliTheme\Gabarits\Gabarit;
use OliTheme\Gabarits\GabaritRegistry;
use PHPUnit\Framework\TestCase;

final class GabaritRegistryTest extends TestCase
{
    private string $tmp = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp = sys_get_temp_dir() . '/oli-gabarits-' . uniqid('', true);
        mkdir($this->tmp, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->tmp);
        parent::tearDown();
    }

    private function makeGabarit(string $slug, array $manifest, bool $withCss = true, bool $withJs = false): void
    {
        mkdir($this->tmp . '/' . $slug, 0o777, true);
        file_put_contents($this->tmp . '/' . $slug . '/manifest.json', (string) json_encode($manifest));
        if ($withCss) {
            file_put_contents($this->tmp . '/' . $slug . '/style.css', '/* css */');
        }
        if ($withJs) {
            file_put_contents($this->tmp . '/' . $slug . '/script.js', '// js');
        }
    }

    private function rmrf(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach ((array) scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->rmrf($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function test_returns_empty_when_root_missing(): void
    {
        $reg = new GabaritRegistry('/nonexistent', 'https://x');
        self::assertSame([], $reg->all());
    }

    public function test_loads_gabarit_from_manifest(): void
    {
        $this->makeGabarit('magazine', [
            'name'         => 'Magazine',
            'description'  => 'Deux colonnes.',
            'supports'     => ['post'],
            'parallax'     => false,
            'previewColor' => '#1e3a8a',
        ]);
        $reg = new GabaritRegistry($this->tmp, 'https://x/gabarits');

        $list = $reg->all();
        self::assertCount(1, $list);
        self::assertSame('magazine', $list[0]->id);
        self::assertSame('Magazine', $list[0]->name);
        self::assertSame(['post'], $list[0]->supports);
        self::assertSame('https://x/gabarits/magazine/style.css', $list[0]->cssPath);
        self::assertNull($list[0]->jsPath);
    }

    public function test_picks_up_optional_script_js(): void
    {
        $this->makeGabarit('cinema', ['name' => 'Cinema', 'parallax' => true], withCss: true, withJs: true);
        $reg = new GabaritRegistry($this->tmp, 'https://x/g');
        $g = $reg->byId('cinema');
        self::assertNotNull($g);
        self::assertSame('https://x/g/cinema/script.js', $g->jsPath);
        self::assertTrue($g->parallax);
    }

    public function test_ignores_dir_without_css(): void
    {
        $this->makeGabarit('broken', ['name' => 'Broken'], withCss: false);
        $reg = new GabaritRegistry($this->tmp, 'https://x');
        self::assertSame([], $reg->all());
    }

    public function test_for_type_filters_by_supports(): void
    {
        $this->makeGabarit('a', ['name' => 'A', 'supports' => ['post']]);
        $this->makeGabarit('b', ['name' => 'B', 'supports' => ['page']]);
        $this->makeGabarit('c', ['name' => 'C', 'supports' => ['post', 'page']]);
        $reg = new GabaritRegistry($this->tmp, 'https://x');

        $posts = array_map(static fn (Gabarit $g): string => $g->id, $reg->forType('post'));
        sort($posts);
        self::assertSame(['a', 'c'], $posts);
    }

    public function test_by_id_returns_null_when_unknown(): void
    {
        $reg = new GabaritRegistry($this->tmp, 'https://x');
        self::assertNull($reg->byId('inexistant'));
    }

    public function test_invalid_json_is_ignored(): void
    {
        mkdir($this->tmp . '/bad', 0o777, true);
        file_put_contents($this->tmp . '/bad/manifest.json', 'not json');
        file_put_contents($this->tmp . '/bad/style.css', '/* css */');
        $reg = new GabaritRegistry($this->tmp, 'https://x');
        self::assertSame([], $reg->all());
    }
}
