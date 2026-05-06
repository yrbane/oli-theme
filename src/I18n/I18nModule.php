<?php

declare(strict_types=1);

namespace OliTheme\I18n;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;

/**
 * Module I18n : orchestre l'enregistrement des hooks WordPress du sous-système.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final class I18nModule implements ModuleInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        add_action('init', function (): void {
            $taxonomy = $this->container->get(LanguageTaxonomy::class);
            \assert($taxonomy instanceof LanguageTaxonomy);
            $taxonomy->register();

            $rules = $this->container->get(RewriteRules::class);
            \assert($rules instanceof RewriteRules);
            $rules->register();
        });

        add_filter('query_vars', function (array $vars): array {
            $rules = $this->container->get(RewriteRules::class);
            \assert($rules instanceof RewriteRules);

            return $rules->addQueryVar($vars);
        });

        add_filter('home_url', function (string $url, string $path): string {
            $filter = $this->container->get(LanguageUrlFilter::class);
            \assert($filter instanceof LanguageUrlFilter);

            return $filter->filterHomeUrl($url, $path);
        }, 10, 2);

        // Filtre les permaliens internes (pages, articles, CPT) pour conserver la langue
        // active sur les liens du menu, des cards et de la navigation interne.
        $permalinkFilter = function ($url): string {
            if (!\is_string($url)) {
                return (string) $url;
            }
            $filter = $this->container->get(LanguageUrlFilter::class);
            \assert($filter instanceof LanguageUrlFilter);

            return $filter->filterPermalink($url);
        };
        add_filter('page_link', $permalinkFilter, 10, 1);
        add_filter('post_link', $permalinkFilter, 10, 1);
        add_filter('post_type_link', $permalinkFilter, 10, 1);

        // Persistance de la langue dans un cookie : si la requête courante a résolu
        // la langue depuis l'URL (oli_lang query var), on la mémorise pour la prochaine
        // requête au cas où l'utilisateur tomberait sur une URL non préfixée.
        add_action('template_redirect', function (): void {
            $resolver = $this->container->get(LanguageResolver::class);
            \assert($resolver instanceof LanguageResolver);

            if (!\in_array($resolver->source(), ['path', 'path_default', 'query_var'], true)) {
                return;
            }

            if (headers_sent()) {
                return;
            }

            setcookie(
                LanguageResolver::COOKIE_NAME,
                $resolver->current()->code,
                [
                    'expires'  => time() + 30 * 86400,
                    'path'     => '/',
                    'samesite' => 'Lax',
                ],
            );
        });

        add_action('add_meta_boxes', function (): void {
            $metabox = $this->container->get(LanguageMetabox::class);
            \assert($metabox instanceof LanguageMetabox);
            $metabox->register();
        });

        add_action('save_post', function (int $postId): void {
            $metabox = $this->container->get(LanguageMetabox::class);
            \assert($metabox instanceof LanguageMetabox);
            /** @var array<string, mixed> $postData */
            $postData = $_POST;
            $metabox->save($postId, $postData);
        });
    }
}
