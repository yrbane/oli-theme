<?php

/**
 * Pontage WordPress → EventArchiveController pour l'archive des événements.
 *
 * @package OliTheme
 *
 * @since 1.0.0
 */

declare(strict_types=1);

use OliTheme\Events\EventArchiveController;
use OliTheme\Theme;

echo Theme::container()->get(EventArchiveController::class)->renderArchive();
