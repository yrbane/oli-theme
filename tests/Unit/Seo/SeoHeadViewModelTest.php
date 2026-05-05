<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use OliTheme\Seo\SeoHeadViewModel;
use PHPUnit\Framework\TestCase;

/**
 * Tests du SeoHeadViewModel.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class SeoHeadViewModelTest extends TestCase
{
    public function testItExposesAllProperties(): void
    {
        $hreflangs = [['code' => 'fr', 'url' => 'https://example.com/fr/']];
        $og = ['og:type' => 'article', 'og:title' => 'Test'];
        $twitter = ['twitter:card' => 'summary', 'twitter:title' => 'Test'];

        $vm = new SeoHeadViewModel(
            title: 'Mon titre — Mon site',
            description: 'Une description courte.',
            robots: 'index, follow',
            canonical: 'https://example.com/fr/post/',
            hreflangs: $hreflangs,
            og: $og,
            twitter: $twitter,
            jsonLd: '{"@context":"https://schema.org","@graph":[]}',
        );

        self::assertSame('Mon titre — Mon site', $vm->title);
        self::assertSame('Une description courte.', $vm->description);
        self::assertSame('index, follow', $vm->robots);
        self::assertSame('https://example.com/fr/post/', $vm->canonical);
        self::assertSame($hreflangs, $vm->hreflangs);
        self::assertSame($og, $vm->og);
        self::assertSame($twitter, $vm->twitter);
        self::assertSame('{"@context":"https://schema.org","@graph":[]}', $vm->jsonLd);
    }
}
