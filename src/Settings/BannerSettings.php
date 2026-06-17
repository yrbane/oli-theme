<?php

declare(strict_types=1);

namespace OliTheme\Settings;

/**
 * DTO immuable pour les paramètres de la bannière et du logo du thème.
 *
 * Regroupe les identifiants d'images (bannière desktop/mobile)
 * ainsi que les textes alternatifs par langue.
 *
 * @package OliTheme\Settings
 *
 * @since 1.0.0
 */
final readonly class BannerSettings
{
    /**
     * @param int|null $bannerDesktopId Identifiant de la bannière desktop.
     * @param int|null $bannerMobileId Identifiant de la bannière mobile.
     * @param array<string, string> $altByLanguage Map code langue → alt text.
     */
    public function __construct(
        public ?int $bannerDesktopId,
        public ?int $bannerMobileId,
        public array $altByLanguage,
    ) {
    }
}
