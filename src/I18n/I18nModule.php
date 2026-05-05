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
