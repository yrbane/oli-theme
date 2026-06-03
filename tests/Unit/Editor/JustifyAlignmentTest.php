<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Editor;

use OliTheme\Editor\JustifyAlignment;
use PHPUnit\Framework\TestCase;

final class JustifyAlignmentTest extends TestCase
{
    public function test_adds_justify_to_paragraph_block(): void
    {
        $result = (new JustifyAlignment())->extendTextAlign(['name' => 'core/paragraph']);
        self::assertSame(
            ['left', 'center', 'right', 'justify'],
            $result['supports']['typography']['textAlign'],
        );
    }

    public function test_adds_justify_to_heading_block(): void
    {
        $result = (new JustifyAlignment())->extendTextAlign(['name' => 'core/heading']);
        self::assertContains('justify', $result['supports']['typography']['textAlign']);
    }

    public function test_adds_justify_to_list_blocks(): void
    {
        foreach (['core/list', 'core/list-item'] as $name) {
            $result = (new JustifyAlignment())->extendTextAlign(['name' => $name]);
            self::assertContains('justify', $result['supports']['typography']['textAlign'], "Bloc {$name}");
        }
    }

    public function test_does_not_touch_unrelated_blocks(): void
    {
        $original = ['name' => 'core/image', 'supports' => ['align' => ['wide', 'full']]];
        $result   = (new JustifyAlignment())->extendTextAlign($original);
        self::assertSame($original, $result);
    }

    public function test_preserves_existing_typography_supports(): void
    {
        $original = [
            'name' => 'core/paragraph',
            'supports' => [
                'typography' => [
                    'fontSize'  => true,
                    'lineHeight' => true,
                ],
            ],
        ];
        $result = (new JustifyAlignment())->extendTextAlign($original);
        self::assertTrue($result['supports']['typography']['fontSize']);
        self::assertTrue($result['supports']['typography']['lineHeight']);
        self::assertContains('justify', $result['supports']['typography']['textAlign']);
    }

    public function test_works_when_supports_missing(): void
    {
        $result = (new JustifyAlignment())->extendTextAlign(['name' => 'core/paragraph']);
        self::assertIsArray($result['supports']);
        self::assertIsArray($result['supports']['typography']);
    }
}
