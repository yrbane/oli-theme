<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\Core\RendererInterface;
use OliTheme\Events\EventModelInterface;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\TranslationModelInterface;
use OliTheme\Posts\PostModelInterface;
use OliTheme\Seo\Admin\RedirectsPage;
use OliTheme\Seo\Admin\SeoMetabox;
use OliTheme\Seo\Admin\SeoOverviewPage;

/**
 * Module SEO : enregistre tous les services SEO dans le container
 * et branche les hooks WordPress associés.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class SeoModule implements ModuleInterface
{
    /**
     * @param Container $container Container de services du thème.
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Enregistre les services SEO et branche les hooks WordPress.
     */
    public function register(): void
    {
        $container = $this->container;

        // --- Builders ---

        if (! $container->has(CanonicalBuilder::class)) {
            $container->factory(
                CanonicalBuilder::class,
                static fn (): CanonicalBuilder => new CanonicalBuilder(),
            );
        }

        if (! $container->has(RobotsBuilder::class)) {
            $container->factory(
                RobotsBuilder::class,
                static fn (): RobotsBuilder => new RobotsBuilder(),
            );
        }

        if (! $container->has(OpenGraphBuilder::class)) {
            $container->factory(
                OpenGraphBuilder::class,
                static fn (): OpenGraphBuilder => new OpenGraphBuilder(),
            );
        }

        if (! $container->has(TwitterCardBuilder::class)) {
            $container->factory(
                TwitterCardBuilder::class,
                static fn (): TwitterCardBuilder => new TwitterCardBuilder(),
            );
        }

        if (! $container->has(HreflangBuilder::class)) {
            $container->factory(
                HreflangBuilder::class,
                static fn (Container $c): HreflangBuilder => new HreflangBuilder(
                    $c->get(LanguageRegistryInterface::class),
                    $c->get(TranslationModelInterface::class),
                ),
            );
        }

        // --- Modèles ---

        if (! $container->has(SeoMetaModel::class)) {
            $container->factory(
                SeoMetaModel::class,
                static fn (): SeoMetaModel => new SeoMetaModel(),
            );
        }

        if (! $container->has(SeoMetaModelInterface::class)) {
            $container->factory(
                SeoMetaModelInterface::class,
                static fn (Container $c): SeoMetaModelInterface => $c->get(SeoMetaModel::class),
            );
        }

        if (! $container->has(RedirectInstaller::class)) {
            $container->factory(
                RedirectInstaller::class,
                static function (): RedirectInstaller {
                    /** @var \wpdb $wpdb */
                    $wpdb = \is_object($GLOBALS['wpdb'] ?? null) ? $GLOBALS['wpdb'] : new \stdClass();
                    return new RedirectInstaller($wpdb);
                },
            );
        }

        if (! $container->has(RedirectModel::class)) {
            $container->factory(
                RedirectModel::class,
                static function (Container $c): RedirectModel {
                    /** @var \wpdb $wpdb */
                    $wpdb = \is_object($GLOBALS['wpdb'] ?? null) ? $GLOBALS['wpdb'] : new \stdClass();
                    return new RedirectModel($wpdb, $c->get(RedirectInstaller::class));
                },
            );
        }

        if (! $container->has(RedirectModelInterface::class)) {
            $container->factory(
                RedirectModelInterface::class,
                static fn (Container $c): RedirectModelInterface => $c->get(RedirectModel::class),
            );
        }

        // --- Fil d'Ariane ---

        if (! $container->has(BreadcrumbsController::class)) {
            $container->factory(
                BreadcrumbsController::class,
                static fn (): BreadcrumbsController => new BreadcrumbsController(),
            );
        }

        if (! $container->has(BreadcrumbsControllerInterface::class)) {
            $container->factory(
                BreadcrumbsControllerInterface::class,
                static fn (Container $c): BreadcrumbsControllerInterface => $c->get(BreadcrumbsController::class),
            );
        }

        // --- Contrôleur SEO ---

        if (! $container->has(SeoController::class)) {
            $container->factory(
                SeoController::class,
                static fn (Container $c): SeoController => new SeoController(
                    $c->get(SeoMetaModelInterface::class),
                    $c->get(CanonicalBuilder::class),
                    $c->get(HreflangBuilder::class),
                    $c->get(RobotsBuilder::class),
                    $c->get(OpenGraphBuilder::class),
                    $c->get(TwitterCardBuilder::class),
                    $c->get(BreadcrumbsControllerInterface::class),
                ),
            );
        }

        if (! $container->has(SeoControllerInterface::class)) {
            $container->factory(
                SeoControllerInterface::class,
                static fn (Container $c): SeoControllerInterface => $c->get(SeoController::class),
            );
        }

        // --- Sitemap ---

        if (! $container->has(SitemapEntryBuilder::class)) {
            $container->factory(
                SitemapEntryBuilder::class,
                static fn (): SitemapEntryBuilder => new SitemapEntryBuilder(),
            );
        }

        if (! $container->has(SitemapIndexBuilder::class)) {
            $container->factory(
                SitemapIndexBuilder::class,
                static fn (): SitemapIndexBuilder => new SitemapIndexBuilder(),
            );
        }

        if (! $container->has(SitemapController::class)) {
            $container->factory(
                SitemapController::class,
                static fn (Container $c): SitemapController => new SitemapController(
                    $c->get(LanguageRegistryInterface::class),
                    $c->get(PostModelInterface::class),
                    $c->get(EventModelInterface::class),
                    $c->get(SitemapEntryBuilder::class),
                    $c->get(SitemapIndexBuilder::class),
                ),
            );
        }

        if (! $container->has(SitemapControllerInterface::class)) {
            $container->factory(
                SitemapControllerInterface::class,
                static fn (Container $c): SitemapControllerInterface => $c->get(SitemapController::class),
            );
        }

        // --- Analyseurs ---

        if (! $container->has(ReadabilityAnalyzer::class)) {
            $container->factory(
                ReadabilityAnalyzer::class,
                static fn (): ReadabilityAnalyzer => new ReadabilityAnalyzer(),
            );
        }

        if (! $container->has(KeywordAnalyzer::class)) {
            $container->factory(
                KeywordAnalyzer::class,
                static fn (): KeywordAnalyzer => new KeywordAnalyzer(),
            );
        }

        if (! $container->has(ImageAuditor::class)) {
            $container->factory(
                ImageAuditor::class,
                static fn (): ImageAuditor => new ImageAuditor(),
            );
        }

        if (! $container->has(ScoreCalculator::class)) {
            $container->factory(
                ScoreCalculator::class,
                static fn (Container $c): ScoreCalculator => new ScoreCalculator(
                    $c->get(ReadabilityAnalyzer::class),
                    $c->get(KeywordAnalyzer::class),
                    $c->get(ImageAuditor::class),
                ),
            );
        }

        if (! $container->has(InternalLinkSuggester::class)) {
            $container->factory(
                InternalLinkSuggester::class,
                static fn (Container $c): InternalLinkSuggester => new InternalLinkSuggester(
                    $c->get(PostModelInterface::class),
                ),
            );
        }

        // --- Contrôleur redirections ---

        if (! $container->has(RedirectController::class)) {
            $container->factory(
                RedirectController::class,
                static fn (Container $c): RedirectController => new RedirectController(
                    $c->get(RedirectModelInterface::class),
                ),
            );
        }

        // --- Admin ---

        if (! $container->has(SeoMetabox::class)) {
            $container->factory(
                SeoMetabox::class,
                static fn (Container $c): SeoMetabox => new SeoMetabox(
                    $c->get(SeoMetaModelInterface::class),
                    $c->get(RendererInterface::class),
                ),
            );
        }

        if (! $container->has(SeoOverviewPage::class)) {
            $container->factory(
                SeoOverviewPage::class,
                static fn (Container $c): SeoOverviewPage => new SeoOverviewPage(
                    $c->get(RendererInterface::class),
                ),
            );
        }

        if (! $container->has(RedirectsPage::class)) {
            $container->factory(
                RedirectsPage::class,
                static fn (Container $c): RedirectsPage => new RedirectsPage(
                    $c->get(RedirectModelInterface::class),
                    $c->get(RendererInterface::class),
                ),
            );
        }

        // --- Hooks WordPress ---

        // Migration idempotente du schéma à chaque démarrage : couvre les déploiements
        // par `git pull` où `after_switch_theme` ne s'exécute pas (issue #3).
        add_action('init', function (): void {
            $installer = $this->container->get(RedirectInstaller::class);
            \assert($installer instanceof RedirectInstaller);
            $installer->ensureInstalled();
        }, 5);

        add_action('template_redirect', function (): void {
            $controller = $this->container->get(RedirectController::class);
            $controller->handle((string) ($_SERVER['REQUEST_URI'] ?? '/'));
        });

        add_action('add_meta_boxes', function (): void {
            $this->container->get(SeoMetabox::class)->register();
        });

        add_action('save_post', function (int $postId): void {
            $metabox = $this->container->get(SeoMetabox::class);
            /** @var array<string, mixed> $postData */
            $postData = $_POST;
            $metabox->save($postId, $postData);
        });

        add_action('admin_menu', function (): void {
            $this->container->get(SeoOverviewPage::class)->register();
            $this->container->get(RedirectsPage::class)->register();
        });

        // Hooks admin-post.php pour le CRUD redirections : admin_menu n'est pas appelé
        // sur admin-post.php, on doit donc les brancher via admin_init.
        add_action('admin_init', function (): void {
            $this->container->get(RedirectsPage::class)->registerActions();
        });
    }
}
