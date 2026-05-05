<?php

declare(strict_types=1);

/**
 * Pont WordPress -> contrôleur de fallback.
 *
 * Tant que le module Posts n'est pas livré (Plan 3), affiche un layout
 * minimal "Coming soon" via Lunar pour valider la chaîne de rendu.
 *
 * @package OliTheme
 */

use OliTheme\Core\ViewRenderer;
use OliTheme\Theme;

\ob_start();
\wp_head();
$wpHead = \ob_get_clean() ?: '';

\ob_start();
\wp_footer();
$wpFooter = \ob_get_clean() ?: '';

$renderer = Theme::container()->get(ViewRenderer::class);
\assert($renderer instanceof ViewRenderer);

echo $renderer->render('layouts/empty.html', [
    'lang' => \get_bloginfo('language') ?: 'fr',
    'title' => \get_bloginfo('name'),
    'message' => \__('Site en cours de construction.', 'oli-theme'),
    'wpHead' => $wpHead,
    'wpFooter' => $wpFooter,
]);
