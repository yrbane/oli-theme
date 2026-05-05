<?php

/**
 * Pontage WordPress → PostController pour la recherche.
 *
 * @package OliTheme
 */

declare(strict_types=1);

use OliTheme\Posts\PostController;
use OliTheme\Theme;

echo Theme::container()->get(PostController::class)->renderSearch();
