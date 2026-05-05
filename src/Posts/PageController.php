<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;
use OliTheme\Slides\HomeCarouselControllerInterface;

/**
 * Controller pour le rendu d'une page WordPress (singular `page`).
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class PageController
{
    public function __construct(
        private readonly PostModelInterface $posts,
        private readonly LanguageResolverInterface $resolver,
        private readonly LanguageSwitcherControllerInterface $switcher,
        private readonly \OliTheme\Navigation\MenuControllerInterface $menus,
        private readonly RendererInterface $renderer,
        private readonly HomeCarouselControllerInterface $carousel,
    ) {
    }

    /**
     * Rend la page singulière courante. Retourne du HTML prêt à imprimer.
     */
    public function renderSingular(): string
    {
        $id = (int) get_queried_object_id();
        $entity = $id > 0 ? $this->posts->find($id) : null;

        if (! $entity instanceof PostEntity) {
            return $this->renderer->render('pages/404.html', $this->buildBaseViewModel($id));
        }

        $viewModel = $this->buildViewModel($entity);
        if ($this->isFrontPage($entity->id)) {
            $viewModel['carousel'] = $this->carousel->build();
        }

        return $this->renderer->render('pages/page.html', $viewModel);
    }

    /**
     * Indique si le post courant est la page d'accueil statique.
     */
    private function isFrontPage(int $postId): bool
    {
        return (int) get_option('page_on_front', 0) === $postId;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildViewModel(PostEntity $entity): array
    {
        $base = $this->buildBaseViewModel($entity->id);
        $base['post'] = $entity;
        $base['bodyClasses'] = \sprintf(
            'page page-id-%d lang-%s',
            $entity->id,
            $entity->language->code,
        );

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBaseViewModel(int $currentPostId = 0): array
    {
        $current = $this->resolver->current();

        return [
            'lang'             => $current,
            'languageSwitcher' => $this->switcher->build($currentPostId),
            'primaryMenu'      => $this->menus->buildPrimary($current),
            'footerMenu'       => $this->menus->buildFooter($current),
            'bodyClasses'      => 'lang-' . $current->code,
        ];
    }
}
