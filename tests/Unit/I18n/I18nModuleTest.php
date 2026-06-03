<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\I18n;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Container;
use OliTheme\Core\HookRegistrar;
use OliTheme\Core\RendererInterface;
use OliTheme\Core\RequestContext;
use OliTheme\I18n\I18nModule;
use OliTheme\I18n\LanguageMetabox;
use OliTheme\I18n\LanguageRegistry;
use OliTheme\I18n\LanguageResolver;
use OliTheme\I18n\LanguageTaxonomy;
use OliTheme\I18n\LanguageUrlFilter;
use OliTheme\I18n\RewriteRules;
use OliTheme\I18n\TranslationModel;
use PHPUnit\Framework\TestCase;

final class I18nModuleTest extends TestCase
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

    public function test_register_binds_taxonomy_and_rewrite_on_init(): void
    {
        Functions\expect('add_action')
            ->atLeast()->once()
            ->with('init', \Mockery::any());

        $this->buildModule()->register();

        $this->addToAssertionCount(1);
    }

    public function test_register_filters_query_vars_and_home_url(): void
    {
        Functions\expect('add_filter')
            ->atLeast()->once()
            ->with('query_vars', \Mockery::any());

        Functions\expect('add_filter')
            ->atLeast()->once()
            ->with('home_url', \Mockery::any(), 10, 2);

        $this->buildModule()->register();

        $this->addToAssertionCount(1);
    }

    private function buildModule(): I18nModule
    {
        $container = new Container();
        $container->set(RequestContext::class, new RequestContext());
        $container->set(HookRegistrar::class, new HookRegistrar());

        $renderer = $this->createMock(RendererInterface::class);
        $container->set(RendererInterface::class, $renderer);

        $container->factory(LanguageRegistry::class, static fn () => new LanguageRegistry());
        $container->factory(LanguageResolver::class, static fn (Container $c) => new LanguageResolver(
            $c->get(LanguageRegistry::class),
            $c->get(RequestContext::class),
        ));
        $container->factory(TranslationModel::class, static fn () => new TranslationModel());
        $container->factory(LanguageTaxonomy::class, static fn (Container $c) => new LanguageTaxonomy($c->get(LanguageRegistry::class)));
        $container->factory(RewriteRules::class, static fn (Container $c) => new RewriteRules($c->get(LanguageRegistry::class)));
        $container->factory(LanguageUrlFilter::class, static fn (Container $c) => new LanguageUrlFilter(
            $c->get(LanguageRegistry::class),
            $c->get(LanguageResolver::class),
        ));
        $container->factory(LanguageMetabox::class, static fn (Container $c) => new LanguageMetabox(
            $c->get(LanguageRegistry::class),
            $c->get(TranslationModel::class),
            $c->get(RendererInterface::class),
        ));

        return new I18nModule($container);
    }
}
