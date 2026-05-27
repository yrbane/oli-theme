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
final class PageController
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
    ) {
    }

    /**
     * Rend la page singulière courante. Retourne du HTML prêt à imprimer.
     */
    public function renderSingular(): string
    {
        $id = (int) get_queried_object_id();
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
        $vm['bodyClasses'] = \sprintf(
            '%spage page-id-%d lang-%s',
            $this->isFrontPage($entity->id) ? 'home ' : '',
            $entity->id,
            $entity->language->code,
        );

        $split = $this->coverExtractor->split($entity->content);
        $vm['coverHtml'] = $split['cover'];
        $vm['bodyHtml']  = $split['body'];

        // Routing spécial pour les pages galerie : on rend un template dédié
        // avec la liste de photos ou vidéos passée en plus du contenu normal.
        if ($this->gallery !== null) {
            if (\in_array($entity->slug, self::PHOTO_SLUGS, true)) {
                $vm['photos'] = $this->gallery->getPhotos();
                $vm['hasPhotos'] = $vm['photos'] !== [];
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
