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

/**
 * Stub minimaliste de \WP_Term pour les tests unitaires (Brain Monkey ne
 * charge pas le cœur WP). Quelques classes du thème font des `instanceof
 * \WP_Term` à des fins de robustesse — sans cette définition, les tests qui
 * stubbent `get_terms()` ne pourraient pas livrer des objets typés.
 */
if (!class_exists('WP_Term')) {
    class WP_Term // phpcs:ignore
    {
        public int $term_id = 0;
        public string $name = '';
        public string $slug = '';
        public int $parent = 0;
        public int $count = 0;
        public string $taxonomy = '';

        public function __construct(?object $data = null)
        {
            if ($data === null) {
                return;
            }
            foreach (get_object_vars($data) as $k => $v) {
                if (property_exists($this, $k)) {
                    $this->{$k} = $v;
                }
            }
        }
    }
}
