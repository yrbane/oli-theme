<?php

declare(strict_types=1);

namespace OliTheme\Seo\Schema;

use OliTheme\Posts\PostEntity;

/**
 * Schéma JSON-LD pour le type Article.
 *
 * Construit à partir d'un PostEntity et de l'identifiant de l'organisation éditrice.
 *
 * @package OliTheme\Seo\Schema
 *
 * @since 1.0.0
 */
final readonly class ArticleSchema implements SchemaInterface
{
    /**
     * @param PostEntity $post Article WordPress source.
     * @param string $organizationId Valeur de l'@id de l'organisation éditrice.
     */
    public function __construct(
        private PostEntity $post,
        private string $organizationId,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $schema = [
            '@type' => 'Article',
            '@id' => $this->post->permalink . '#article',
            'headline' => $this->post->title,
            'datePublished' => $this->post->publishedAt->format('c'),
            'dateModified' => $this->post->updatedAt?->format('c') ?? $this->post->publishedAt->format('c'),
            'author' => $this->post->author !== null
                ? ['@type' => 'Person', 'name' => $this->post->author]
                : null,
            'publisher' => ['@id' => $this->organizationId],
            'inLanguage' => $this->post->language->code,
            'image' => $this->post->featuredImageUrl !== null
                ? ['@type' => 'ImageObject', 'url' => $this->post->featuredImageUrl]
                : null,
            'mainEntityOfPage' => ['@id' => $this->post->permalink],
        ];

        return array_filter($schema, static fn ($v) => $v !== null);
    }
}
