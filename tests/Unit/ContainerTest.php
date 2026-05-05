<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit;

use OliTheme\Container;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests du conteneur de dépendances minimaliste.
 */
final class ContainerTest extends TestCase
{
    public function test_it_should_register_and_retrieve_an_instance(): void
    {
        $container = new Container();
        $instance = new stdClass();
        $container->set(stdClass::class, $instance);

        self::assertSame($instance, $container->get(stdClass::class));
    }

    public function test_it_should_register_and_retrieve_via_factory(): void
    {
        $container = new Container();
        $container->factory('greeting', static fn (): string => 'bonjour');

        self::assertSame('bonjour', $container->get('greeting'));
    }

    public function test_it_should_share_factory_instances_by_default(): void
    {
        $container = new Container();
        $container->factory(stdClass::class, static fn (): stdClass => new stdClass());

        $first = $container->get(stdClass::class);
        $second = $container->get(stdClass::class);

        self::assertSame($first, $second);
    }

    public function test_it_should_pass_container_to_factory_for_dependency_injection(): void
    {
        $container = new Container();
        $container->set('config', ['name' => 'oli']);
        $container->factory('greeter', static function (Container $c): string {
            $config = $c->get('config');

            return 'salut ' . $config['name'];
        });

        self::assertSame('salut oli', $container->get('greeter'));
    }

    public function test_it_should_check_if_service_is_registered(): void
    {
        $container = new Container();
        self::assertFalse($container->has('foo'));
        $container->set('foo', 'bar');
        self::assertTrue($container->has('foo'));
    }

    public function test_it_should_throw_when_service_not_found(): void
    {
        $container = new Container();

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage("Service 'unknown' non enregistré dans le conteneur.");

        $container->get('unknown');
    }
}
