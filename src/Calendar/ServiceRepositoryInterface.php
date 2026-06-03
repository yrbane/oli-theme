<?php

declare(strict_types=1);

namespace OliTheme\Calendar;

/**
 * @package OliTheme\Calendar
 *
 * @since 1.3.0
 */
interface ServiceRepositoryInterface
{
    /** @return list<Service> */
    public function all(): array;

    public function byId(string $id): ?Service;
}
