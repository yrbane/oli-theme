<?php

declare(strict_types=1);

namespace OliTheme\Admin;

/**
 * Définition figée des 5 groupes principaux (onglets de premier niveau).
 *
 * @package OliTheme\Admin
 *
 * @since 1.1.0
 */
final class AdminGroups
{
    public const DEFAULT_GROUP = 'identite';

    /**
     * Groupes ordonnés : id => libellé.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'identite'  => __('Identité & Marque', 'oli-theme'),
            'apparence' => __('Apparence', 'oli-theme'),
            'contenu'   => __('Contenu', 'oli-theme'),
            'contact'   => __('Contact', 'oli-theme'),
            'seo'       => __('SEO', 'oli-theme'),
        ];
    }

    /** Vrai si l'id de groupe existe. */
    public static function exists(string $group): bool
    {
        return \array_key_exists($group, self::all());
    }
}
