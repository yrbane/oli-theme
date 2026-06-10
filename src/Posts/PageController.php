<?php

declare(strict_types=1);

namespace OliTheme\Posts;

use OliTheme\Core\RendererInterface;
use OliTheme\Gallery\GalleryRepository;
use OliTheme\I18n\LanguageResolverInterface;
use OliTheme\I18n\LanguageSwitcherControllerInterface;
use OliTheme\I18n\TranslationModelInterface;
use OliTheme\Seo\BreadcrumbsControllerInterface;
use OliTheme\Seo\SeoControllerInterface;

/**
 * Controller pour le rendu d'une page WordPress (singular `page`).
 *
 * @package OliTheme\Posts
 *
 * @since 1.0.0
 */
final class PageController implements PageRendererInterface
{
    /** Slugs des pages traitées comme galerie photos. */
    private const PHOTO_SLUGS = ['photos', 'photos-en'];

    /** Slugs des pages traitées comme galerie vidéos. */
    private const VIDEO_SLUGS = ['videos', 'videos-en'];

    public function __construct(
        private readonly PostModelInterface $posts,
        private readonly LanguageResolverInterface $resolver,
        private readonly LanguageSwitcherControllerInterface $switcher,
        private readonly \OliTheme\Navigation\MenuControllerInterface $menus,
        private readonly SeoControllerInterface $seo,
        private readonly BreadcrumbsControllerInterface $breadcrumbs,
        private readonly RendererInterface $renderer,
        private readonly CoverExtractor $coverExtractor = new CoverExtractor(),
        private readonly ?GalleryRepository $gallery = null,
        private readonly ?TranslationModelInterface $translations = null,
        private readonly ?\OliTheme\Gabarits\GabaritResolver $gabarits = null,
        private readonly ?\OliTheme\Gabarits\ZoneContentRepository $gabaritZones = null,
        private readonly ?\OliTheme\Gabarits\GabaritRenderer $gabaritRenderer = null,
        private readonly ?\OliTheme\MediaFolders\MediaFolderQuery $folderQuery = null,
        private readonly ?\OliTheme\MediaFolders\MediaFoldersGallerySettings $gallerySettings = null,
    ) {
    }

    /**
     * Rend la page singulière courante. Retourne du HTML prêt à imprimer.
     */
    public function renderSingular(): string
    {
        return $this->renderById((int) get_queried_object_id());
    }

    /**
     * Rend une page par son id WP. Utilisé notamment par le front-page
     * controller pour rendre la traduction de `page_on_front`.
     */
    public function renderById(int $id): string
    {
        $entity = $id > 0 ? $this->posts->find($id) : null;

        if (! $entity instanceof PostEntity) {
            $current = $this->resolver->current();
            $vm = $this->buildBaseViewModel(0);
            $vm['seo'] = $this->seo->buildFor404($current);
            $vm['crumbs'] = $this->breadcrumbs->buildFor404($current);
            return $this->renderer->render('pages/404.html', $vm);
        }

        $vm = $this->buildBaseViewModel($entity->id);
        $vm['post'] = $entity;
        $vm['seo'] = $this->seo->buildForPost($entity);
        $vm['crumbs'] = $this->breadcrumbs->buildForPost($entity);

        // La classe `home` distingue la page d'accueil des pages internes :
        // elle pilote la bannière `--oli-internal-banner-url`, masquée sur la
        // home (qui affiche déjà le carousel plein écran JS) via `body:not(.home)`
        // côté CSS. Le carousel d'accueil est rendu côté client par
        // assets/js/home-carousel.js (variation Olikalari) ; aucun rendu serveur.
        $vm['bodyClasses'] = \OliTheme\Theme::applyBodyClassesFilter(\sprintf(
            '%spage page-id-%d lang-%s',
            $this->isFrontPage($entity->id) ? 'home ' : '',
            $entity->id,
            $entity->language->code,
        ));

        $split = $this->coverExtractor->split($entity->content);
        $vm['coverHtml'] = $split['cover'];
        $vm['bodyHtml']  = $split['body'];

        // Si un gabarit zonal s'applique à ce post, son rendu remplace bodyHtml.
        $gabaritHtml = $this->renderGabaritIfZonal($entity->id);
        if ($gabaritHtml !== '') {
            $vm['bodyHtml'] = $gabaritHtml;
        }

        // Routing spécial pour les pages galerie : on rend un template dédié
        // avec la liste de photos ou vidéos passée en plus du contenu normal.
        if ($this->gallery !== null) {
            if (\in_array($entity->slug, self::PHOTO_SLUGS, true)) {
                // Galeries par dossier (sélection éditoriale ou fallback) :
                // chaque dossier devient un bucket pour les filtres front.
                $vm['folderGalleries']    = $this->buildFolderGalleries();
                $vm['hasFolderGalleries'] = $vm['folderGalleries'] !== [];
                // « Tous » = union dédupliquée des photos des dossiers exposés.
                $vm['photos']    = $this->aggregateAllPhotos($vm['folderGalleries']);
                $vm['hasPhotos'] = $vm['photos'] !== [];
                $vm['hasAnyGallery']      = $vm['hasPhotos'] || $vm['hasFolderGalleries'];
                // Données consommées par le JS de filtres dossiers : permet
                // de basculer la galerie principale entre `all` et chaque
                // dossier sans recharger la page.
                $vm['galleryDataJson']    = $this->buildGalleryFiltersJson(
                    $vm['photos'],
                    $vm['folderGalleries'],
                );
                return $this->renderer->render('pages/gallery-photos.html', $vm);
            }
            if (\in_array($entity->slug, self::VIDEO_SLUGS, true)) {
                $vm['videos']  = $this->gallery->getVideos();
                $vm['hasVideos'] = $vm['videos'] !== [];
                $vm['channelUrl'] = $this->gallery->getYoutubeChannel();
                return $this->renderer->render('pages/gallery-videos.html', $vm);
            }
        }

        return $this->renderer->render('pages/page.html', $vm);
    }

