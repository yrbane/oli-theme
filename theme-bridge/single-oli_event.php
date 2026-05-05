<?php

/**
 * Pontage WordPress → EventController pour la fiche événement.
 *
 * @package OliTheme
 *
 * @since 1.0.0
 */

declare(strict_types=1);

use OliTheme\Events\EventController;
use OliTheme\Theme;

echo Theme::container()->get(EventController::class)->renderSingle();
