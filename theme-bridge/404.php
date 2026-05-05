<?php

/**
 * Pontage WordPress → NotFoundController pour la 404.
 *
 * @package OliTheme
 */

declare(strict_types=1);

use OliTheme\Posts\NotFoundController;
use OliTheme\Theme;

echo Theme::container()->get(NotFoundController::class)->render();
