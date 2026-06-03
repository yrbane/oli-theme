<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use OliTheme\Calendar\AvailabilityRepositoryInterface;
use OliTheme\Calendar\Booking;
use OliTheme\Calendar\BookingFormHandler;
use OliTheme\Calendar\BookingRepositoryInterface;
use OliTheme\Calendar\BookingRequest;
use OliTheme\Calendar\BookingStatus;
use OliTheme\Calendar\CalendarSettings;
use OliTheme\Calendar\RateLimiter;
use OliTheme\Calendar\Service;
use OliTheme\Calendar\ServiceRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class BookingFormHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('get_transient')->justReturn(0);
        Functions\when('set_transient')->justReturn(true);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    private function makeHandler(
        ?ServiceRepositoryInterface $services = null,
        ?AvailabilityRepositoryInterface $avail = null,
        ?BookingRepositoryInterface $book = null,
        bool $autoConfirm = false,
    ): BookingFormHandler {
        return new BookingFormHandler(
            new CalendarSettings(autoConfirm: $autoConfirm),
            $services ?? $this->servicesWith('massage', 60),
            $avail ?? $this->mockAvailability([]),
            $book ?? $this->mockBookings([], persistedId: 42),
            new RateLimiter(),
        );
    }

    private function servicesWith(string $id, int $duration): ServiceRepositoryInterface
    {
        $repo = $this->createMock(ServiceRepositoryInterface::class);
        $repo->method('byId')->willReturn(new Service($id, 'Massage', 'Massage', $duration));

        return $repo;
    }

    private function mockAvailability(array $found): AvailabilityRepositoryInterface
    {
        $repo = $this->createMock(AvailabilityRepositoryInterface::class);
        $repo->method('findInRange')->willReturn($found);
        return $repo;
    }

    private function mockBookings(array $active, int $persistedId = 0): BookingRepositoryInterface
    {
        $repo = $this->createMock(BookingRepositoryInterface::class);
        $repo->method('findActiveInRange')->willReturn($active);
        $repo->method('save')->willReturn($persistedId);
        return $repo;
    }

    private function validRequest(int $now): BookingRequest
    {
        return new BookingRequest(
            serviceId: 'massage',
            startIso: (new DateTimeImmutable('@' . ($now + 3600)))->format(DateTimeImmutable::ATOM),
            customerName: 'Jean Dupont',
            customerEmail: 'jean@example.com',
            renderedAt: $now - 10,
            ipHash: 'iphash1',
        );
    }

    public function test_honeypot_short_circuits_with_fake_success(): void
    {
        $now = 1_700_000_000;
        $request = new BookingRequest(serviceId: 'massage', startIso: 'x', customerName: 'x', customerEmail: 'x@x.x', honeypot: 'spam-bot');
        $result = $this->makeHandler()->handle($request, $now);
        self::assertTrue($result['success']);
        self::assertArrayNotHasKey('bookingId', $result);
    }

    public function test_rejects_submission_too_fast(): void
    {
        $now = 1_700_000_000;
        $req = new BookingRequest(serviceId: 'massage', startIso: 'x', customerName: 'x', customerEmail: 'x@x.x', renderedAt: $now);
        $result = $this->makeHandler()->handle($req, $now);
        self::assertFalse($result['success']);
        self::assertSame('too_fast', $result['errorCode']);
    }

    public function test_rejects_unknown_service(): void
    {
        $now = 1_700_000_000;
        $services = $this->createMock(ServiceRepositoryInterface::class);
        $services->method('byId')->willReturn(null);
        $handler = $this->makeHandler(services: $services);
        $result = $handler->handle($this->validRequest($now), $now);
        self::assertFalse($result['success']);
        self::assertSame('unknown_service', $result['errorCode']);
    }

    public function test_rejects_invalid_email(): void
    {
        $now = 1_700_000_000;
        $req = new BookingRequest(
            serviceId: 'massage',
            startIso: (new DateTimeImmutable('@' . ($now + 3600)))->format(DateTimeImmutable::ATOM),
            customerName: 'Jean',
            customerEmail: 'not-an-email',
            renderedAt: $now - 10,
        );
        $result = $this->makeHandler()->handle($req, $now);
        self::assertFalse($result['success']);
        self::assertSame('invalid_email', $result['errorCode']);
    }

    public function test_rejects_past_date(): void
    {
        $now = 1_700_000_000;
        $req = new BookingRequest(
            serviceId: 'massage',
            startIso: (new DateTimeImmutable('@' . ($now - 3600)))->format(DateTimeImmutable::ATOM),
            customerName: 'Jean',
            customerEmail: 'jean@example.com',
            renderedAt: $now - 10,
        );
        $result = $this->makeHandler()->handle($req, $now);
        self::assertFalse($result['success']);
        self::assertSame('past_date', $result['errorCode']);
    }

    public function test_rejects_when_active_booking_overlaps(): void
    {
        $now = 1_700_000_000;
        $existing = new Booking(
            id: 1,
            start: (new DateTimeImmutable('@' . ($now + 3600))),
            end:   (new DateTimeImmutable('@' . ($now + 7200))),
            serviceId: 'massage',
            customerName: 'X', customerEmail: 'x@x.x',
            status: BookingStatus::Confirmed,
        );
        $handler = $this->makeHandler(book: $this->mockBookings([$existing]));
        $result = $handler->handle($this->validRequest($now), $now);
        self::assertFalse($result['success']);
        self::assertSame('slot_taken', $result['errorCode']);
    }

    public function test_persists_pending_by_default(): void
    {
        $now = 1_700_000_000;
        $result = $this->makeHandler()->handle($this->validRequest($now), $now);
        self::assertTrue($result['success']);
        self::assertSame(42, $result['bookingId']);
        self::assertSame(BookingStatus::Pending, $result['status']);
    }

    public function test_persists_confirmed_when_auto_confirm(): void
    {
        $now = 1_700_000_000;
        $result = $this->makeHandler(autoConfirm: true)->handle($this->validRequest($now), $now);
        self::assertTrue($result['success']);
        self::assertSame(BookingStatus::Confirmed, $result['status']);
    }
}
