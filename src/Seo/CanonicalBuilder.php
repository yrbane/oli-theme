<?php

declare(strict_types=1);

namespace OliTheme\Seo;

/**
 * Construit l'URL canonique d'un contenu WordPress.
 *
 * Utilise l'override SEO en priorité, puis le permalink WordPress,
 * et replie sur l'URL d'accueil en cas d'échec.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class CanonicalBuilder
{
    /**
     * Retourne l'URL canonique pour le post donné.
     *
     * @param int $postId Identifiant WordPress du post.
     * @param string|null $override URL canonique personnalisée (prioritaire).
     */
    public function build(int $postId, ?string $override = null): string
    {
        if ($override !== null && $override !== '') {
            return $override;
        }

        $permalink = get_permalink($postId);

        return \is_string($permalink) && $permalink !== '' ? $permalink : (string) home_url('/');
    }
}
