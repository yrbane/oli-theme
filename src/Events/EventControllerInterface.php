<?php

declare(strict_types=1);

namespace OliTheme\Events;

/**
 * Contrat du contrôleur de rendu d'un événement singulier.
 *
 * @package OliTheme\Events
 *
 * @since 1.0.0
 */
interface EventControllerInterface
{
    /**
     * Rend la page singulière de l'événement courant. Retourne du HTML prêt à imprimer.
     */
    public function renderSingle(): string;
}
