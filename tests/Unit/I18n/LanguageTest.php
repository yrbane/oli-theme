<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use OliTheme\I18n\Language;
use PHPUnit\Framework\TestCase;

final class LanguageTest extends TestCase
{
    public function test_it_should_expose_all_constructor_properties(): void
    {
        $lang = new Language(
            code: 'fr',
            label: 'French',
            nativeLabel: 'Français',
            flag: '🇫🇷',
            locale: 'fr_FR',
            direction: 'ltr',
        );

        self::assertSame('fr', $lang->code);
        self::assertSame('French', $lang->label);
        self::assertSame('Français', $lang->nativeLabel);
        self::assertSame('🇫🇷', $lang->flag);
        self::assertSame('fr_FR', $lang->locale);
        self::assertSame('ltr', $lang->direction);
    }

    public function test_it_should_default_direction_to_ltr(): void
    {
        $lang = new Language(code: 'en', label: 'English', nativeLabel: 'English', flag: '🇬🇧', locale: 'en_US');

        self::assertSame('ltr', $lang->direction);
    }

    public function test_it_should_be_equal_when_code_matches(): void
    {
        $a = new Language('fr', 'French', 'Français', '🇫🇷', 'fr_FR');
        $b = new Language('fr', 'X', 'Y', 'Z', 'fr_FR');

        self::assertTrue($a->equals($b));
    }

    public function test_it_should_not_be_equal_when_code_differs(): void
    {
        $a = new Language('fr', 'French', 'Français', '🇫🇷', 'fr_FR');
        $b = new Language('en', 'English', 'English', '🇬🇧', 'en_US');

        self::assertFalse($a->equals($b));
    }
}
