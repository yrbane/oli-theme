<?php

declare(strict_types=1);

namespace OliTheme\Slides;

use OliTheme\I18n\Language;

/**
 * Contrat du modèle de récupération des slides.
 *
 * @package OliTheme\Slides
 *
 * @since 1.0.0
 */
interface SlideModelInterface
{
    /**
     * Retourne les slides actifs pour une langue et une limite données.
     *
     * @return SlideEntity[]
     */
    public function findActive(Language $language, int $limit = 10): array;

    /**
     * Retourne un slide par son identifiant WordPress, ou null si introuvable.
     */
    public function findById(int $id): ?SlideEntity;
}
