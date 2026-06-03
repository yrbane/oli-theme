<?php

declare(strict_types=1);

namespace OliTheme\Posts;

/**
 * Contrat minimal pour le rendu de l'archive des articles.
 *
 * Permet à {@see FrontPageController} de dépendre d'une abstraction
 * plutôt que de la classe finale {@see PostController}.
 *
 * @package OliTheme\Posts
 *
 * @since 1.2.0
 */
interface ArchiveRendererInterface
{
    /**
     * Rend la liste des articles filtrée par la langue courante.
     */
    public function renderArchive(int $limit = 10): string;
}
