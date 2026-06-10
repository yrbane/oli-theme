<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Theme;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires du helper {@see Theme::applyBodyClassesFilter()}.
 *
 * Les controllers du thème construisent eux-mêmes la chaîne `bodyClasses`
 * imprimée par le template `base.html.tpl`. Sans ce helper, le filtre WP
 * `body_class` n'est jamais traversé — les modules (Gabarit, etc.) qui
 * hookent ce filtre standard n'ont aucun effet. Ce test garantit que le
 * helper exécute bien le filtre et l'agrège proprement.
 *
 * @package OliTheme\Tests\Unit\Core
 *
 * @since 1.6.0
 */
final class BodyClassesFilterTest extends TestCase
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

    /**
     * Le filtre `body_class` reçoit le tableau des classes initiales et
     * peut en ajouter (cas d'usage : `gabarit-<id>` ajoutée par le module
     * Gabarits). Le résultat est ré-aplati en chaîne dédupliquée.
     */
    public function testHelperPassesClassesThroughBodyClassFilter(): void
    {
        Functions\when('apply_filters')->alias(
            static function (string $hook, array $classes) {
                if ($hook === 'body_class') {
                    $classes[] = 'gabarit-brutalist';
                }

                return $classes;
            },
        );

        $result = Theme::applyBodyClassesFilter('page page-id-548 lang-fr');

        self::assertStringContainsString('page', $result);
        self::assertStringContainsString('page-id-548', $result);
        self::assertStringContainsString('lang-fr', $result);
        self::assertStringContainsString('gabarit-brutalist', $result);
    }

    /**
     * Si le filtre retire une classe (cas marginal mais valide pour les
     * thèmes / plugins), la chaîne résultante ne la contient plus.
     */
    public function testHelperRespectsFilterRemovals(): void
    {
        Functions\when('apply_filters')->alias(
            static fn (string $hook, array $classes) => array_filter(
                $classes,
                static fn (string $c): bool => $c !== 'page-id-548',
            ),
        );

        $result = Theme::applyBodyClassesFilter('page page-id-548 lang-fr');

        self::assertStringNotContainsString('page-id-548', $result);
        self::assertStringContainsString('page', $result);
        self::assertStringContainsString('lang-fr', $result);
    }

    /**
     * Sans WordPress (apply_filters absent), le helper retourne la chaîne
     * d'entrée intacte — pas d'exception ni de fatal.
     */
    public function testHelperReturnsInputUnchangedWhenApplyFiltersMissing(): void
    {
        // Pas de Functions\when : apply_filters n'est pas défini.
        $raw = 'page page-id-548 lang-fr';

        $result = Theme::applyBodyClassesFilter($raw);

        self::assertSame($raw, $result);
    }
}
