<?php

declare(strict_types=1);

namespace OliTheme\Events;

use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;
use OliTheme\Seo\BreadcrumbsControllerInterface;
use OliTheme\Seo\SeoControllerInterface;

/**
 * Contrôleur pour le rendu d'un événement singulier.
 *
 * @package OliTheme\Events
 *
 * @since 1.0.0
 */
final class EventController implements EventControllerInterface
{
    /**
     * @param EventModelInterface $events Modèle d'événements.
     * @param LanguageResolverInterface $resolver Résolveur de langue courante.
     * @param LanguageSwitcherControllerInterface $switcher Contrôleur du sélecteur de langue.
     * @param \OliTheme\Navigation\MenuControllerInterface $menus Contrôleur des menus de navigation.
     * @param SeoControllerInterface $seo Contrôleur SEO.
     * @param BreadcrumbsControllerInterface $breadcrumbs Contrôleur fil d'Ariane.
     * @param RendererInterface $renderer Moteur de rendu de templates.
     */
    public function __construct(
        private readonly EventModelInterface $events,
        private readonly LanguageResolverInterface $resolver,
        private readonly LanguageSwitcherControllerInterface $switcher,
        private readonly \OliTheme\Navigation\MenuControllerInterface $menus,
        private readonly SeoControllerInterface $seo,
        private readonly BreadcrumbsControllerInterface $breadcrumbs,
        private readonly RendererInterface $renderer,
    ) {
    }

    /**
     * Rend la page singulière de l'événement courant. Retourne du HTML prêt à imprimer.
     */
    public function renderSingle(): string
    {
        $id = (int) get_queried_object_id();
        $entity = $id > 0 ? $this->events->findById($id) : null;

        if (! $entity instanceof EventEntity) {
            $current = $this->resolver->current();

            return $this->renderer->render('pages/404.html', [
                'lang' => $current,
                'languageSwitcher' => $this->switcher->build(0),
                'primaryMenu' => $this->menus->buildPrimary($current),
                'footerMenu' => $this->menus->buildFooter($current),
                'seo' => $this->seo->buildFor404($current),
                'crumbs' => $this->breadcrumbs->buildFor404($current),
                'bodyClasses' => 'lang-' . $current->code,
            ]);
        }

        $current = $this->resolver->current();

        return $this->renderer->render('pages/single-event.html', [
            'event' => $entity,
            'lang' => $current,
            'languageSwitcher' => $this->switcher->build($entity->id),
            'primaryMenu' => $this->menus->buildPrimary($current),
            'footerMenu' => $this->menus->buildFooter($current),
            'seo' => $this->seo->buildForEvent($entity),
            'crumbs' => $this->breadcrumbs->buildForEvent($entity),
            'bodyClasses' => \OliTheme\Theme::applyBodyClassesFilter(\sprintf('single single-event event-id-%d lang-%s', $entity->id, $entity->language->code)),
        ]);
    }
}
