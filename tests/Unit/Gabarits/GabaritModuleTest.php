<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gabarits;

use Brain\Monkey;
use OliTheme\Container;
use OliTheme\Gabarits\Gabarit;
use OliTheme\Gabarits\GabaritModule;
use OliTheme\Gabarits\Zone;
use OliTheme\Gabarits\ZoneType;
use PHPUnit\Framework\TestCase;

final class GabaritModuleTest extends TestCase
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

    private function zonalGabarit(): Gabarit
    {
        return new Gabarit('triptyque', 'Triptyque', '', ['post'], '/s.css', null, false, '#000', [
            new Zone('intro', ZoneType::Text, 'Introduction'),
        ]);
    }

    private function cssOnlyGabarit(): Gabarit
    {
        return new Gabarit('magazine', 'Magazine', '', ['post'], '/s.css');
    }

    public function test_disables_block_editor_for_zonal_gabarit(): void
    {
        $module = new GabaritModule(new Container());
        self::assertFalse($module->decideBlockEditor(true, $this->zonalGabarit()));
    }

    public function test_keeps_block_editor_for_css_only_gabarit(): void
    {
        $module = new GabaritModule(new Container());
        self::assertTrue($module->decideBlockEditor(true, $this->cssOnlyGabarit()));
    }

    public function test_keeps_incoming_value_when_no_gabarit(): void
    {
        $module = new GabaritModule(new Container());
        self::assertTrue($module->decideBlockEditor(true, null));
        self::assertFalse($module->decideBlockEditor(false, null));
    }
}
