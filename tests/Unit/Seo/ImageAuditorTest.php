<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use OliTheme\Seo\ImageAuditor;
use PHPUnit\Framework\TestCase;

/**
 * Tests de ImageAuditor.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class ImageAuditorTest extends TestCase
{
    private ImageAuditor $auditor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditor = new ImageAuditor();
    }

    public function testAuditReturnsEmptyWhenNoImages(): void
    {
        $issues = $this->auditor->audit('<p>text</p>');
        self::assertSame([], $issues);
    }

    public function testAuditFlagsMissingAlt(): void
    {
        $issues = $this->auditor->audit('<img src="a.jpg">');
        self::assertCount(1, $issues);
        self::assertSame('missing_alt', $issues[0]['issue']);
        self::assertSame('a.jpg', $issues[0]['src']);
    }

    public function testAuditFlagsEmptyAlt(): void
    {
        $issues = $this->auditor->audit('<img src="b.jpg" alt="">');
        self::assertCount(1, $issues);
        self::assertSame('empty_alt', $issues[0]['issue']);
        self::assertSame('b.jpg', $issues[0]['src']);
    }
}
