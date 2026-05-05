<?php

/**
 * Pontage WordPress → PageController pour les pages WP.
 *
 * @package OliTheme
 *
 * @since 1.0.0
 */

declare(strict_types=1);

use OliTheme\Posts\PageController;
use OliTheme\Theme;

echo Theme::container()->get(PageController::class)->renderSingular();
