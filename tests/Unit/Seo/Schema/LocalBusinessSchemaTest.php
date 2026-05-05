<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo\Schema;

use OliTheme\Seo\Schema\LocalBusinessSchema;
use PHPUnit\Framework\TestCase;

/**
 * Tests du LocalBusinessSchema.
 *
 * @package OliTheme\Tests\Unit\Seo\Schema
 *
 * @since 1.0.0
 */
final class LocalBusinessSchemaTest extends TestCase
{
    public function testToArrayFull(): void
    {
        $schema = new LocalBusinessSchema(
            'Café de la Paix',
            'https://cafedelapaix.fr/',
            '2 place de l\'Opéra, 75009 Paris',
            '+33 1 40 07 36 36',
        );
        $result = $schema->toArray();

        self::assertSame('LocalBusiness', $result['@type']);
        self::assertSame('https://cafedelapaix.fr/#local-business', $result['@id']);
        self::assertSame('Café de la Paix', $result['name']);
        self::assertSame('https://cafedelapaix.fr/', $result['url']);
        self::assertSame('PostalAddress', $result['address']['@type']);
        self::assertSame('2 place de l\'Opéra, 75009 Paris', $result['address']['streetAddress']);
        self::assertSame('+33 1 40 07 36 36', $result['telephone']);
    }

    public function testToArrayMinimal(): void
    {
        $schema = new LocalBusinessSchema('Ma Boutique', 'https://maboutique.fr');
        $result = $schema->toArray();

        self::assertSame('LocalBusiness', $result['@type']);
        self::assertSame('https://maboutique.fr/#local-business', $result['@id']);
        self::assertArrayNotHasKey('address', $result);
        self::assertArrayNotHasKey('telephone', $result);
    }
}
