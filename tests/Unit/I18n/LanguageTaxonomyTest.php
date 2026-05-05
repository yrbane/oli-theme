<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\LanguageTaxonomy;
use PHPUnit\Framework\TestCase;

final class LanguageTaxonomyTest extends TestCase
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

    public function test_it_should_register_language_taxonomy_on_post_and_page(): void
    {
        Functions\expect('register_taxonomy')
            ->once()
            ->with('language', \Mockery::on(static fn ($v) => is_array($v) && in_array('post', $v, true) && in_array('page', $v, true)), \Mockery::any());

        Functions\when('term_exists')->justReturn(false);
        Functions\when('wp_insert_term')->justReturn(['term_id' => 1, 'term_taxonomy_id' => 1]);

        (new LanguageTaxonomy(new LanguageRegistry()))->register();

        $this->addToAssertionCount(1);
    }

    public function test_it_should_seed_terms_for_each_enabled_language(): void
    {
        Functions\when('register_taxonomy')->justReturn(true);
        Functions\when('term_exists')->justReturn(false);

        Functions\expect('wp_insert_term')
            ->atLeast()->times(4)
            ->andReturn(['term_id' => 1, 'term_taxonomy_id' => 1]);

        (new LanguageTaxonomy(new LanguageRegistry()))->register();

        $this->addToAssertionCount(1);
    }

    public function test_it_should_not_recreate_existing_terms(): void
    {
        Functions\when('register_taxonomy')->justReturn(true);
        Functions\when('term_exists')->justReturn(['term_id' => 99]);

        Functions\expect('wp_insert_term')->never();

        (new LanguageTaxonomy(new LanguageRegistry()))->register();

        $this->addToAssertionCount(1);
    }
}
