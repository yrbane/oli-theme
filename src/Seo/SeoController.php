<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\Events\EventEntity;
use OliTheme\I18n\Language;
use OliTheme\Posts\PostEntity;
use OliTheme\Seo\Schema\ArticleSchema;
use OliTheme\Seo\Schema\BreadcrumbListSchema;
use OliTheme\Seo\Schema\EventSchema;
use OliTheme\Seo\Schema\OrganizationSchema;
use OliTheme\Seo\Schema\SchemaContext;
use OliTheme\Seo\Schema\WebSiteSchema;

/**
 * Orchestrateur SEO : assemble le SeoHeadViewModel complet selon le contexte
 * (post, événement, archive, recherche, 404).
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class SeoController implements SeoControllerInterface
{
    public function __construct(
        private readonly SeoMetaModelInterface $meta,
        private readonly CanonicalBuilder $canonical,
        private readonly HreflangBuilder $hreflang,
        private readonly RobotsBuilder $robots,
        private readonly OpenGraphBuilder $og,
        private readonly TwitterCardBuilder $twitter,
        private readonly BreadcrumbsControllerInterface $breadcrumbs,
    ) {
    }

    public function buildForPost(PostEntity $post): SeoHeadViewModel
    {
        $meta = $this->meta->find($post->id);
        $canonical = $this->canonical->build($post->id, $meta->canonical);
        $title = $meta->title ?? $post->title;
        $description = $meta->description ?? ($post->excerpt ?? '');

        $context = $this->makeBaseContext($canonical, $post->language);
        $context->add(new ArticleSchema($post, rtrim((string) home_url('/'), '/') . '/#organization'));
        $context->add(new BreadcrumbListSchema($this->crumbsToArray($this->breadcrumbs->buildForPost($post))));

        return new SeoHeadViewModel(
            title: $this->formatTitle($title),
            description: mb_substr($description, 0, 158),
            robots: $this->robots->build($meta),
            canonical: $canonical,
            hreflangs: $this->hreflang->build($post->id),
            og: $this->og->build($meta, $post, $post->language, $canonical),
            twitter: $this->twitter->build($meta, $post),
            jsonLd: $context->toJsonLd(),
        );
    }

    public function buildForEvent(EventEntity $event): SeoHeadViewModel
    {
        $meta = $this->meta->find($event->id);
        $canonical = $this->canonical->build($event->id, $meta->canonical);
        $title = $meta->title ?? $event->title;
        $description = $meta->description ?? ($event->excerpt ?? '');

        $context = $this->makeBaseContext($canonical, $event->language);
        $context->add(new EventSchema($event));
        $context->add(new BreadcrumbListSchema($this->crumbsToArray($this->breadcrumbs->buildForEvent($event))));

        return new SeoHeadViewModel(
            title: $this->formatTitle($title),
            description: mb_substr($description, 0, 158),
            robots: $this->robots->build($meta),
            canonical: $canonical,
            hreflangs: $this->hreflang->build($event->id),
            og: $this->og->build($meta, $event, $event->language, $canonical),
            twitter: $this->twitter->build($meta, null),
            jsonLd: $context->toJsonLd(),
        );
    }

    public function buildForArchive(string $type, Language $language): SeoHeadViewModel
    {
        $meta = new SeoMeta(
            title: null,
            description: null,
            focusKeyword: null,
            additionalKeywords: [],
            ogImageId: null,
            twitterCardType: 'summary',
            noindex: false,
            nofollow: false,
            canonical: null,
            priority: null,
            changefreq: null,
            readabilityScore: null,
            seoScore: null,
        );
        $canonical = (string) home_url('/' . $language->code . '/');
        $title = $type === 'oli_event' ? __('Événements', 'oli-theme') : __('Actualités', 'oli-theme');

        $context = $this->makeBaseContext($canonical, $language);
        $context->add(new BreadcrumbListSchema($this->crumbsToArray($this->breadcrumbs->buildForArchive($type, $language))));

        return new SeoHeadViewModel(
            title: $this->formatTitle((string) $title),
            description: '',
            robots: $this->robots->build($meta),
            canonical: $canonical,
            hreflangs: [],
            og: $this->og->build($meta, null, $language, $canonical),
            twitter: $this->twitter->build($meta, null),
            jsonLd: $context->toJsonLd(),
        );
    }

    public function buildForSearch(string $query, Language $language): SeoHeadViewModel
    {
        $meta = new SeoMeta(
            title: null,
            description: null,
            focusKeyword: null,
            additionalKeywords: [],
            ogImageId: null,
            twitterCardType: 'summary',
            noindex: true,
            nofollow: false,
            canonical: null,
            priority: null,
            changefreq: null,
            readabilityScore: null,
            seoScore: null,
        );
        $canonical = (string) home_url('/?s=' . rawurlencode($query));
        $title = \sprintf((string) __('Recherche : %s', 'oli-theme'), $query);

        $context = $this->makeBaseContext($canonical, $language);
        $context->add(new BreadcrumbListSchema($this->crumbsToArray($this->breadcrumbs->buildForSearch($query, $language))));

        return new SeoHeadViewModel(
            title: $this->formatTitle($title),
            description: '',
            robots: $this->robots->build($meta),
            canonical: $canonical,
            hreflangs: [],
            og: $this->og->build($meta, null, $language, $canonical),
            twitter: $this->twitter->build($meta, null),
            jsonLd: $context->toJsonLd(),
        );
    }

    public function buildFor404(Language $language): SeoHeadViewModel
    {
        $meta = new SeoMeta(
            title: null,
            description: null,
            focusKeyword: null,
            additionalKeywords: [],
            ogImageId: null,
            twitterCardType: 'summary',
            noindex: true,
            nofollow: false,
            canonical: null,
            priority: null,
            changefreq: null,
            readabilityScore: null,
            seoScore: null,
        );
        $canonical = (string) home_url('/');
        $title = (string) __('Page introuvable', 'oli-theme');

        $context = $this->makeBaseContext($canonical, $language);
        $context->add(new BreadcrumbListSchema($this->crumbsToArray($this->breadcrumbs->buildFor404($language))));

        return new SeoHeadViewModel(
            title: $this->formatTitle($title),
            description: '',
            robots: $this->robots->build($meta),
            canonical: $canonical,
            hreflangs: [],
            og: $this->og->build($meta, null, $language, $canonical),
            twitter: $this->twitter->build($meta, null),
            jsonLd: $context->toJsonLd(),
        );
    }

    private function makeBaseContext(string $url, Language $language): SchemaContext
    {
        $siteUrl = (string) home_url('/');
        $siteName = (string) get_bloginfo('name');
        $context = new SchemaContext();
        $context->add(new WebSiteSchema($siteName, $siteUrl, $siteUrl . '?s={search_term_string}'));
        $context->add(new OrganizationSchema($siteName, $siteUrl));
        return $context;
    }

    private function formatTitle(string $title): string
    {
        $separator = (string) apply_filters('oli_seo_title_separator', ' — ');
        $siteName = (string) get_bloginfo('name');
        return $title . $separator . $siteName;
    }

    /**
     * @param BreadcrumbItemEntity[] $crumbs
     *
     * @return array<int, array{label: string, url: string, isCurrent: bool}>
     */
    private function crumbsToArray(array $crumbs): array
    {
        return array_map(
            static fn (BreadcrumbItemEntity $crumb): array => [
                'label' => $crumb->label,
                'url' => $crumb->url,
                'isCurrent' => $crumb->isCurrent,
            ],
            $crumbs,
        );
    }
}
