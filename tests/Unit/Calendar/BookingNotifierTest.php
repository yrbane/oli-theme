<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Calendar;

use DateTimeImmutable;
use OliTheme\Calendar\Booking;
use OliTheme\Calendar\BookingStatus;
use OliTheme\Calendar\CalendarSettings;
use OliTheme\Calendar\Notifications\BookingNotifier;
use OliTheme\Calendar\Service;
use OliTheme\Calendar\ServiceRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class BookingNotifierTest extends TestCase
{
    private array $mailsSent = [];

    private function services(?Service $svc): ServiceRepositoryInterface
    {
        $mock = $this->createMock(ServiceRepositoryInterface::class);
        $mock->method('byId')->willReturn($svc);
        return $mock;
    }

    private function makeBooking(string $lang = 'fr', BookingStatus $status = BookingStatus::Pending): Booking
    {
        return new Booking(
            id: 1,
            start: new DateTimeImmutable('2026-06-10 10:00'),
            end:   new DateTimeImmutable('2026-06-10 11:00'),
            serviceId: 'massage',
            customerName: 'Jean Dupont',
            customerEmail: 'jean@example.com',
            status: $status,
            customerPhone: '06 11 22 33 44',
            message: 'Première fois, débutant.',
            language: $lang,
        );
    }

    private function makeNotifier(?CalendarSettings $settings = null, ?Service $svc = null): BookingNotifier
    {
        $this->mailsSent = [];
        $notifier = new BookingNotifier(
            $settings ?? new CalendarSettings(),
            $this->services($svc ?? new Service('massage', 'Massage 1h', 'Massage 1h', 60)),
            mailer: function (string $to, string $subject, string $body) {
                $this->mailsSent[] = compact('to', 'subject', 'body');
                return true;
            },
            adminEmailProvider: fn (): string => 'admin@example.com',
            siteNameProvider:   fn (): string => 'Oli Kalari Test',
        );
        return $notifier;
    }

    public function test_sends_two_emails_customer_and_admin(): void
    {
        $notifier = $this->makeNotifier();
        $result = $notifier->notify($this->makeBooking());

        self::assertSame([true, true], $result);
        self::assertCount(2, $this->mailsSent);
        self::assertSame('jean@example.com', $this->mailsSent[0]['to']);
        self::assertSame('admin@example.com', $this->mailsSent[1]['to']);
    }

    public function test_uses_notification_email_when_configured(): void
    {
        $notifier = $this->makeNotifier(
            new CalendarSettings(notificationEmail: 'olivier@perso.com'),
        );
        $notifier->notify($this->makeBooking());
        self::assertSame('olivier@perso.com', $this->mailsSent[1]['to']);
    }

    public function test_french_body_when_lang_fr(): void
    {
        $this->makeNotifier()->notify($this->makeBooking('fr'));
        self::assertStringContainsString('Bonjour Jean Dupont', $this->mailsSent[0]['body']);
        self::assertStringContainsString('Statut : en attente', $this->mailsSent[0]['body']);
        self::assertStringContainsString('Massage 1h', $this->mailsSent[0]['body']);
    }

    public function test_english_body_when_lang_en(): void
    {
        $this->makeNotifier()->notify($this->makeBooking('en'));
        self::assertStringContainsString('Hi Jean Dupont', $this->mailsSent[0]['body']);
        self::assertStringContainsString('Status: pending', $this->mailsSent[0]['body']);
    }

    public function test_admin_subject_indicates_confirmed_vs_pending(): void
    {
        $this->makeNotifier()->notify($this->makeBooking('fr', BookingStatus::Pending));
        self::assertStringStartsWith('[À CONFIRMER]', $this->mailsSent[1]['subject']);

        $this->makeNotifier()->notify($this->makeBooking('fr', BookingStatus::Confirmed));
        self::assertStringStartsWith('[CONFIRMÉE]', $this->mailsSent[1]['subject']);
    }

    public function test_admin_body_includes_phone_and_message(): void
    {
        $this->makeNotifier()->notify($this->makeBooking());
        $adminBody = $this->mailsSent[1]['body'];
        self::assertStringContainsString('06 11 22 33 44', $adminBody);
        self::assertStringContainsString('Première fois, débutant.', $adminBody);
    }
}
