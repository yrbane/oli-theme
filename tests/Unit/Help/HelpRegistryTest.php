<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Help;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Help\HelpGuide;
use OliTheme\Help\HelpRegistry;
use PHPUnit\Framework\TestCase;

final class HelpRegistryTest extends TestCase
{
    private HelpRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg(1);
        $this->registry = new HelpRegistry();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_all_returns_non_empty_list_of_guides(): void
    {
        $guides = $this->registry->all();

        self::assertNotEmpty($guides);
        foreach ($guides as $guide) {
            self::assertInstanceOf(HelpGuide::class, $guide);
        }
    }

    public function test_all_contains_the_expected_core_guides(): void
    {
        $ids = array_map(static fn (HelpGuide $g): string => $g->id, $this->registry->all());

        // Couverture des sujets clés du thème.
        foreach (['identite', 'banniere', 'slides', 'galerie', 'menu', 'traductions', 'footer', 'apparence', 'typo'] as $expected) {
            self::assertContains($expected, $ids, "Guide manquant : {$expected}");
        }
    }

    public function test_by_id_returns_guide_when_known(): void
    {
        $guide = $this->registry->byId('banniere');

        self::assertNotNull($guide);
        self::assertSame('banniere', $guide->id);
        self::assertNotSame('', $guide->file);
    }

    public function test_by_id_returns_null_when_unknown(): void
    {
        self::assertNull($this->registry->byId('inconnu-xyz'));
    }

    public function test_each_guide_has_unique_id(): void
    {
        $ids = array_map(static fn (HelpGuide $g): string => $g->id, $this->registry->all());

        self::assertSame($ids, array_unique($ids), 'Doublons d\'id dans le registre.');
    }
}
