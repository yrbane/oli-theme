<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Events\EventEntity;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;

/**
 * Construit les fils d'Ariane pour les différents types de pages du thème.
 *
 * Utilise home_url() directement (fonction WordPress globale).
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class BreadcrumbsController implements BreadcrumbsControllerInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildForPost(PostEntity $post): array
    {
        $home = new BreadcrumbItemEntity(
            label: 'Accueil',
            url: home_url('/'),
            isCurrent: false,
        );

        if ($post->type === 'post') {
            $archive = new BreadcrumbItemEntity(
                label: 'Actualités',
                url: home_url('/' . $post->language->code . '/actualites/'),
                isCurrent: false,
            );

            return [
                $home,
                $archive,
                new BreadcrumbItemEntity(
                    label: $post->title,
                    url: $post->permalink,
                    isCurrent: true,
                ),
            ];
        }

        return [
            $home,
            new BreadcrumbItemEntity(
                label: $post->title,
                url: $post->permalink,
                isCurrent: true,
            ),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function buildForEvent(EventEntity $event): array
    {
        return [
            new BreadcrumbItemEntity(
                label: 'Accueil',
                url: home_url('/'),
                isCurrent: false,
            ),
            new BreadcrumbItemEntity(
                label: 'Événements',
                url: home_url('/' . $event->language->code . '/evenements/'),
                isCurrent: false,
            ),
            new BreadcrumbItemEntity(
                label: $event->title,
                url: $event->permalink,
                isCurrent: true,
            ),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function buildForArchive(string $type, Language $language): array
    {
        $home = new BreadcrumbItemEntity(
            label: 'Accueil',
            url: home_url('/'),
            isCurrent: false,
        );

        if ($type === 'post') {
            return [
                $home,
                new BreadcrumbItemEntity(
                    label: 'Actualités',
                    url: home_url('/' . $language->code . '/actualites/'),
                    isCurrent: true,
                ),
            ];
        }

        if ($type === 'oli_event') {
            return [
                $home,
                new BreadcrumbItemEntity(
                    label: 'Événements',
                    url: home_url('/' . $language->code . '/evenements/'),
                    isCurrent: true,
                ),
            ];
        }

        return [$home];
    }

    /**
     * {@inheritDoc}
     */
    public function buildForSearch(string $query, Language $language): array
    {
        return [
            new BreadcrumbItemEntity(
                label: 'Accueil',
                url: home_url('/'),
                isCurrent: false,
            ),
            new BreadcrumbItemEntity(
                label: 'Recherche : ' . $query,
                url: home_url('/' . $language->code . '/?s=' . $query),
                isCurrent: true,
            ),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function buildFor404(Language $language): array
    {
        return [
            new BreadcrumbItemEntity(
                label: 'Accueil',
                url: home_url('/'),
                isCurrent: false,
            ),
            new BreadcrumbItemEntity(
                label: 'Page introuvable',
                url: home_url('/' . $language->code . '/404/'),
                isCurrent: true,
            ),
        ];
    }
}
