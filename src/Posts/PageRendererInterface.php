<?php

declare(strict_types=1);

namespace OliTheme\Posts;

/**
 * Contrat minimal pour le rendu d'une page singulière par son ID.
 *
 * Permet à {@see FrontPageController} de dépendre d'une abstraction
 * plutôt que de la classe finale {@see PageController}.
 *
 * @package OliTheme\Posts
 *
 * @since 1.2.0
 */
interface PageRendererInterface
{
    /**
     * Rend la page singulière correspondant à l'ID donné.
     */
    public function renderById(int $id): string;
}
