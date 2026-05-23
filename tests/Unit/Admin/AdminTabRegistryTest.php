<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Admin\AdminTabInterface;
use OliTheme\Admin\AdminTabRegistry;
use PHPUnit\Framework\TestCase;

final class AdminTabRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg(1);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testAddAndRetrieveByGroup(): void
    {
        $registry = new AdminTabRegistry();
        $registry->add($this->tab('galerie', 'contenu', 'Galerie'));
        $registry->add($this->tab('banner', 'identite', 'Identité visuelle'));

        $contenu = $registry->forGroup('contenu');
        self::assertCount(1, $contenu);
        self::assertSame('galerie', $contenu[0]->id());
    }

    public function testFindReturnsExactTab(): void
    {
        $registry = new AdminTabRegistry();
        $registry->add($this->tab('dashboard', 'seo', 'Dashboard'));

        self::assertSame('dashboard', $registry->find('seo', 'dashboard')?->id());
        self::assertNull($registry->find('seo', 'inconnu'));
    }

    public function testFirstOfGroupReturnsInsertionOrder(): void
    {
        $registry = new AdminTabRegistry();
        $registry->add($this->tab('reglages', 'seo', 'Réglages SEO'));
        $registry->add($this->tab('dashboard', 'seo', 'Dashboard'));

        self::assertSame('reglages', $registry->firstOfGroup('seo')?->id());
    }

    public function testGroupsWithTabsKeepsAdminGroupsOrder(): void
    {
        $registry = new AdminTabRegistry();
        $registry->add($this->tab('galerie', 'contenu', 'Galerie'));
        $registry->add($this->tab('banner', 'identite', 'Identité visuelle'));

        // 'identite' précède 'contenu' dans AdminGroups::all().
        self::assertSame(['identite', 'contenu'], array_keys($registry->groupsWithTabs()));
    }

    private function tab(string $id, string $group, string $label): AdminTabInterface
    {
        return new class ($id, $group, $label) implements AdminTabInterface {
            public function __construct(
                private string $id,
                private string $group,
                private string $label,
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
                return $this->label;
            }
            public function capability(): string
            {
                return 'manage_options';
            }
            public function renderPanel(): void
            {
                echo $this->id;
            }
        };
    }
}
