<?php

declare(strict_types=1);

/**
 * Bootstrap PHPUnit pour le thème oli-theme.
 *
 * Charge l'autoloader Composer et initialise Brain Monkey si nécessaire
 * (chaque test fait son propre setUp/tearDown).
 *
 * @package OliTheme\Tests
 */

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    fwrite(STDERR, "Autoloader introuvable. Lancer 'composer install' avant les tests.\n");
    exit(1);
}

require_once $autoload;
