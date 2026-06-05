<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Editor;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Editor\HierarchicalListStyle;
use PHPUnit\Framework\TestCase;

/**
 * Tests du style de bloc « Hiérarchique » pour core/list.
 *
 * @package OliTheme\Tests\Unit\Editor
 *
 * @since 1.7.0
 */
final class HierarchicalListStyleTest extends TestCase
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

    public function test_register_hooks_into_init(): void
    {
        $module = new HierarchicalListStyle();
        $module->register();

        self::assertNotFalse(
            has_action('init', [$module, 'registerStyle']),
            'Le module doit s\'accrocher à init pour register_block_style.',
        );
    }

    public function test_register_style_calls_wp_with_hierarchical_descriptor(): void
    {
        $captured = null;
        Functions\when('register_block_style')->alias(static function (...$args) use (&$captured): void {
            $captured = $args;
        });

        (new HierarchicalListStyle())->registerStyle();

        self::assertSame('core/list', $captured[0]);
        self::assertSame('hierarchical', $captured[1]['name']);
        self::assertIsString($captured[1]['label']);
        self::assertNotEmpty($captured[1]['label']);
    }

    public function test_register_style_targets_core_list_block_only(): void
    {
        $calls = [];
        Functions\when('register_block_style')->alias(static function (string $blockName, array $descriptor) use (&$calls): void {
            $calls[] = [$blockName, $descriptor['name']];
        });

        (new HierarchicalListStyle())->registerStyle();

        self::assertCount(1, $calls, 'Un seul style enregistré, sur core/list uniquement.');
        self::assertSame(['core/list', 'hierarchical'], $calls[0]);
    }
}
