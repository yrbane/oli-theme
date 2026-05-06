<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Events;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Events\EventArchiveRewriteRules;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageRegistryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de EventArchiveRewriteRules.
 *
 * @package OliTheme\Tests\Unit\Events
 *
 * @since 1.0.0
 */
final class EventArchiveRewriteRulesTest extends TestCase
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

    public function testRegistersArchiveRewriteForEachNonDefaultLanguage(): void
    {
        $registry = $this->makeRegistry(
            default: 'fr',
            enabled: ['fr', 'en', 'it', 'es'],
        );

        /** @var array<int, array{regex: string, redirect: string, position: string}> $calls */
        $calls = [];
        Functions\when('add_rewrite_rule')->alias(
            static function (string $regex, string $redirect, string $position) use (&$calls): void {
                $calls[] = ['regex' => $regex, 'redirect' => $redirect, 'position' => $position];
            },
        );

        (new EventArchiveRewriteRules($registry))->register();

        $regexes = array_column($calls, 'regex');

        // EN/IT/ES enregistrées, FR gérée nativement par le CPT.
        self::assertContains('^en/events/?$', $regexes);
        self::assertContains('^it/eventi/?$', $regexes);
        self::assertContains('^es/eventos/?$', $regexes);
        self::assertNotContains('^evenements/?$', $regexes);
        self::assertNotContains('^fr/evenements/?$', $regexes);
    }

    public function testRedirectsToPostTypeArchiveOfOliEvent(): void
    {
        $registry = $this->makeRegistry(default: 'fr', enabled: ['fr', 'en']);

        /** @var array<int, array{regex: string, redirect: string, position: string}> $calls */
        $calls = [];
        Functions\when('add_rewrite_rule')->alias(
            static function (string $regex, string $redirect, string $position) use (&$calls): void {
                $calls[] = ['regex' => $regex, 'redirect' => $redirect, 'position' => $position];
            },
        );

        (new EventArchiveRewriteRules($registry))->register();

        $enRule = array_values(array_filter($calls, static fn ($c) => $c['regex'] === '^en/events/?$'));

        self::assertCount(1, $enRule);
        self::assertSame('index.php?oli_lang=en&post_type=oli_event', $enRule[0]['redirect']);
        self::assertSame('top', $enRule[0]['position']);
    }

    public function testRegistersPagedRouteForArchive(): void
    {
        $registry = $this->makeRegistry(default: 'fr', enabled: ['fr', 'en']);

        /** @var array<int, array{regex: string, redirect: string}> $calls */
        $calls = [];
        Functions\when('add_rewrite_rule')->alias(
            static function (string $regex, string $redirect) use (&$calls): void {
                $calls[] = ['regex' => $regex, 'redirect' => $redirect];
            },
        );

        (new EventArchiveRewriteRules($registry))->register();

        $regexes = array_column($calls, 'regex');
        self::assertContains('^en/events/page/([0-9]+)/?$', $regexes);

        $paged = array_values(array_filter($calls, static fn ($c) => $c['regex'] === '^en/events/page/([0-9]+)/?$'));
        self::assertSame(
            'index.php?oli_lang=en&post_type=oli_event&paged=$matches[1]',
            $paged[0]['redirect'],
        );
    }

    public function testSlugForReturnsExpectedMapping(): void
    {
        self::assertSame('evenements', EventArchiveRewriteRules::slugFor('fr'));
        self::assertSame('events', EventArchiveRewriteRules::slugFor('en'));
        self::assertSame('eventi', EventArchiveRewriteRules::slugFor('it'));
        self::assertSame('eventos', EventArchiveRewriteRules::slugFor('es'));
        self::assertNull(EventArchiveRewriteRules::slugFor('zz'));
    }

    /**
     * @param string[] $enabled
     */
    private function makeRegistry(string $default, array $enabled): LanguageRegistryInterface
    {
        $catalogue = [
            'fr' => new Language('fr', 'French', 'Français', '🇫🇷', 'fr_FR'),
            'en' => new Language('en', 'English', 'English', '🇬🇧', 'en_US'),
            'it' => new Language('it', 'Italian', 'Italiano', '🇮🇹', 'it_IT'),
            'es' => new Language('es', 'Spanish', 'Español', '🇪🇸', 'es_ES'),
        ];

        $registry = $this->createMock(LanguageRegistryInterface::class);
        $registry->method('all')->willReturn(array_values(array_intersect_key($catalogue, array_flip($enabled))));
        $registry->method('default')->willReturn($catalogue[$default]);
        $registry->method('get')->willReturnCallback(static fn (string $code) => $catalogue[$code] ?? null);
        $registry->method('isEnabled')->willReturnCallback(static fn (string $code) => \in_array($code, $enabled, true));

        return $registry;
    }
}
