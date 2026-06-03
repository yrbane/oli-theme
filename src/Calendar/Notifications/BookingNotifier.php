<?php

declare(strict_types=1);

namespace OliTheme\Calendar\Notifications;

use OliTheme\Calendar\Booking;
use OliTheme\Calendar\CalendarSettings;
use OliTheme\Calendar\Service;
use OliTheme\Calendar\ServiceRepositoryInterface;

/**
 * Envoie les e-mails de notification après création d'une réservation.
 *
 * 2 e-mails :
 *  - Au client : récap + statut (en attente / confirmée).
 *  - À l'admin (e-mail de notification configuré, sinon admin_email WP).
 *
 * Localisation : FR / EN selon la langue de la réservation.
 *
 * @package OliTheme\Calendar\Notifications
 *
 * @since 1.3.0
 */
final class BookingNotifier
{
    public function __construct(
        private readonly CalendarSettings $settings,
        private readonly ServiceRepositoryInterface $services,
        /** @var callable(string,string,string,array<int|string,string>=,array<int,string>=): bool */
        private $mailer = null,
        /** @var callable(): string */
        private $adminEmailProvider = null,
        /** @var callable(): string */
        private $siteNameProvider = null,
    ) {
    }

    /**
     * @return array<int, bool> [mailToCustomerOk, mailToAdminOk]
     */
    public function notify(Booking $booking): array
    {
        $service = $this->services->byId($booking->serviceId);
        $lang    = $booking->language === 'en' ? 'en' : 'fr';

        $customerOk = $this->mail(
            $booking->customerEmail,
            $this->customerSubject($lang, $service),
            $this->customerBody($lang, $booking, $service),
        );

        $adminTo   = $this->settings->notificationEmail !== '' ? $this->settings->notificationEmail : $this->adminEmail();
        $adminOk   = $this->mail(
            $adminTo,
            $this->adminSubject($lang, $booking, $service),
            $this->adminBody($booking, $service),
        );

        return [$customerOk, $adminOk];
    }

    private function customerSubject(string $lang, ?Service $service): string
    {
        $site = $this->siteName();
        $svc  = $service !== null ? $service->label($lang) : '';
        return $lang === 'en'
            ? sprintf('[%s] Your booking — %s', $site, $svc)
            : sprintf('[%s] Votre réservation — %s', $site, $svc);
    }

    private function customerBody(string $lang, Booking $booking, ?Service $service): string
    {
        $serviceName = $service !== null ? $service->label($lang) : $booking->serviceId;
        $date        = $booking->start->format('d/m/Y H:i');
        $duration    = $service !== null ? $service->durationMinutes . ' min' : '';
        $statusFr    = match ($booking->status->value) {
            'confirmed' => 'confirmée',
            'pending'   => 'en attente de confirmation',
            default     => $booking->status->value,
        };
        $statusEn    = match ($booking->status->value) {
            'confirmed' => 'confirmed',
            'pending'   => 'pending confirmation',
            default     => $booking->status->value,
        };

        if ($lang === 'en') {
            return implode("\n", [
                sprintf('Hi %s,', $booking->customerName),
                '',
                'Thank you for your booking. Here is the recap:',
                '',
                '• Service: ' . $serviceName,
                '• Date: ' . $date . ' (' . $duration . ')',
                '• Status: ' . $statusEn,
                '',
                'We will reach out if anything needs adjusting.',
                '',
                '— ' . $this->siteName(),
            ]);
        }

        return implode("\n", [
            sprintf('Bonjour %s,', $booking->customerName),
            '',
            'Merci pour votre demande de réservation. Voici le récapitulatif :',
            '',
            '• Service : ' . $serviceName,
            '• Date : ' . $date . ' (' . $duration . ')',
            '• Statut : ' . $statusFr,
            '',
            'Nous reviendrons vers vous si besoin de précisions.',
            '',
            '— ' . $this->siteName(),
        ]);
    }

    private function adminSubject(string $lang, Booking $booking, ?Service $service): string
    {
        $svc = $service !== null ? $service->labelFr : $booking->serviceId;
        $tag = $booking->status->value === 'pending' ? '[À CONFIRMER]' : '[CONFIRMÉE]';
        return sprintf('%s Réservation %s — %s', $tag, $svc, $booking->customerName);
    }

    private function adminBody(Booking $booking, ?Service $service): string
    {
        $serviceName = $service !== null ? $service->labelFr : $booking->serviceId;
        return implode("\n", [
            'Nouvelle réservation reçue :',
            '',
            '• Service : ' . $serviceName,
            '• Date : ' . $booking->start->format('d/m/Y H:i') . ' → ' . $booking->end->format('H:i'),
            '• Statut : ' . $booking->status->value,
            '• Langue : ' . $booking->language,
            '',
            '• Client : ' . $booking->customerName,
            '• Email : ' . $booking->customerEmail,
            '• Téléphone : ' . ($booking->customerPhone !== '' ? $booking->customerPhone : '—'),
            '',
            $booking->message !== '' ? 'Message : ' . $booking->message : '',
        ]);
    }

    private function mail(string $to, string $subject, string $body): bool
    {
        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        if ($this->mailer !== null) {
            return (bool) ($this->mailer)($to, $subject, $body, $headers);
        }
        if (\function_exists('wp_mail')) {
            return (bool) wp_mail($to, $subject, $body, $headers);
        }
        return false;
    }

    private function adminEmail(): string
    {
        if ($this->adminEmailProvider !== null) {
            return (string) ($this->adminEmailProvider)();
        }
        if (\function_exists('get_option')) {
            return (string) get_option('admin_email', '');
        }
        return '';
    }

    private function siteName(): string
    {
        if ($this->siteNameProvider !== null) {
            return (string) ($this->siteNameProvider)();
        }
        if (\function_exists('get_bloginfo')) {
            return (string) get_bloginfo('name');
        }
        return 'Olikalari';
    }
}
