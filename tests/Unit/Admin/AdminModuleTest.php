<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Admin;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use OliTheme\Admin\AdminModule;
use OliTheme\Container;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de AdminModule.
 *
 * @package OliTheme\Tests\Unit\Admin
 *
 * @since 1.1.0
 */
final class AdminModuleTest extends TestCase
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

    public function testRegisterHooksAdminMenuEtAdminPageAccessDenied(): void
    {
        Actions\expectAdded('admin_menu')->once();
        Actions\expectAdded('admin_page_access_denied')->once();

        (new AdminModule(new Container()))->register();

        $this->addToAssertionCount(1);
    }
}
