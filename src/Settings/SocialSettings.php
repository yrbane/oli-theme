<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * DTO immuable pour les paramètres des réseaux sociaux.
 *
 * Chaque propriété correspond à l'URL du profil sur le réseau concerné.
 * Une valeur nulle indique que le réseau n'est pas configuré.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final readonly class SocialSettings
{
    /**
     * @param string|null $facebook URL du profil Facebook.
     * @param string|null $instagram URL du profil Instagram.
     * @param string|null $youtube URL de la chaîne YouTube.
     * @param string|null $linkedin URL du profil LinkedIn.
     * @param string|null $twitter URL du profil Twitter/X.
     */
    public function __construct(
        public ?string $facebook,
        public ?string $instagram,
        public ?string $youtube,
        public ?string $linkedin,
        public ?string $twitter,
    ) {
    }
}
