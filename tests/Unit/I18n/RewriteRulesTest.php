<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\RewriteRules;
use PHPUnit\Framework\TestCase;

final class RewriteRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('get_option')->alias(static fn (string $k, $d = false) => $k === 'oli_languages' ? ['enabled' => ['fr', 'en', 'it', 'es'], 'default' => 'fr'] : $d);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_it_should_register_query_var_oli_lang(): void
    {
        $rules = new RewriteRules(new LanguageRegistry());

        self::assertContains('oli_lang', $rules->addQueryVar(['existing']));
    }

    public function test_it_should_pass_through_existing_query_vars(): void
    {
        $rules = new RewriteRules(new LanguageRegistry());

        self::assertContains('existing', $rules->addQueryVar(['existing']));
    }

    public function test_it_should_add_one_top_rule_per_non_default_language(): void
    {
        // 4 langues activées : 3 non-défaut (en/it/es) → 3 rules ^<code>/?$.
        // Plus de rule ^<code>/(.+)/?$ : remplacée par LanguagePathRouter.
        Functions\expect('add_rewrite_rule')->times(3);

        (new RewriteRules(new LanguageRegistry()))->register();

        $this->addToAssertionCount(1);
    }

    /**
     * Bug : WordPress génère des « verbose page rules » pour chaque slug de page.
     * Si une page a comme slug « en » (Home anglais) ou « fr » (Accueil), WP
     * crée `^en/?$ → index.php?pagename=en` qui peut prendre priorité sur la
     * nôtre `^en/?$ → index.php?oli_lang=en`. On filtre l'array final pour
     * garantir que nos rules de langue restent en tête.
     */
    public function test_filter_overrides_conflicting_page_rule_for_language_prefix(): void
    {
        $rules = new RewriteRules(new LanguageRegistry());

        $wpRules = [
            '^en/?$'             => 'index.php?pagename=en',          // verbose page rule conflictuelle
            '^en/(.+)/?$'        => 'index.php?pagename=en/$matches[1]', // ancienne capture aveugle (purgée)
            '^some-other/?$'     => 'index.php?pagename=some-other',  // doit être préservée
        ];

        $filtered = $rules->filter($wpRules);

        // Notre rule racine remplace la verbose-page-rule.
        self::assertSame('index.php?oli_lang=en', $filtered['^en/?$']);
        // L'ancienne rule de capture est purgée : LanguagePathRouter prend le relais.
        self::assertArrayNotHasKey('^en/(.+)/?$', $filtered);
        // Les rules non-langue sont préservées.
        self::assertSame('index.php?pagename=some-other', $filtered['^some-other/?$']);
    }

    public function test_filter_inserts_language_rules_at_top_when_absent(): void
    {
        $rules = new RewriteRules(new LanguageRegistry());

        $wpRules = ['^foo/?$' => 'index.php?pagename=foo'];
        $filtered = $rules->filter($wpRules);

        $keys = array_keys($filtered);
        // Les rules de langue (non-défaut) doivent figurer avant ^foo/?$.
        $fooPos = array_search('^foo/?$', $keys, true);
        self::assertGreaterThan(0, $fooPos, '^foo/?$ doit venir après les rules de langue');
        self::assertContains('^en/?$', $keys);
    }
}
