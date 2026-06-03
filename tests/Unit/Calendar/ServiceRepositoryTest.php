<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Calendar\Service;
use OliTheme\Calendar\ServiceRepository;
use PHPUnit\Framework\TestCase;

final class ServiceRepositoryTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $store = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->store = [];
        Functions\when('get_option')->alias(fn (string $k) => $k === ServiceRepository::OPTION_KEY ? $this->store : []);
        Functions\when('update_option')->alias(function (string $k, mixed $v) {
            if ($k === ServiceRepository::OPTION_KEY && \is_array($v)) {
                $this->store = $v;
            }
            return true;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_all_returns_empty_when_no_persistence(): void
    {
        self::assertSame([], (new ServiceRepository())->all());
    }

    public function test_save_then_load(): void
    {
        $repo = new ServiceRepository();
        $svc  = $repo->save(new Service(
            id: 'massage-1h',
            labelFr: 'Massage 1h',
            labelEn: 'Massage 1h',
            durationMinutes: 60,
            priceCents: 6000,
        ));

        $loaded = $repo->byId('massage-1h');
        self::assertNotNull($loaded);
        self::assertEquals($svc, $loaded);
    }

    public function test_save_replaces_when_id_matches(): void
    {
        $repo = new ServiceRepository();
        $repo->save(new Service('s', 'A', 'A', 60));
        $repo->save(new Service('s', 'B', 'B', 90));

        $list = $repo->all();
        self::assertCount(1, $list);
        self::assertSame('B', $list[0]->labelFr);
        self::assertSame(90, $list[0]->durationMinutes);
    }

    public function test_save_generates_id_from_label_when_missing(): void
    {
        $repo = new ServiceRepository();
        $saved = $repo->save(new Service('', 'Cours particulier', 'Private course', 60));
        self::assertSame('cours-particulier', $saved->id);
    }

    public function test_save_sanitizes_provided_id(): void
    {
        $repo = new ServiceRepository();
        $saved = $repo->save(new Service('Massage 1h!', 'Massage', 'Massage', 60));
        // espaces et caractères spéciaux supprimés.
        self::assertSame('massage1h', $saved->id);
    }

    public function test_delete_removes_by_id(): void
    {
        $repo = new ServiceRepository();
        $repo->save(new Service('a', 'A', 'A', 60));
        $repo->save(new Service('b', 'B', 'B', 60));

        self::assertTrue($repo->delete('a'));
        self::assertNull($repo->byId('a'));
        self::assertNotNull($repo->byId('b'));
    }

    public function test_delete_returns_false_when_unknown(): void
    {
        $repo = new ServiceRepository();
        self::assertFalse($repo->delete('inconnu'));
    }

    public function test_label_falls_back_to_fr_when_en_empty(): void
    {
        $svc = new Service('s', 'Cours', '', 60);
        self::assertSame('Cours', $svc->label('en'));
        self::assertSame('Cours', $svc->label('fr'));
    }

    public function test_label_uses_en_when_present(): void
    {
        $svc = new Service('s', 'Cours', 'Course', 60);
        self::assertSame('Course', $svc->label('en'));
        self::assertSame('Cours', $svc->label('fr'));
    }

    public function test_duration_is_clamped(): void
    {
        $svc = Service::fromArray(['id' => 's', 'labelFr' => 'x', 'labelEn' => 'x', 'durationMinutes' => 9999]);
        self::assertSame(480, $svc->durationMinutes);
        $svc = Service::fromArray(['id' => 's', 'labelFr' => 'x', 'labelEn' => 'x', 'durationMinutes' => 1]);
        self::assertSame(15, $svc->durationMinutes);
    }
}
