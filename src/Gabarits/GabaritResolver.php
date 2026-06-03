<?php

declare(strict_types=1);

namespace OliTheme\Gabarits;

/**
 * Résout le gabarit applicable à un post WP (lit la postmeta `_oli_gabarit`).
 *
 * @package OliTheme\Gabarits
 *
 * @since 1.4.0
 */
final class GabaritResolver
{
    public const POSTMETA = '_oli_gabarit';

    public function __construct(private readonly GabaritRegistryInterface $registry)
    {
    }

    /**
     * Récupère le gabarit du post ou null si aucun n'est explicitement choisi.
     */
    public function forPost(int $postId): ?Gabarit
    {
        if ($postId <= 0 || !\function_exists('get_post_meta')) {
            return null;
        }
        $id = (string) get_post_meta($postId, self::POSTMETA, true);
        if ($id === '') {
            return null;
        }
        return $this->registry->byId($id);
    }
}
