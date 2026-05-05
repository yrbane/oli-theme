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
}
