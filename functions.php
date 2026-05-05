<?php

declare(strict_types=1);

/**
 * Bootstrap principal du thème oli-theme.
 *
 * Charge l'autoloader Composer puis délègue toute l'initialisation à
 * la classe \OliTheme\Theme. Toute logique métier vit dans src/.
 *
 * @package OliTheme
 */

if (!\defined('ABSPATH')) {
    exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (!\file_exists($autoload)) {
    \wp_die('Le thème oli-theme n\'a pas été installé. Lancer "composer install" depuis le répertoire du thème.');
}

require_once $autoload;

\OliTheme\Theme::boot(__DIR__);
