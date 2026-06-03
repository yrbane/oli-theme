<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

use OliTheme\Admin\AdminTabRegistry;
use OliTheme\Calendar\Admin\BookingsListAdminPage;
use OliTheme\Calendar\Admin\CalendarPlanningPage;
use OliTheme\Calendar\Admin\CalendarSettingsAdminPage;
use OliTheme\Calendar\Admin\ServicesAdminPage;
use OliTheme\Calendar\Cpt\AvailabilityCpt;
use OliTheme\Calendar\Cpt\BookingCpt;
use OliTheme\Container;
use OliTheme\Core\ModuleInterface;

/**
 * Module Calendrier — Phase 1 (P1) : fondations DI uniquement.
 *
 * Phases à venir :
 *  - P2 : CPT `oli_availability` / `oli_booking` / `oli_service` + admin
 *         calendrier hebdo (voir issue #14).
 *  - P3 : endpoints REST + bloc Gutenberg + widget frontend.
 *  - P4 : notifications + rate limiting + honeypot.
 *  - P5 : i18n + QA.
 *
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
final class CalendarModule implements ModuleInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        $c = $this->container;

        if (!$c->has(CalendarSettings::class)) {
            $c->factory(CalendarSettings::class, static function (): CalendarSettings {
                if (!\function_exists('get_option')) {
                    return CalendarSettings::default();
                }
                $raw = get_option('oli_calendar_settings', []);

                return \is_array($raw) ? CalendarSettings::fromInput($raw) : CalendarSettings::default();
            });
        }

        if (!$c->has(SlotGenerator::class)) {
            $c->factory(
                SlotGenerator::class,
                static fn (Container $cc): SlotGenerator => new SlotGenerator($cc->get(CalendarSettings::class)),
            );
        }
        if (!$c->has(ServiceRepository::class)) {
            $c->factory(ServiceRepository::class, static fn (): ServiceRepository => new ServiceRepository());
        }
        if (!$c->has(AvailabilityRepository::class)) {
            $c->factory(AvailabilityRepository::class, static fn (): AvailabilityRepository => new AvailabilityRepository());
        }
        if (!$c->has(BookingRepository::class)) {
            $c->factory(BookingRepository::class, static fn (): BookingRepository => new BookingRepository());
        }

        // Enregistrement des CPTs sur init (priorité 0 pour précéder les filtres
        // qui dépendent de leur existence).
        add_action('init', static function (): void {
            (new AvailabilityCpt())->register();
            (new BookingCpt())->register();
        }, 0);

        // Sous-onglets admin : Réglages, Services, Planning, Réservations.
        add_action('admin_menu', static function () use ($c): void {
            $registry = $c->get(AdminTabRegistry::class);
            \assert($registry instanceof AdminTabRegistry);

            $registry->add(new CalendarPlanningPage(
                $c->get(CalendarSettings::class),
                $c->get(SlotGenerator::class),
                $c->get(AvailabilityRepository::class),
                $c->get(BookingRepository::class),
                $c->get(ServiceRepository::class),
            ));
            $registry->add(new ServicesAdminPage($c->get(ServiceRepository::class)));
            $registry->add(new BookingsListAdminPage(
                $c->get(BookingRepository::class),
                $c->get(ServiceRepository::class),
            ));
            $registry->add(new CalendarSettingsAdminPage());
        }, 10);

        // Handlers admin-post pour les actions des formulaires.
        add_action('admin_post_' . CalendarSettingsAdminPage::ACTION,     [CalendarSettingsAdminPage::class, 'handleSave']);
        add_action('admin_post_' . ServicesAdminPage::ACTION_SAVE,        [ServicesAdminPage::class, 'handleSave']);
        add_action('admin_post_' . ServicesAdminPage::ACTION_DELETE,      [ServicesAdminPage::class, 'handleDelete']);
        add_action('admin_post_' . CalendarPlanningPage::ACTION_BLOCK,    [CalendarPlanningPage::class, 'handleBlock']);
        add_action('admin_post_' . CalendarPlanningPage::ACTION_UNBLOCK,  [CalendarPlanningPage::class, 'handleUnblock']);
        add_action('admin_post_' . CalendarPlanningPage::ACTION_CONFIRM,  [CalendarPlanningPage::class, 'handleConfirm']);
        add_action('admin_post_' . CalendarPlanningPage::ACTION_CANCEL,   [CalendarPlanningPage::class, 'handleCancel']);
    }
}
