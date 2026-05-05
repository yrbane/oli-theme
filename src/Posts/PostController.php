<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;

/**
 * Controller pour les posts standards (single, archive, recherche).
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class PostController
{
    public function __construct(
        private readonly PostModelInterface $posts,
        private readonly LanguageResolverInterface $resolver,
        private readonly LanguageSwitcherControllerInterface $switcher,
        private readonly RendererInterface $renderer,
    ) {
    }

    /**
     * Rend le template d'un post singulier. Retourne du HTML prêt à imprimer.
     */
    public function renderSingle(): string
    {
        $id = (int) get_queried_object_id();
        $entity = $id > 0 ? $this->posts->find($id) : null;

        if (! $entity instanceof PostEntity) {
            return $this->renderer->render('pages/404.html', $this->buildBaseViewModel(0));
        }

        $viewModel = $this->buildBaseViewModel($entity->id);
        $viewModel['post'] = $entity;
        $viewModel['bodyClasses'] = \sprintf(
            'single single-post post-id-%d lang-%s',
            $entity->id,
            $entity->language->code,
        );

        return $this->renderer->render('pages/single-post.html', $viewModel);
    }

    /**
     * Rend le template d'archive des posts. Retourne du HTML prêt à imprimer.
     */
    public function renderArchive(int $limit = 10): string
    {
        $current = $this->resolver->current();
        $items   = $this->posts->findByLanguage($current, $limit);

        $viewModel = $this->buildBaseViewModel(0);
        $viewModel['posts']        = $items;
        $viewModel['archiveTitle'] = '';
        $viewModel['bodyClasses']  = 'archive archive-post lang-' . $current->code;

        return $this->renderer->render('pages/archive-post.html', $viewModel);
    }

    /**
     * Rend le template de résultats de recherche. Retourne du HTML prêt à imprimer.
     */
    public function renderSearch(int $limit = 20): string
    {
        $current = $this->resolver->current();
        $query   = (string) get_search_query();
        $items   = $query === '' ? [] : $this->posts->findByLanguage($current, $limit);

        $viewModel = $this->buildBaseViewModel(0);
        $viewModel['query']       = $query;
        $viewModel['posts']       = $items;
        $viewModel['bodyClasses'] = 'search lang-' . $current->code;

        return $this->renderer->render('pages/search.html', $viewModel);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBaseViewModel(int $currentPostId): array
    {
        $current = $this->resolver->current();

        return [
            'lang'             => $current,
            'languageSwitcher' => $this->switcher->build($currentPostId),
            'bodyClasses'      => 'lang-' . $current->code,
        ];
    }
}
