<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Admin\AdminTabInterface;
use OliTheme\Admin\AdminTabRegistry;
use OliTheme\Admin\ThemeAdminPage;
use PHPUnit\Framework\TestCase;

final class ThemeAdminPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg(1);
        Functions\when('esc_attr')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('esc_url')->returnArg(1);
        Functions\when('sanitize_key')->returnArg(1);
        Functions\when('admin_url')->alias(static fn (string $p = ''): string => 'http://x/wp-admin/' . $p);
        Functions\when('add_query_arg')->alias(static fn (array $a, string $u): string => $u . '?' . http_build_query($a));
        Functions\when('current_user_can')->justReturn(true);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRendersDefaultTabWhenNoParams(): void
    {
        $page = new ThemeAdminPage($this->registryWithTabs());
        $_GET = [];

        ob_start();
        $page->render();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('[BANNER]', $html);
        self::assertStringNotContainsString('[GALERIE]', $html);
    }

    public function testRendersRequestedTabAndSub(): void
    {
        $page = new ThemeAdminPage($this->registryWithTabs());
        $_GET = ['tab' => 'contenu', 'sub' => 'galerie'];

        ob_start();
        $page->render();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('[GALERIE]', $html);
    }

    public function testFallsBackToDefaultForUnknownGroup(): void
    {
        $page = new ThemeAdminPage($this->registryWithTabs());
        $_GET = ['tab' => 'inconnu'];

        ob_start();
        $page->render();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('[BANNER]', $html);
    }

    public function testDeniesWhenCapabilityMissing(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        $page = new ThemeAdminPage($this->registryWithTabs());
        $_GET = ['tab' => 'contenu', 'sub' => 'galerie'];

        ob_start();
        $page->render();
        $html = (string) ob_get_clean();

        self::assertStringNotContainsString('[GALERIE]', $html);
    }

    private function tab(string $id, string $group, string $marker): AdminTabInterface
    {
        return new class ($id, $group, $marker) implements AdminTabInterface {
            public function __construct(
                private string $id,
                private string $group,
                private string $marker,
            ) {
            }
            public function id(): string
            {
                return $this->id;
            }
            public function group(): string
            {
                return $this->group;
            }
            public function label(): string
            {
                return ucfirst($this->id);
            }
            public function capability(): string
            {
                return 'manage_options';
            }
            public function renderPanel(): void
            {
                echo '[' . $this->marker . ']';
            }
        };
    }

    private function registryWithTabs(): AdminTabRegistry
    {
        $r = new AdminTabRegistry();
        $r->add($this->tab('banner', 'identite', 'BANNER'));
        $r->add($this->tab('galerie', 'contenu', 'GALERIE'));
        return $r;
    }
}
