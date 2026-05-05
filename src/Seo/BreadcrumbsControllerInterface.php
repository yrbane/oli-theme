<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Events\EventEntity;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;

/**
 * Contrat du contrôleur de fil d'Ariane (breadcrumbs).
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
interface BreadcrumbsControllerInterface
{
    /**
     * Construit le fil d'Ariane pour un post ou une page.
     *
     * @return BreadcrumbItemEntity[]
     */
    public function buildForPost(PostEntity $post): array;

    /**
     * Construit le fil d'Ariane pour un événement.
     *
     * @return BreadcrumbItemEntity[]
     */
    public function buildForEvent(EventEntity $event): array;

    /**
     * Construit le fil d'Ariane pour une archive de type donné.
     *
     * @return BreadcrumbItemEntity[]
     */
    public function buildForArchive(string $type, Language $language): array;

    /**
     * Construit le fil d'Ariane pour une page de résultats de recherche.
     *
     * @return BreadcrumbItemEntity[]
     */
    public function buildForSearch(string $query, Language $language): array;

    /**
     * Construit le fil d'Ariane pour la page 404.
     *
     * @return BreadcrumbItemEntity[]
     */
    public function buildFor404(Language $language): array;
}
