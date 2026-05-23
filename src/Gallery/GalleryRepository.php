<?php

declare(strict_types=1);

namespace OliTheme\Gallery;

/**
 * Lecture/écriture des données de la galerie (photos + vidéos YouTube).
 *
 * Les données sont stockées dans 3 options WordPress autonomes :
 *   - `oli_gallery_photos` : JSON `[{attachment_id, caption}, ...]`
 *   - `oli_gallery_youtube_channel` : URL de la chaîne YouTube (string)
 *   - `oli_gallery_videos` : JSON `[{video_id, caption}, ...]`
 *
 * @package OliTheme\Gallery
 *
 * @since 1.0.0
 */
final class GalleryRepository
{
    public const OPTION_PHOTOS  = 'oli_gallery_photos';
    public const OPTION_CHANNEL = 'oli_gallery_youtube_channel';
    public const OPTION_VIDEOS  = 'oli_gallery_videos';

    public const DEFAULT_CHANNEL = 'https://www.youtube.com/@OliKalari';

    /**
     * Vidéos par défaut affichées tant que l'admin n'a rien saisi ET que le
     * fetch RSS YouTube ne renvoie rien (réseau bloqué, chaîne supprimée…).
     * Garantit que la page Vidéos n'est jamais vide.
     *
     * @var list<array{video_id: string, caption: string}>
     */
    private const DEFAULT_VIDEOS = [
        ['video_id' => 'M85A64fB4Yo', 'caption' => 'Kalaripayattu Goa 2019 - Kalari Body Forms - Meypayattu'],
        ['video_id' => 'dYQUEd9Em38', 'caption' => 'Kalaripayattu Goa 2019 - Kalari Body Conditioning'],
        ['video_id' => 'QAkfWz32YiY', 'caption' => 'Kalari Urumi - Kalaripayattu Flexible Sword'],
    ];

    public function __construct(private readonly ?YoutubeChannelFetcher $fetcher = null)
    {
    }

    /**
     * Retourne les photos enrichies (URL + alt + srcset résolus depuis WP).
     *
     * @return list<array{id: int, caption: string, url: string, thumb: string, alt: string, srcset: string}>
     */
    public function getPhotos(): array
    {
        $raw = $this->readJson(self::OPTION_PHOTOS);
        $out = [];
        foreach ($raw as $entry) {
            if (!\is_array($entry) || empty($entry['attachment_id'])) {
                continue;
            }
            $id  = (int) $entry['attachment_id'];
            $url = \function_exists('wp_get_attachment_image_url') ? wp_get_attachment_image_url($id, 'large') : '';
            $thb = \function_exists('wp_get_attachment_image_url') ? wp_get_attachment_image_url($id, 'medium') : '';
            if (!\is_string($url) || $url === '') {
                continue;
            }
            $alt = '';
            if (\function_exists('get_post_meta')) {
                $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
            }
            // Jeu de sources responsives (toutes tailles WP), commun à l'image
            // principale et aux vignettes — seul `sizes` diffère côté template.
            $srcset = \function_exists('wp_get_attachment_image_srcset')
                ? wp_get_attachment_image_srcset($id, 'large')
                : false;
            $out[] = [
                'id'      => $id,
                'caption' => (string) ($entry['caption'] ?? ''),
                'url'     => $url,
                'thumb'   => \is_string($thb) && $thb !== '' ? $thb : $url,
                'alt'     => $alt,
                'srcset'  => \is_string($srcset) ? $srcset : '',
            ];
        }
        return $out;
    }

    /**
     * Stocke la liste de photos.
     *
     * @param list<array<string, mixed>> $photos
     */
    public function setPhotos(array $photos): void
    {
        $clean = [];
        foreach ($photos as $entry) {
            if (!\is_array($entry) || empty($entry['attachment_id'])) {
                continue;
            }
            $id = (int) $entry['attachment_id'];
            if ($id <= 0) {
                continue;
            }
            $caption = isset($entry['caption']) && \is_string($entry['caption'])
                ? $this->sanitizeCaption($entry['caption'])
                : '';
            $clean[] = [
                'attachment_id' => $id,
                'caption'       => $caption,
            ];
        }
        update_option(self::OPTION_PHOTOS, wp_json_encode($clean));
    }

    public function getYoutubeChannel(): string
    {
        $val = (string) get_option(self::OPTION_CHANNEL, self::DEFAULT_CHANNEL);
        return $val !== '' ? $val : self::DEFAULT_CHANNEL;
    }

    public function setYoutubeChannel(string $url): void
    {
        $url = trim($url);
        if ($url === '') {
            $url = self::DEFAULT_CHANNEL;
        }
        update_option(self::OPTION_CHANNEL, esc_url_raw($url));
    }

