<?php

declare(strict_types=1);

namespace OliTheme\Events;

/**
 * Contrat du contrôleur de rendu de l'archive des événements.
 *
 * @package OliTheme\Events
 *
 * @since 1.0.0
 */
interface EventArchiveControllerInterface
{
    /**
     * Rend la page d'archive des événements. Retourne du HTML prêt à imprimer.
     *
     * @param int $limit Nombre maximum d'événements par liste.
     */
    public function renderArchive(int $limit = 10): string;
}
