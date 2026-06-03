<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

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
    }
}
