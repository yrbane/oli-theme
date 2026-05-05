<?php

declare(strict_types=1);

namespace OliTheme\Slides;

use OliTheme\I18n\LanguageResolverInterface;

/**
 * Contrôleur du carrousel d'accueil.
 *
 * Résout la langue courante, récupère les slides actifs via le modèle et
 * retourne un ViewModel immuable prêt à l'emploi pour la vue.
 *
 * @package OliTheme\Slides
 *
 * @since 1.0.0
 */
final class HomeCarouselController implements HomeCarouselControllerInterface
{
    /**
     * @param SlideModelInterface $slides Modèle de récupération des slides.
     * @param LanguageResolverInterface $resolver Résolveur de langue courante.
     */
    public function __construct(
        private readonly SlideModelInterface $slides,
        private readonly LanguageResolverInterface $resolver,
    ) {
    }

    /**
     * Construit le ViewModel du carrousel pour la langue courante.
     */
    public function build(): HomeCarouselViewModel
    {
        $current = $this->resolver->current();

        return new HomeCarouselViewModel(
            slides: $this->slides->findActive($current),
        );
    }
}
