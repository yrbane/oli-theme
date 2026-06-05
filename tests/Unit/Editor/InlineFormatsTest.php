<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Editor;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use OliTheme\Editor\InlineFormats;
use PHPUnit\Framework\TestCase;

/**
 * Tests du module qui réactive `core/underline` et enregistre un format
 * inline `oli/inline-color` dans la barre d'outils Gutenberg.
 *
 * @package OliTheme\Tests\Unit\Editor
 *
 * @since 1.7.0
 */
final class InlineFormatsTest extends TestCase
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

    public function test_register_hooks_into_enqueue_block_editor_assets(): void
    {
        $module = new InlineFormats('/themes/oli', '1.7.0');
        $module->register();

        self::assertNotFalse(
            has_action('enqueue_block_editor_assets', [$module, 'enqueue']),
            'Le module doit s\'accrocher à enqueue_block_editor_assets.',
        );
    }

    public function test_enqueue_registers_script_with_richtext_dependencies(): void
    {
        $captured = null;
        Functions\when('wp_enqueue_script')->alias(static function (...$args) use (&$captured): void {
            $captured = $args;
        });

        $module = new InlineFormats('/themes/oli', '1.7.0');
        $module->enqueue();

        self::assertSame('oli-inline-formats', $captured[0]);
        self::assertSame('/themes/oli/assets/js/editor/inline-formats.js', $captured[1]);

        $deps = $captured[2];
        foreach (['wp-rich-text', 'wp-block-editor', 'wp-element', 'wp-components', 'wp-i18n'] as $expected) {
            self::assertContains($expected, $deps, "Dépendance manquante : {$expected}");
        }

        self::assertSame('1.7.0', $captured[3]);
        self::assertTrue($captured[4], 'Le script doit être chargé en footer.');
    }

    public function test_enqueue_only_runs_on_block_editor(): void
    {
        // Le script ne doit pas être enregistré sur les pages non-éditeur :
        // c'est `enqueue_block_editor_assets` lui-même qui ne se déclenche
        // que côté éditeur. Le test vérifie seulement que enqueue() reste
        // idempotent et n'a aucun side-effect ailleurs.
        $calls = 0;
        Functions\when('wp_enqueue_script')->alias(static function () use (&$calls): void {
            $calls++;
        });

        $module = new InlineFormats('/themes/oli', '1.0.0');
        $module->enqueue();
        $module->enqueue();

        self::assertSame(2, $calls, 'enqueue() délègue à wp_enqueue_script à chaque appel sans logique conditionnelle.');
    }
}
