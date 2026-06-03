<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\TranslationModelInterface;

/**
 * Controller pour le rendu de la page d'accueil (front page).
 *
 * Respecte la sémantique WP standard :
 *  - `show_on_front === 'page'` ET `page_on_front > 0` → rend la page
 *    statique correspondante, en tenant compte de la langue courante
 *    (cherche la traduction du groupe pour la langue active si elle existe).
 *  - Sinon (i.e. `show_on_front === 'posts'` OU pas de `page_on_front`) :
 *    délègue à {@see PostController} pour rendre l'archive des articles
 *    filtrée par la langue courante.
 *
 * Régression historique : la première version ne testait que
 * `page_on_front > 0`, ignorant `show_on_front`. Conséquence : sur un
 * site configuré pour afficher les articles mais avec un legacy
 * `page_on_front` non nul, la home affichait une page vide au lieu de
 * l'archive des articles.
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
        // Sémantique WP : `page_on_front` n'est pris en compte QUE si
        // `show_on_front === 'page'`. Sinon on rend l'archive des articles,
        // même si `page_on_front` contient un ID résiduel d'ancienne config.
        $showOnFront = (string) get_option('show_on_front', 'posts');
        $frontId     = (int) get_option('page_on_front', 0);
        if ($showOnFront !== 'page' || $frontId <= 0) {
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
