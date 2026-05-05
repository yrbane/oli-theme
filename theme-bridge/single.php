<?php

/**
 * Pontage WordPress → PostController pour les posts singuliers.
 *
 * @package OliTheme
 */

declare(strict_types=1);

use OliTheme\Posts\PostController;
use OliTheme\Theme;

echo Theme::container()->get(PostController::class)->renderSingle();
