<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\Core\RendererInterface;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;

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
        private readonly RendererInterface $renderer,
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
            return $this->renderer->render('pages/404', $this->buildBaseViewModel($id));
        }

        return $this->renderer->render('pages/page', $this->buildViewModel($entity));
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
        return [
            'lang' => $this->resolver->current(),
            'languageSwitcher' => $this->switcher->build($currentPostId),
            'bodyClasses' => 'lang-' . $this->resolver->current()->code,
        ];
    }
}
