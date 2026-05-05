<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LanguageRegistryTest extends TestCase
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

    public function test_it_should_return_default_languages_when_option_missing(): void
    {
        Functions\when('get_option')->justReturn(false);

        $registry = new LanguageRegistry();
        $all = $registry->all();

        self::assertNotEmpty($all);
        self::assertContainsOnlyInstancesOf(Language::class, $all);
        self::assertSame('fr', $registry->default()->code);
    }

    public function test_it_should_load_languages_from_option(): void
    {
        Functions\when('get_option')->justReturn([
            'enabled' => ['fr', 'en'],
            'default' => 'en',
        ]);

        $registry = new LanguageRegistry();

        self::assertCount(2, $registry->all());
        self::assertSame('en', $registry->default()->code);
        self::assertTrue($registry->isEnabled('fr'));
        self::assertTrue($registry->isEnabled('en'));
        self::assertFalse($registry->isEnabled('it'));
    }

    public function test_it_should_get_language_by_code(): void
    {
        Functions\when('get_option')->justReturn(false);

        $registry = new LanguageRegistry();
        $fr = $registry->get('fr');

        self::assertNotNull($fr);
        self::assertSame('fr', $fr->code);
    }

    public function test_it_should_return_null_when_code_unknown(): void
    {
        Functions\when('get_option')->justReturn(false);

        $registry = new LanguageRegistry();
        self::assertNull($registry->get('zz'));
    }

    public function test_it_should_throw_when_no_language_enabled(): void
    {
        Functions\when('get_option')->justReturn(['enabled' => [], 'default' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Aucune langue activée dans la configuration.');

        new LanguageRegistry();
    }
}
