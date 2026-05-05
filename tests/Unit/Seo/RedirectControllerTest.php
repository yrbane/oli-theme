<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Seo;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Seo\RedirectController;
use OliTheme\Seo\RedirectEntity;
use OliTheme\Seo\RedirectModelInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests de RedirectController.
 *
 * @package OliTheme\Tests\Unit\Seo
 *
 * @since 1.0.0
 */
final class RedirectControllerTest extends TestCase
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

    public function testHandleReturnsFalseWhenNoMatch(): void
    {
        $model = $this->createMock(RedirectModelInterface::class);
        $model->expects(self::once())->method('findBySource')->with('/inexistant')->willReturn(null);
        $model->expects(self::never())->method('incrementHits');

        $controller = new RedirectController($model);
        $result = $controller->handle('/inexistant');

        self::assertFalse($result);
    }

    public function testHandle301RedirectsAndIncrements(): void
    {
        $entity = $this->makeEntity(id: 42, code: 301, target: 'https://example.com/new');

        $model = $this->createMock(RedirectModelInterface::class);
        $model->expects(self::once())->method('findBySource')->willReturn($entity);
        $model->expects(self::once())->method('incrementHits')->with(42);

        Functions\expect('wp_safe_redirect')
            ->once()
            ->with('https://example.com/new', 301);

        $controller = new RedirectController($model);
        $result = $controller->handle('/old');

        self::assertTrue($result);
    }

    public function testHandle410CallsWpDie(): void
    {
        $entity = $this->makeEntity(id: 5, code: 410);

        $model = $this->createMock(RedirectModelInterface::class);
        $model->expects(self::once())->method('findBySource')->willReturn($entity);
        $model->expects(self::once())->method('incrementHits')->with(5);

        Functions\when('status_header')->justReturn();
        Functions\when('nocache_headers')->justReturn();
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('wp_safe_redirect')->justReturn();

        Functions\expect('wp_die')->once()->andReturnNull();

        $controller = new RedirectController($model);
        $controller->handle('/old-410');
    }

    /**
     * Crée une RedirectEntity factice pour les tests.
     */
    private function makeEntity(int $id = 1, string $source = '/old', string $target = 'https://example.com/new', int $code = 301): RedirectEntity
    {
        return new RedirectEntity(
            id: $id,
            source: $source,
            target: $target,
            code: $code,
            hits: 0,
            createdAt: new DateTimeImmutable('2026-01-01 00:00:00'),
        );
    }
}
