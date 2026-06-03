<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use DateTimeImmutable;
use OliTheme\Calendar\Booking;
use OliTheme\Calendar\BookingStatus;
use PHPUnit\Framework\TestCase;

final class BookingTest extends TestCase
{
    private function validParams(): array
    {
        return [
            'id'            => null,
            'start'         => new DateTimeImmutable('2026-06-03 10:00'),
            'end'           => new DateTimeImmutable('2026-06-03 11:00'),
            'serviceId'     => 'massage-1h',
            'customerName'  => 'Jean Dupont',
            'customerEmail' => 'jean@example.com',
            'status'        => BookingStatus::Pending,
        ];
    }

    public function test_valid_booking(): void
    {
        $params = $this->validParams();
        $b = new Booking(...$params);
        self::assertSame('Jean Dupont', $b->customerName);
        self::assertSame(BookingStatus::Pending, $b->status);
    }

    public function test_rejects_inverted_range(): void
    {
        $params = $this->validParams();
        $params['end'] = new DateTimeImmutable('2026-06-03 09:00');
        $this->expectException(\InvalidArgumentException::class);
        new Booking(...$params);
    }

    public function test_rejects_empty_name(): void
    {
        $params = $this->validParams();
        $params['customerName'] = '';
        $this->expectException(\InvalidArgumentException::class);
        new Booking(...$params);
    }

    public function test_rejects_empty_email(): void
    {
        $params = $this->validParams();
        $params['customerEmail'] = '';
        $this->expectException(\InvalidArgumentException::class);
        new Booking(...$params);
    }
}
