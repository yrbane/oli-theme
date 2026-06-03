<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\TranslationModelInterface;

/**
 * Controller pour le rendu de la page d'accueil (front page).
 *
 * Si `page_on_front` est défini : rend la page correspondante en tenant
 * compte de la langue courante (cherche la traduction du groupe pour la
 * langue active si elle existe).
 *
 * Si `page_on_front` n'est pas défini : délègue à {@see PostController}
 * pour rendre l'archive des articles filtrée par la langue courante.
 *
 * @package OliTheme\Posts
 *
 * @since 1.2.0
 */
final class FrontPageController
{
    public function __construct(
        private readonly PageRendererInterface $page,
        private readonly ArchiveRendererInterface $post,
        private readonly TranslationModelInterface $translations,
        private readonly LanguageResolverInterface $resolver,
        private readonly string $defaultLanguageCode,
    ) {
    }

    /**
     * Rend la home dans la langue courante. Retourne du HTML prêt à imprimer.
     */
    public function render(): string
    {
        $frontId = (int) get_option('page_on_front', 0);
        if ($frontId <= 0) {
            return $this->post->renderArchive();
        }

        $current = $this->resolver->current();
        if ($current->code !== $this->defaultLanguageCode) {
            $translations = $this->translations->getTranslations($frontId);
            if (isset($translations[$current->code])) {
                $frontId = $translations[$current->code];
            }
        }

        return $this->page->renderById($frontId);
    }
}
