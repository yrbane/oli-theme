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
        Functions\when('get_option')->justReturn(false);
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

    public function test_it_should_add_top_rewrite_rule_for_each_language(): void
    {
        Functions\expect('add_rewrite_rule')
            ->atLeast()->times(4);

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
            '^en/(.+)/?$'        => 'index.php?pagename=en/$matches[1]', // idem (descendants)
            '^some-other/?$'     => 'index.php?pagename=some-other',  // doit être préservée
        ];

        $filtered = $rules->filter($wpRules);

        // Nos rules ont remplacé les verbose-page-rules pour ^en/?$ et ^en/(.+)/?$.
        self::assertSame('index.php?oli_lang=en', $filtered['^en/?$']);
        self::assertSame('index.php?oli_lang=en&pagename=$matches[1]', $filtered['^en/(.+)/?$']);
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
