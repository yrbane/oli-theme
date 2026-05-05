<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use OliTheme\Container;
use OliTheme\Core\RendererInterface;
use OliTheme\Events\EventModelInterface;
use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\TranslationModelInterface;
use OliTheme\Posts\PostModelInterface;
use OliTheme\Seo\Admin\RedirectsPage;
use OliTheme\Seo\Admin\SeoMetabox;
use OliTheme\Seo\Admin\SeoOverviewPage;
use OliTheme\Seo\BreadcrumbsControllerInterface;
use OliTheme\Seo\RedirectController;
use OliTheme\Seo\SeoControllerInterface;
use OliTheme\Seo\SeoModule;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de SeoModule (orchestrateur services SEO + hooks).
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class SeoModuleTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $GLOBALS['wpdb'] = new \stdClass();

        $this->container = new Container();
        $this->container->set(LanguageRegistryInterface::class, $this->createMock(LanguageRegistryInterface::class));
        $this->container->set(TranslationModelInterface::class, $this->createMock(TranslationModelInterface::class));
        $this->container->set(RendererInterface::class, $this->createMock(RendererInterface::class));
        $this->container->set(PostModelInterface::class, $this->createMock(PostModelInterface::class));
        $this->container->set(EventModelInterface::class, $this->createMock(EventModelInterface::class));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wpdb']);
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRegisterBindsAllServices(): void
    {
        Functions\when('add_action')->justReturn(true);

        $module = new SeoModule($this->container);
        $module->register();

        self::assertTrue($this->container->has(SeoControllerInterface::class));
        self::assertTrue($this->container->has(BreadcrumbsControllerInterface::class));
        self::assertTrue($this->container->has(SeoMetabox::class));
        self::assertTrue($this->container->has(SeoOverviewPage::class));
        self::assertTrue($this->container->has(RedirectsPage::class));
        self::assertTrue($this->container->has(RedirectController::class));
    }

    public function testRegisterHooksAdminMenu(): void
    {
        Actions\expectAdded('admin_menu')->once();

        $module = new SeoModule($this->container);
        $module->register();

        $this->addToAssertionCount(1);
    }
}
