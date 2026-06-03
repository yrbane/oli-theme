<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Settings;

use OliTheme\Settings\TypographySettings;
use PHPUnit\Framework\TestCase;

final class TypographySettingsTest extends TestCase
{
    public function test_default_returns_sensible_values(): void
    {
        $t = TypographySettings::default();
        self::assertSame(1.0, $t->baseSize);
        self::assertSame(1.2, $t->scaleRatio);
        self::assertSame(0.875, $t->menuSize);
        self::assertSame(0.875, $t->footerSize);
    }

    public function test_from_input_clamps_out_of_range_values(): void
    {
        $t = TypographySettings::fromInput([
            'baseSize' => 5.0,
            'scaleRatio' => 0.5,
            'menuSize' => 99,
            'footerSize' => 0.1,
        ]);
        self::assertSame(TypographySettings::BASE_MAX, $t->baseSize);
        self::assertSame(TypographySettings::RATIO_MIN, $t->scaleRatio);
        self::assertSame(TypographySettings::AUX_MAX, $t->menuSize);
        self::assertSame(TypographySettings::AUX_MIN, $t->footerSize);
    }

    public function test_from_input_keeps_in_range_values(): void
    {
        $t = TypographySettings::fromInput([
            'baseSize' => 1.1,
            'scaleRatio' => 1.25,
            'menuSize' => 0.9,
            'footerSize' => 0.8,
        ]);
        self::assertSame(1.1, $t->baseSize);
        self::assertSame(1.25, $t->scaleRatio);
        self::assertSame(0.9, $t->menuSize);
        self::assertSame(0.8, $t->footerSize);
    }

    public function test_to_css_includes_all_custom_properties(): void
    {
        $t = TypographySettings::default();
        $css = $t->toCss();

        self::assertStringContainsString('--font-size-base:1rem', $css);
        self::assertStringContainsString('--font-size-h1:', $css);
        self::assertStringContainsString('--font-size-h2:', $css);
        self::assertStringContainsString('--font-size-h3:', $css);
        self::assertStringContainsString('--font-size-h4:', $css);
        self::assertStringContainsString('--font-size-h5:', $css);
        self::assertStringContainsString('--font-size-h6:', $css);
        self::assertStringContainsString('--font-size-menu:', $css);
        self::assertStringContainsString('--font-size-footer:', $css);
    }

    public function test_to_css_scale_produces_increasing_sizes(): void
    {
        $t = new TypographySettings(baseSize: 1.0, scaleRatio: 1.2);
        $css = $t->toCss();
        // h6 = 1.0 * 1.2 = 1.2 ; h1 = 1.2^6 ≈ 2.986
        self::assertStringContainsString('--font-size-h6:1.2rem', $css);
        self::assertStringContainsString('--font-size-h1:2.986rem', $css);
    }
}
