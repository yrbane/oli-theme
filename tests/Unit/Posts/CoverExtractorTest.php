<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Posts;

use OliTheme\Posts\CoverExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de CoverExtractor.
 *
 * @package OliTheme\Tests\Unit\Posts
 *
 * @since 1.0.0
 */
final class CoverExtractorTest extends TestCase
{
    public function testExtractsLeadingFigure(): void
    {
        $html = '<figure class="page-cover"><img src="cover.jpg" alt=""></figure><h2>Titre</h2><p>Texte.</p>';

        $result = (new CoverExtractor())->split($html);

        self::assertSame('<figure class="page-cover"><img src="cover.jpg" alt=""></figure>', $result['cover']);
        self::assertSame('<h2>Titre</h2><p>Texte.</p>', $result['body']);
    }

    public function testHandlesLeadingWhitespaceAndNewlines(): void
    {
        $html = "\n  <figure class=\"page-cover\">\n    <img src=\"cover.jpg\" alt=\"\">\n  </figure>\n<h2>Titre</h2>";

        $result = (new CoverExtractor())->split($html);

        self::assertNotNull($result['cover']);
        self::assertStringContainsString('<img src="cover.jpg"', $result['cover']);
        self::assertSame('<h2>Titre</h2>', $result['body']);
    }

    public function testReturnsNullCoverWhenContentDoesNotStartWithFigure(): void
    {
        $html = '<p>Intro.</p><figure><img src="x.jpg"></figure><p>Suite.</p>';

        $result = (new CoverExtractor())->split($html);

        self::assertNull($result['cover']);
        self::assertSame($html, $result['body']);
    }

    public function testReturnsNullCoverForEmptyContent(): void
    {
        $result = (new CoverExtractor())->split('');

        self::assertNull($result['cover']);
        self::assertSame('', $result['body']);
    }

    public function testExtractsOnlyTheFirstFigure(): void
    {
        $html = '<figure>A</figure><p>Mid</p><figure>B</figure>';

        $result = (new CoverExtractor())->split($html);

        self::assertSame('<figure>A</figure>', $result['cover']);
        self::assertSame('<p>Mid</p><figure>B</figure>', $result['body']);
    }
}
