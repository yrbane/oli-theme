<?php

declare(strict_types=1);

namespace OliTheme\Slides;

/**
 * ViewModel immuable du carrousel d'accueil.
 *
 * Regroupe les slides actifs et les paramètres de comportement du carrousel
 * (lecture automatique, intervalle, boucle).
 *
 * @package OliTheme\Slides
 *
 * @since 1.0.0
 */
final readonly class HomeCarouselViewModel
{
    /**
     * @param SlideEntity[] $slides Liste des slides actifs à afficher.
     * @param bool $autoplay Lecture automatique du carrousel.
     * @param int $intervalMs Intervalle entre slides en millisecondes.
     * @param bool $loop Boucle infinie du carrousel.
     */
    public function __construct(
        public array $slides,
        public bool $autoplay = true,
        public int $intervalMs = 5000,
        public bool $loop = true,
    ) {
    }
}
