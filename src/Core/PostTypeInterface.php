<?php

declare(strict_types=1);

namespace OliTheme\Core;

/**
 * Contrat des classes responsables d'enregistrer un Custom Post Type.
 *
 * Implémenté par {@see \OliTheme\Events\EventCpt}, {@see \OliTheme\Slides\SlideCpt}, etc.
 * La méthode register() doit être branchée sur le hook 'init' par le module
 * porteur (et non depuis l'implémentation elle-même).
 *
 * @package OliTheme\Core
 *
 * @since 1.0.0
 */
interface PostTypeInterface
{
    /**
     * Enregistre le Custom Post Type via register_post_type() et la taxonomie associée si besoin.
     */
    public function register(): void;
}
