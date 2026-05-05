<?php

/**
 * Pontage WordPress → page d'accueil.
 *
 * Si un article static est défini comme page d'accueil, on rend la page
 * comme une page WP standard. Sinon, on affiche l'archive des actualités.
 *
 * @package OliTheme
 */

declare(strict_types=1);

use OliTheme\Posts\PageController;
use OliTheme\Posts\PostController;
use OliTheme\Theme;

if (\get_queried_object_id() > 0) {
    echo Theme::container()->get(PageController::class)->renderSingular();
} else {
    echo Theme::container()->get(PostController::class)->renderArchive();
}
