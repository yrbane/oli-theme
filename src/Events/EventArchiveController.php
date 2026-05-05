<?php

declare(strict_types=1);

namespace OliTheme\Events;

use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;

/**
 * Contrôleur pour le rendu de l'archive des événements.
 *
 * @package OliTheme\Events
 *
 * @since 1.0.0
 */
final class EventArchiveController implements EventArchiveControllerInterface
{
    /**
     * @param EventModelInterface $events Modèle d'événements.
     * @param LanguageResolverInterface $resolver Résolveur de langue courante.
     * @param LanguageSwitcherControllerInterface $switcher Contrôleur du sélecteur de langue.
     * @param \OliTheme\Navigation\MenuControllerInterface $menus Contrôleur des menus de navigation.
     * @param RendererInterface $renderer Moteur de rendu de templates.
     */
    public function __construct(
        private readonly EventModelInterface $events,
        private readonly LanguageResolverInterface $resolver,
        private readonly LanguageSwitcherControllerInterface $switcher,
        private readonly \OliTheme\Navigation\MenuControllerInterface $menus,
        private readonly RendererInterface $renderer,
    ) {
    }

    /**
     * Rend la page d'archive des événements. Retourne du HTML prêt à imprimer.
     *
     * @param int $limit Nombre maximum d'événements par liste.
     */
    public function renderArchive(int $limit = 10): string
    {
        $current = $this->resolver->current();
        $upcoming = $this->events->findUpcoming($current, $limit);
        $past = $this->events->findPast($current, $limit);

        return $this->renderer->render('pages/archive-event.html', [
            'upcomingEvents' => $upcoming,
            'pastEvents' => $past,
            'archiveTitle' => '',
            'lang' => $current,
            'languageSwitcher' => $this->switcher->build(0),
            'primaryMenu' => $this->menus->buildPrimary($current),
            'footerMenu' => $this->menus->buildFooter($current),
            'bodyClasses' => 'archive archive-event lang-' . $current->code,
        ]);
    }
}
