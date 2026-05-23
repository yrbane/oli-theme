<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Events\EventEntity;
use OliTheme\I18n\Language;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\Posts\PostEntity;

/**
 * Construit les fils d'Ariane pour les différents types de pages du thème.
 *
 * Les libellés sont localisés via un dictionnaire interne par langue
 * (pas de dépendance au système de traductions WordPress, qui n'est pas
 * forcément chargé pour la langue courante côté front).
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class BreadcrumbsController implements BreadcrumbsControllerInterface
{
    /**
     * Dictionnaire de libellés par langue. Le fr est la source de vérité,
     * les autres langues retombent sur le fr si une clé manque.
     *
     * @var array<string, array<string, string>>
     */
    private const LABELS = [
        // Le préfixe 'search' inclut son séparateur final pour respecter la
        // typographie : « Recherche : » (espace insécable avant `:`) en fr,
        // « Search: » (collé) dans les autres langues.
        'fr' => [
            'home'       => 'Accueil',
            'news'       => 'Actualités',
            'events'     => 'Événements',
            'search'     => 'Recherche : ',
            'not_found'  => 'Page introuvable',
        ],
        'en' => [
            'home'       => 'Home',
            'news'       => 'News',
            'events'     => 'Events',
            'search'     => 'Search: ',
            'not_found'  => 'Page not found',
        ],
        'it' => [
            'home'       => 'Home',
            'news'       => 'Notizie',
            'events'     => 'Eventi',
            'search'     => 'Ricerca: ',
            'not_found'  => 'Pagina non trovata',
        ],
        'es' => [
            'home'       => 'Inicio',
            'news'       => 'Noticias',
            'events'     => 'Eventos',
            'search'     => 'Búsqueda: ',
            'not_found'  => 'Página no encontrada',
        ],
    ];
    /**
     * Le résolveur (optionnel) sert à privilégier la **langue active** sur
     * la langue du contenu pour les libellés. Cas concret : sur `/en/` la
     * home WP peut servir un post fr ; le breadcrumb doit dire « Home »
     * (langue de l'URL) et non « Accueil » (langue du post).
     */
    public function __construct(
        private readonly ?LanguageResolverInterface $resolver = null,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function buildForPost(PostEntity $post): array
    {
        $labelLang = $this->labelLanguage($post->language);
        $urlLang   = $post->language;
        $home      = $this->homeFor($labelLang);

        if ($post->type === 'post') {
            $archive = new BreadcrumbItemEntity(
                label: $this->t('news', $labelLang),
                url: home_url('/' . $urlLang->code . '/actualites/'),
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

        // Pour les pages, insère la chaîne d'ancêtres (post_parent → racine)
        // entre la home et la page courante. Ainsi `/a-propos/notre-equipe/`
        // donne « Accueil › À propos › Notre équipe ».
        $ancestors = $this->ancestorsFor($post->id);

        return [
            $home,
            ...$ancestors,
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
        $labelLang = $this->labelLanguage($event->language);
        $urlLang   = $event->language;

        return [
            $this->homeFor($labelLang),
            new BreadcrumbItemEntity(
                label: $this->t('events', $labelLang),
                url: home_url('/' . $urlLang->code . '/evenements/'),
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
        $home = $this->homeFor($language);

        if ($type === 'post') {
            return [
                $home,
                new BreadcrumbItemEntity(
                    label: $this->t('news', $language),
                    url: home_url('/' . $language->code . '/actualites/'),
                    isCurrent: true,
                ),
            ];
        }

        if ($type === 'oli_event') {
            return [
                $home,
                new BreadcrumbItemEntity(
                    label: $this->t('events', $language),
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
            $this->homeFor($language),
            new BreadcrumbItemEntity(
                label: $this->t('search', $language) . $query,
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
            $this->homeFor($language),
            new BreadcrumbItemEntity(
                label: $this->t('not_found', $language),
                url: home_url('/' . $language->code . '/404/'),
                isCurrent: true,
            ),
        ];
    }

    /**
     * Construit la liste des ancêtres d'une page (du plus haut au plus proche).
     *
     * @return BreadcrumbItemEntity[]
     */
    private function ancestorsFor(int $postId): array
    {
        if (!\function_exists('get_post_ancestors')) {
            return [];
        }

        // get_post_ancestors retourne du plus proche au plus lointain → on inverse.
        /** @var int[] $ids */
        $ids = (array) get_post_ancestors($postId);
        $ids = array_reverse($ids);

        $crumbs = [];
        foreach ($ids as $aid) {
            $title = \function_exists('get_the_title') ? (string) get_the_title($aid) : '';
            $url   = \function_exists('get_permalink') ? (string) get_permalink($aid) : '';
            if ($title === '') {
                continue;
            }
            $crumbs[] = new BreadcrumbItemEntity(label: $title, url: $url, isCurrent: false);
        }

        return $crumbs;
    }

    /**
     * Item « accueil » pour une langue donnée.
     */
    private function homeFor(Language $language): BreadcrumbItemEntity
    {
        return new BreadcrumbItemEntity(
            label: $this->t('home', $language),
            url: home_url('/'),
            isCurrent: false,
        );
    }

    /**
     * Langue à utiliser pour les libellés. Privilégie le résolveur (= langue
     * active de la requête) sur la langue de l'entité passée. Permet d'avoir
     * « Home » sur `/en/` même si la home WP sert un post fr.
     */
    private function labelLanguage(Language $fallback): Language
    {
        return $this->resolver?->current() ?? $fallback;
    }

    /**
     * Traduit une clé pour la langue donnée, fallback sur le fr.
     */
    private function t(string $key, Language $language): string
    {
        return self::LABELS[$language->code][$key] ?? self::LABELS['fr'][$key];
    }
}
