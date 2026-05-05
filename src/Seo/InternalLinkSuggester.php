<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Posts\PostEntity;
use OliTheme\Posts\PostModelInterface;

/**
 * Suggère des articles pour le maillage interne d'un contenu donné.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class InternalLinkSuggester
{
    public function __construct(private readonly PostModelInterface $posts)
    {
    }

    /**
     * Retourne des articles suggérés pour le maillage interne.
     *
     * Exclut l'article courant et les articles déjà liés dans le contenu.
     *
     * @return PostEntity[] Articles suggérés pour maillage interne.
     */
    public function suggestFor(PostEntity $current, int $limit = 5): array
    {
        $candidates = $this->posts->findByLanguage($current->language, 50);
        $suggestions = [];
        foreach ($candidates as $candidate) {
            if ($candidate->id === $current->id) {
                continue;
            }
            // Ignore les articles déjà liés dans le contenu courant.
            if (str_contains($current->content, $candidate->permalink)) {
                continue;
            }
            $suggestions[] = $candidate;
            if (\count($suggestions) >= $limit) {
                break;
            }
        }
        return $suggestions;
    }
}
