<?php

declare(strict_types=1);

namespace OliTheme\Gallery\Frontend;

use OliTheme\Gallery\GalleryRepository;

/**
 * Shortcode + bloc Gutenberg pour insérer une vidéo YouTube responsive
 * n'importe où dans le contenu d'un post, d'une page ou d'un event.
 *
 * Usage shortcode :
 *
 *   [oli_video id="M85A64fB4Yo"]
 *   [oli_video id="https://youtu.be/dYQUEd9Em38" caption="Démo Kalari"]
 *   [oli_video id="M85A64fB4Yo" autoplay="true" aspect="4/3"]
 *
 * Usage bloc Gutenberg : `oli/video` (server-render — l'éditeur voit un
 * placeholder, le front voit la vidéo embed).
 *
 * @package OliTheme\Gallery\Frontend
 *
 * @since 1.6.0
 */
final class VideoShortcode
{
    public const BLOCK_NAME = 'oli/video';
    public const SHORTCODE  = 'oli_video';

    private const NOCOOKIE_BASE = 'https://www.youtube-nocookie.com/embed/';

    public function __construct(private readonly GalleryRepository $repo)
    {
    }

    public function register(): void
    {
        if (\function_exists('add_shortcode')) {
            add_shortcode(self::SHORTCODE, [$this, 'renderShortcode']);
        }
        if (\function_exists('register_block_type')) {
            register_block_type(self::BLOCK_NAME, [
                'render_callback' => [$this, 'renderBlock'],
                'attributes'      => [
                    'videoId'  => ['type' => 'string',  'default' => ''],
                    'caption'  => ['type' => 'string',  'default' => ''],
                    'autoplay' => ['type' => 'boolean', 'default' => false],
                    'aspect'   => ['type' => 'string',  'default' => '16/9'],
                ],
            ]);
        }
    }

    /**
     * Handler du shortcode.
     *
     * @param array<string, mixed>|string $atts
     */
    public function renderShortcode(array|string $atts = []): string
    {
        $atts = \is_array($atts) ? $atts : [];

        return $this->render(
            (string) ($atts['id']       ?? ''),
            (string) ($atts['caption']  ?? ''),
            $this->parseBool($atts['autoplay'] ?? 'false'),
            (string) ($atts['aspect']   ?? '16/9'),
        );
    }

    /**
     * Handler du bloc Gutenberg server-render (attributs typés).
     *
     * @param array<string, mixed>|null $attrs
     */
    public function renderBlock(?array $attrs = null): string
    {
        $attrs ??= [];

        return $this->render(
            (string) ($attrs['videoId']  ?? ''),
            (string) ($attrs['caption']  ?? ''),
            (bool)   ($attrs['autoplay'] ?? false),
            (string) ($attrs['aspect']   ?? '16/9'),
        );
    }

    /**
     * Construit le markup du shortcode/bloc à partir des paramètres normalisés.
     */
    private function render(string $rawId, string $caption, bool $autoplay, string $aspect): string
    {
        if ($rawId === '') {
            return '<p class="oli-video oli-video--empty"><em>'
                . esc_html__('Aucune vidéo spécifiée pour ce bloc.', 'oli-theme')
                . '</em></p>';
        }

        // Réutilise la sanitisation existante (accepte ID brut OU URL YouTube).
        $videoId = $this->repo->sanitizeVideoId($rawId);
        if ($videoId === '') {
            return '<p class="oli-video oli-video--empty"><em>'
                . esc_html(sprintf(
                    /* translators: %s = identifiant fourni */
                    __('Identifiant vidéo invalide : « %s ».', 'oli-theme'),
                    $rawId,
                ))
                . '</em></p>';
        }

        $url = self::NOCOOKIE_BASE . $videoId . '?rel=0';
        if ($autoplay) {
            // Les navigateurs bloquent l'autoplay non muté ; on force mute=1
            // pour que la lecture démarre réellement.
            $url .= '&autoplay=1&mute=1';
        }

        // Sécurise l'aspect ratio à un set limité.
        $safeAspect = $this->sanitizeAspect($aspect);

        ob_start();
        ?>
        <figure class="oli-video" style="aspect-ratio:<?php echo esc_attr($safeAspect); ?>;">
            <iframe
                class="oli-video__iframe"
                src="<?php echo esc_url($url); ?>"
                title="<?php echo esc_attr($caption !== '' ? $caption : __('Vidéo YouTube', 'oli-theme')); ?>"
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
                allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
            ></iframe>
            <?php if ($caption !== '') : ?>
                <figcaption class="oli-video__caption"><?php echo esc_html($caption); ?></figcaption>
            <?php endif; ?>
        </figure>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Accepte 'true'/'false'/'1'/'0'/'yes'/'no' (insensible à la casse).
     */
    private function parseBool(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower((string) $value), ['true', '1', 'yes', 'on'], true);
    }

    /**
     * Garde uniquement les ratios reconnus pour éviter du CSS injecté.
     */
    private function sanitizeAspect(string $aspect): string
    {
        $aspect = trim($aspect);
        if (preg_match('~^\d{1,2}/\d{1,2}$~', $aspect) === 1) {
            return $aspect;
        }

        return '16/9';
    }
}
