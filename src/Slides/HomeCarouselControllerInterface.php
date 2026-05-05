<?php

declare(strict_types=1);

namespace OliTheme\Slides;

/**
 * Contrat du contrôleur de carrousel d'accueil.
 *
 * @package OliTheme\Slides
 *
 * @since 1.0.0
 */
interface HomeCarouselControllerInterface
{
    /**
     * Construit le ViewModel du carrousel pour la langue courante.
     */
    public function build(): HomeCarouselViewModel;
}
