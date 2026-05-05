<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use OliTheme\Seo\ReadabilityAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Tests de ReadabilityAnalyzer.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class ReadabilityAnalyzerTest extends TestCase
{
    private ReadabilityAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new ReadabilityAnalyzer();
    }

    public function testEmptyTextReturnsZero(): void
    {
        self::assertSame(0, $this->analyzer->score(''));
    }

    public function testWhitespaceOnlyReturnsZero(): void
    {
        self::assertSame(0, $this->analyzer->score('   '));
    }

    public function testSimpleFrenchSentenceScoresAbove60(): void
    {
        $score = $this->analyzer->score('Le chat dort sur le canapé. Il est content.');
        self::assertGreaterThanOrEqual(60, $score);
    }

    public function testComplexLegalTextScoresLow(): void
    {
        $text = 'Nonobstant les dispositions susmentionnées relatives à l\'application des réglementations '
            . 'administratives prépondérantes, les établissements institutionnels réglementaires '
            . 'doivent impérativement conformer leurs procédures administratives aux exigences '
            . 'constitutionnelles contemporaines susvisées en matière de gouvernance organisationnelle.';
        $score = $this->analyzer->score($text);
        self::assertLessThan(50, $score);
    }
}