    /**
     * Construit la liste des galeries par dossier (chaque dossier non vide
     * et de premier niveau = une section avec ses photos et ses sous-dossiers).
     *
     * @return list<array{slug:string, name:string, photos:list<array<string, mixed>>}>
     */
    private function buildFolderGalleries(): array
    {
        if ($this->folderQuery === null) {
            return [];
        }
        // Sélection éditoriale (Médias → Galerie photos). Si l'éditeur a
        // coché des dossiers, on respecte cet ordre exactement ; sinon on
        // rend tous les dossiers racine non vides (fallback historique).
        $configured = $this->gallerySettings?->getConfiguredFolders() ?? [];
        $foldersBySlug = [];
        foreach ($this->folderQuery->allFolders() as $folder) {
            $foldersBySlug[$folder['slug']] = $folder;
        }
        $ordered = $configured !== []
            ? array_values(array_filter(
                array_map(static fn (string $slug) => $foldersBySlug[$slug] ?? null, $configured),
            ))
            : array_values(array_filter(
                $foldersBySlug,
                static fn (array $folder): bool => $folder['parent'] === 0,
            ));

        $out = [];
        foreach ($ordered as $folder) {
            $photos = $this->folderQuery->photosInFolder($folder['slug'], includeChildren: true);
            if (empty($photos)) {
                continue;
            }
            $out[] = [
                'slug'   => $folder['slug'],
                'name'   => $folder['name'],
                'photos' => $photos,
            ];
        }
        return $out;
    }

    /**
     * Agrège les photos de tous les dossiers exposés en une liste unique
     * pour le filtre « Tous », en dédupliquant par identifiant attachment.
     * Si la même photo est rangée dans plusieurs dossiers, elle n'apparaît
     * qu'une seule fois.
     *
     * @param list<array{slug:string, name:string, photos:list<array<string, mixed>>}> $folderGalleries
     *
     * @return list<array<string, mixed>>
     */
    private function aggregateAllPhotos(array $folderGalleries): array
    {
        $seen = [];
        $out  = [];
        foreach ($folderGalleries as $folder) {
            foreach ($folder['photos'] as $photo) {
                $id = (int) ($photo['id'] ?? 0);
                if ($id <= 0 || isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $out[]     = $photo;
            }
        }

        return $out;
    }

    /**
     * Sérialise la donnée des filtres galerie en JSON pour le client.
     *
     * Forme : `{"all": [...allPhotos], "folder-<slug>": [...photosDossier], ...}`.
     *
     * @param list<array<string, mixed>>                                              $allPhotos
     * @param list<array{slug:string, name:string, photos:list<array<string, mixed>>}> $folderGalleries
     */
    private function buildGalleryFiltersJson(array $allPhotos, array $folderGalleries): string
    {
        $buckets = ['all' => $allPhotos];
        foreach ($folderGalleries as $folder) {
            $buckets['folder-' . $folder['slug']] = $folder['photos'];
        }

        return (string) json_encode($buckets, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Si le post a un gabarit zonal avec un template custom, rend le HTML
     * du gabarit (à utiliser à la place du body extrait du content WP).
     */
    private function renderGabaritIfZonal(int $postId): string
    {
        if ($this->gabarits === null || $this->gabaritZones === null || $this->gabaritRenderer === null) {
            return '';
        }
        $gabarit = $this->gabarits->forPost($postId);
        if ($gabarit === null || !$gabarit->isZonal() || !$gabarit->hasCustomTemplate()) {
            return '';
        }
        $contents = $this->gabaritZones->load($postId);
        $post     = \function_exists('get_post') ? get_post($postId) : null;
        return $this->gabaritRenderer->render($gabarit, $contents, $post instanceof \WP_Post ? $post : null);
    }

    /**
     * Indique si le post courant est la page d'accueil statique — y compris
     * pour ses traductions. WordPress stocke un seul ID dans `page_on_front`
     * (typiquement la langue par défaut), donc une simple égalité d'ID ne
     * couvre pas les traductions. On considère toute page liée au même
     * groupe de traduction que `page_on_front` comme front page de sa langue.
     */
    private function isFrontPage(int $postId): bool
    {
        $frontId = (int) get_option('page_on_front', 0);
        if ($frontId <= 0) {
            return false;
        }
        if ($frontId === $postId) {
            return true;
        }
        if ($this->translations === null) {
            return false;
        }
        return \in_array($postId, $this->translations->getTranslations($frontId), true);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBaseViewModel(int $currentPostId = 0): array
    {
        $current = $this->resolver->current();

        return [
            'lang'             => $current,
            'languageSwitcher' => $this->switcher->build($currentPostId),
            'primaryMenu'      => $this->menus->buildPrimary($current),
            'footerMenu'       => $this->menus->buildFooter($current),
            'bodyClasses'      => 'lang-' . $current->code,
        ];
    }
}
