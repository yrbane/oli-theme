<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Editor;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Editor\JustifyBlockStyle;
use PHPUnit\Framework\TestCase;

/**
 * Tests du style de bloc « Justifié » pour core/paragraph et core/heading.
 *
 * @package OliTheme\Tests\Unit\Editor
 *
 * @since 1.8.0
 */
final class JustifyBlockStyleTest extends TestCase
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
        $module = new JustifyBlockStyle();
        $module->register();

        self::assertNotFalse(
            has_action('init', [$module, 'registerStyle']),
            'Le module doit s\'accrocher à init pour register_block_style.',
        );
    }

    public function test_register_style_targets_paragraph_and_heading_with_justified_name(): void
    {
        $calls = [];
        Functions\when('register_block_style')->alias(static function (string $blockName, array $descriptor) use (&$calls): void {
            $calls[] = [$blockName, $descriptor['name'], $descriptor['label']];
        });

        (new JustifyBlockStyle())->registerStyle();

        self::assertCount(2, $calls, 'Un style « justified » sur paragraph et heading.');
        self::assertSame('core/paragraph', $calls[0][0]);
        self::assertSame('justified', $calls[0][1]);
        self::assertIsString($calls[0][2]);
        self::assertNotEmpty($calls[0][2]);
        self::assertSame('core/heading', $calls[1][0]);
        self::assertSame('justified', $calls[1][1]);
    }
}
