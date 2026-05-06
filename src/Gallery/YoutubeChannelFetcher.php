<?php

declare(strict_types=1);

namespace OliTheme\Gallery;

/**
 * Récupère les vidéos d'une chaîne YouTube via son flux RSS public.
 *
 * Aucun cle API requise. Limitations YouTube :
 *   - Le flux RSS expose les **15 dernières vidéos** de la chaîne.
 *   - L'URL d'un @handle (ex. https://www.youtube.com/@OliKalari) ne suffit
 *     pas pour le flux RSS : il faut un `channel_id` (UCxxxxx). On extrait
 *     ce dernier depuis le HTML public de la chaîne (regex sur `externalId`)
 *     puis on cache 7 jours via transient.
 *   - Le flux XML lui-même est cache 1h pour éviter de marteler YouTube.
 *
 * @package OliTheme\Gallery
 *
 * @since 1.0.0
 */
final class YoutubeChannelFetcher
{
    private const TRANSIENT_CHANNEL_ID = 'oli_yt_channel_id_';
    private const TRANSIENT_VIDEOS     = 'oli_yt_videos_';
    private const TTL_CHANNEL_ID       = 604800;   // 7 jours
    private const TTL_VIDEOS           = 3600;     // 1 heure

    /**
     * Récupère les dernières vidéos publiées de la chaîne.
     *
     * @return list<array{video_id: string, caption: string, embed_url: string, thumb: string, watch_url: string}>
     */
    public function fetchVideos(string $channelUrl): array
    {
        $channelUrl = trim($channelUrl);
        if ($channelUrl === '') {
            return [];
        }

        $cacheKey = self::TRANSIENT_VIDEOS . md5($channelUrl);
        if (\function_exists('get_transient')) {
            $cached = get_transient($cacheKey);
            if (\is_array($cached)) {
                /** @var list<array{video_id: string, caption: string, embed_url: string, thumb: string, watch_url: string}> $cached */
                return $cached;
            }
        }

        $channelId = $this->resolveChannelId($channelUrl);
        if ($channelId === null) {
            return [];
        }

        $rssUrl = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $channelId;
        $xml    = $this->httpGet($rssUrl);
        if ($xml === '') {
            return [];
        }

        $videos = $this->parseRss($xml);

        if (\function_exists('set_transient')) {
            set_transient($cacheKey, $videos, self::TTL_VIDEOS);
        }

        return $videos;
    }

    /**
     * Extrait le `channel_id` (UCxxx) à partir de l'URL d'une chaîne. Cache 7 j.
     * Accepte les formats /@handle, /channel/UC..., /c/Custom, /user/Name.
     */
    public function resolveChannelId(string $channelUrl): ?string
    {
        // /channel/UCxxxxxxxx — déjà un ID, pas besoin d'aller plus loin.
        if (preg_match('~/channel/(UC[A-Za-z0-9_-]{22})~', $channelUrl, $m) === 1) {
            return $m[1];
        }

        $cacheKey = self::TRANSIENT_CHANNEL_ID . md5($channelUrl);
        if (\function_exists('get_transient')) {
            $cached = get_transient($cacheKey);
            if (\is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $html = $this->httpGet($channelUrl);
        if ($html === '') {
            return null;
        }

        $id = null;
        if (preg_match('~"externalId":"(UC[A-Za-z0-9_-]{22})"~', $html, $m) === 1) {
            $id = $m[1];
        } elseif (preg_match('~<meta\s+itemprop="(?:identifier|channelId)"\s+content="(UC[A-Za-z0-9_-]{22})"~', $html, $m) === 1) {
            $id = $m[1];
        } elseif (preg_match('~/channel/(UC[A-Za-z0-9_-]{22})~', $html, $m) === 1) {
            $id = $m[1];
        }

        if ($id !== null && \function_exists('set_transient')) {
            set_transient($cacheKey, $id, self::TTL_CHANNEL_ID);
        }

        return $id;
    }

    /**
     * Parse le XML du flux RSS YouTube et retourne la liste de vidéos enrichies.
     *
     * @return list<array{video_id: string, caption: string, embed_url: string, thumb: string, watch_url: string}>
     */
    private function parseRss(string $xml): array
    {
        $previousState = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        libxml_use_internal_errors($previousState);

        if ($doc === false) {
            return [];
        }

        // Le flux YT utilise les namespaces atom et yt. On enregistre juste yt
        // pour pouvoir accéder à yt:videoId.
        $doc->registerXPathNamespace('yt', 'http://www.youtube.com/xml/schemas/2015');

        $videos = [];
        foreach ($doc->entry ?? [] as $entry) {
            $title   = trim((string) ($entry->title ?? ''));
            $idNodes = $entry->xpath('yt:videoId') ?: [];
            $videoId = $idNodes !== [] ? trim((string) $idNodes[0]) : '';
            if ($videoId === '' || !preg_match('~^[A-Za-z0-9_-]{11}$~', $videoId)) {
                continue;
            }
            $videos[] = [
                'video_id'  => $videoId,
                'caption'   => $title,
                'embed_url' => 'https://www.youtube-nocookie.com/embed/' . $videoId . '?rel=0',
                'thumb'     => 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg',
                'watch_url' => 'https://www.youtube.com/watch?v=' . $videoId,
            ];
        }

        return $videos;
    }

    /**
     * Wrapper HTTP GET : utilise wp_remote_get si disponible, file_get_contents sinon.
     *
     * User-agent navigateur + cookies CONSENT/SOCS pour bypass la page RGPD
     * que YouTube renvoie aux requêtes server-side. Sans ces cookies, la
     * réponse est une page de "Before you continue to YouTube" qui ne
     * contient pas le marker `externalId`.
     */
    private function httpGet(string $url): string
    {
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';
        $consentCookies = 'CONSENT=YES+cb; SOCS=CAISEwgDEgk2NDU3MDc2NjUaAmVuIAEaBgiA0_aXBg';

        if (\function_exists('wp_remote_get')) {
            $response = wp_remote_get($url, [
                'timeout'     => 8,
                'redirection' => 5,
                'user-agent'  => $userAgent,
                'headers'     => ['Cookie' => $consentCookies],
            ]);
            if (\function_exists('is_wp_error') && is_wp_error($response)) {
                return '';
            }
            if (!\is_array($response)) {
                return '';
            }
            $body = (string) $response['body'];
            $code = (int) $response['response']['code'];

            return $code >= 200 && $code < 300 ? $body : '';
        }

        $context = stream_context_create([
            'http' => [
                'timeout'        => 8,
                'follow_location' => 1,
                'max_redirects'  => 5,
                'header'         => "User-Agent: {$userAgent}\r\nCookie: {$consentCookies}\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);

        return \is_string($body) ? $body : '';
    }
}
