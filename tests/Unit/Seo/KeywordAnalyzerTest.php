<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use OliTheme\Seo\KeywordAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Tests de KeywordAnalyzer.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class KeywordAnalyzerTest extends TestCase
{
    private KeywordAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new KeywordAnalyzer();
    }

    public function testDensityInReturnsPercentage(): void
    {
        // "yoga" apparaît 2 fois sur 4 mots → 50%
        $density = $this->analyzer->densityIn('yoga yoga is great', 'yoga');
        self::assertSame(50.0, $density);
    }

    public function testInTitleCaseInsensitive(): void
    {
        self::assertTrue($this->analyzer->inTitle('Yoga Hebdomadaire', 'yoga'));
    }

    public function testInSlugReplacesDashes(): void
    {
        self::assertTrue($this->analyzer->inSlug('cours-de-yoga', 'yoga'));
    }

    public function testInFirstParagraphFinds(): void
    {
        $html = '<p>Hello yoga world</p><p>second</p>';
        self::assertTrue($this->analyzer->inFirstParagraph($html, 'yoga'));
    }

    public function testInHeadingsReturnsCorrectMap(): void
    {
        $html = '<h1>Yoga</h1><h3>Mantras</h3>';
        $result = $this->analyzer->inHeadings($html, 'yoga');
        self::assertTrue($result['h1']);
        self::assertFalse($result['h2']);
        self::assertFalse($result['h3']);
    }
}