    /**
     * Retourne les vidéos enrichies (embed_url + thumbnail YT résolus).
     *
     * Mode mixte :
     *   - Si l'admin a saisi des vidéos manuelles (option non vide) → on les
     *     utilise (override, captions custom).
     *   - Sinon → fetch auto via {@see YoutubeChannelFetcher} (15 dernières
     *     vidéos publiées de la chaîne, depuis le RSS public).
     *
     * @return list<array{video_id: string, caption: string, embed_url: string, thumb: string, watch_url: string}>
     */
    public function getVideos(): array
    {
        $manual = $this->getManualVideos();
        if ($manual !== []) {
            return $manual;
        }

        if ($this->fetcher !== null) {
            $fetched = $this->fetcher->fetchVideos($this->getYoutubeChannel());
            if ($fetched !== []) {
                return $fetched;
            }
        }

        // Fallback final : quelques vidéos par défaut pour ne jamais avoir
        // une page vide (utile en dev local quand le réseau bloque YouTube,
        // ou quand la chaîne renvoie 404 sur son flux RSS).
        return $this->hydrateVideos(self::DEFAULT_VIDEOS);
    }

    /**
     * Lit la liste de vidéos saisies manuellement (option `oli_gallery_videos`).
     *
     * @return list<array{video_id: string, caption: string, embed_url: string, thumb: string, watch_url: string}>
     */
    public function getManualVideos(): array
    {
        $raw = $this->readJson(self::OPTION_VIDEOS);
        $out = [];
        foreach ($raw as $entry) {
            if (!\is_array($entry) || empty($entry['video_id'])) {
                continue;
            }
            $vid = $this->sanitizeVideoId((string) $entry['video_id']);
            if ($vid === '') {
                continue;
            }
            $out[] = [
                'video_id'  => $vid,
                'caption'   => (string) ($entry['caption'] ?? ''),
                'embed_url' => 'https://www.youtube-nocookie.com/embed/' . $vid . '?rel=0',
                'thumb'     => 'https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg',
                'watch_url' => 'https://www.youtube.com/watch?v=' . $vid,
            ];
        }
        return $out;
    }

    /**
     * Stocke la liste de vidéos.
     *
     * @param list<array<string, mixed>> $videos
     */
    public function setVideos(array $videos): void
    {
        $clean = [];
        foreach ($videos as $entry) {
            if (!\is_array($entry) || empty($entry['video_id'])) {
                continue;
            }
            $vid = $this->sanitizeVideoId((string) $entry['video_id']);
            if ($vid === '') {
                continue;
            }
            $caption = isset($entry['caption']) && \is_string($entry['caption'])
                ? $this->sanitizeCaption($entry['caption'])
                : '';
            $clean[] = [
                'video_id' => $vid,
                'caption'  => $caption,
            ];
        }
        update_option(self::OPTION_VIDEOS, wp_json_encode($clean));
    }

    /**
     * Extrait l'ID d'une URL YouTube ou retourne tel quel si déjà un ID.
     * Accepte : youtube.com/watch?v=XXX, youtu.be/XXX, youtube.com/embed/XXX,
     * ou simplement l'ID brut (11 caractères alphanumériques + - + _).
     */
    public function sanitizeVideoId(string $input): string
    {
        $input = trim($input);
        if ($input === '') {
            return '';
        }

        // ID brut : 11 caractères [A-Za-z0-9_-].
        if (preg_match('~^[A-Za-z0-9_-]{11}$~', $input) === 1) {
            return $input;
        }

        // URL : extrait le paramètre `v` ou le segment de path après /embed/ ou /shorts/.
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $input, $m) === 1) {
            return $m[1];
        }

        return '';
    }

    /**
     * Enrichit une liste minimale `[{video_id, caption}, ...]` en list complète
     * avec embed_url / thumb / watch_url.
     *
     * @param list<array{video_id: string, caption: string}> $videos
     *
     * @return list<array{video_id: string, caption: string, embed_url: string, thumb: string, watch_url: string}>
     */
    private function hydrateVideos(array $videos): array
    {
        $out = [];
        foreach ($videos as $v) {
            $vid = $this->sanitizeVideoId($v['video_id']);
            if ($vid === '') {
                continue;
            }
            $out[] = [
                'video_id'  => $vid,
                'caption'   => $v['caption'],
                'embed_url' => 'https://www.youtube-nocookie.com/embed/' . $vid . '?rel=0',
                'thumb'     => 'https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg',
                'watch_url' => 'https://www.youtube.com/watch?v=' . $vid,
            ];
        }
        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readJson(string $option): array
    {
        $raw = get_option($option, '');
        if (!\is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!\is_array($decoded)) {
            return [];
        }
        return array_values($decoded);
    }

    private function sanitizeCaption(string $text): string
    {
        return \function_exists('sanitize_text_field') ? (string) sanitize_text_field($text) : strip_tags($text);
    }
}
