<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gallery;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Gallery\YoutubeChannelFetcher;
use PHPUnit\Framework\TestCase;

/**
 * @package OliTheme\Tests\Unit\Gallery
 *
 * @since 1.0.0
 */
final class YoutubeChannelFetcherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('get_transient')->justReturn(false);
        Functions\when('set_transient')->justReturn(true);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testResolveChannelIdReturnsIdDirectlyFromChannelUrl(): void
    {
        $f = new YoutubeChannelFetcher();
        self::assertSame('UCabcdefghijklmnopqrstuv', $f->resolveChannelId(
            'https://www.youtube.com/channel/UCabcdefghijklmnopqrstuv',
        ));
    }

    public function testResolveChannelIdExtractsFromHandlePageHtml(): void
    {
        $html = '<!doctype html><html>...<script>var ytInitialData = {"externalId":"UC1234567890abcdefghijkl"};</script>...</html>';
        Functions\when('wp_remote_get')->justReturn(['body' => $html, 'response' => ['code' => 200]]);
        Functions\when('is_wp_error')->justReturn(false);

        $id = (new YoutubeChannelFetcher())->resolveChannelId('https://www.youtube.com/@SomeHandle');

        self::assertSame('UC1234567890abcdefghijkl', $id);
    }

    public function testResolveChannelIdExtractsFromMetaTag(): void
    {
        $html = '<html><head><meta itemprop="identifier" content="UCabcdefghijklmnopqrstuv"></head></html>';
        Functions\when('wp_remote_get')->justReturn(['body' => $html, 'response' => ['code' => 200]]);
        Functions\when('is_wp_error')->justReturn(false);

        $id = (new YoutubeChannelFetcher())->resolveChannelId('https://www.youtube.com/@Whatever');

        self::assertSame('UCabcdefghijklmnopqrstuv', $id);
    }

    public function testResolveChannelIdReturnsNullOnEmptyResponse(): void
    {
        Functions\when('wp_remote_get')->justReturn(['body' => '<html><head></head></html>', 'response' => ['code' => 200]]);
        Functions\when('is_wp_error')->justReturn(false);

        self::assertNull((new YoutubeChannelFetcher())->resolveChannelId('https://www.youtube.com/@Empty'));
    }

    public function testFetchVideosParsesRssAndReturnsEnrichedList(): void
    {
        $rss = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:yt="http://www.youtube.com/xml/schemas/2015">
  <entry>
    <yt:videoId>dQw4w9WgXcQ</yt:videoId>
    <title>Demo video #1</title>
  </entry>
  <entry>
    <yt:videoId>abcdefghijk</yt:videoId>
    <title>Demo video #2</title>
  </entry>
</feed>
XML;
        // resolveChannelId extrait depuis HTML, fetchVideos appelle httpGet sur le RSS.
        $callCount = 0;
        Functions\when('wp_remote_get')->alias(static function (string $url) use (&$callCount, $rss) {
            ++$callCount;
            // 1er appel : page chaîne pour extraire channel_id
            if (str_contains($url, 'youtube.com/@')) {
                return ['body' => '<html>"externalId":"UCabcdefghijklmnopqrstuv"</html>', 'response' => ['code' => 200]];
            }
            // 2e appel : flux RSS
            return ['body' => $rss, 'response' => ['code' => 200]];
        });
        Functions\when('is_wp_error')->justReturn(false);

        $videos = (new YoutubeChannelFetcher())->fetchVideos('https://www.youtube.com/@Demo');

        self::assertCount(2, $videos);
        self::assertSame('dQw4w9WgXcQ', $videos[0]['video_id']);
        self::assertSame('Demo video #1', $videos[0]['caption']);
        self::assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0', $videos[0]['embed_url']);
        self::assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $videos[0]['thumb']);
    }

    public function testFetchVideosReturnsEmptyForBlankUrl(): void
    {
        self::assertSame([], (new YoutubeChannelFetcher())->fetchVideos(''));
    }

    public function testFetchVideosUsesCacheWhenAvailable(): void
    {
        $cached = [['video_id' => 'cachedABC12', 'caption' => 'Cached', 'embed_url' => '', 'thumb' => '', 'watch_url' => '']];
        Functions\when('get_transient')->justReturn($cached);

        // Aucun appel HTTP attendu
        Functions\when('wp_remote_get')->alias(static function () {
            throw new \RuntimeException('should not be called when cache hit');
        });

        $videos = (new YoutubeChannelFetcher())->fetchVideos('https://www.youtube.com/@x');

        self::assertSame($cached, $videos);
    }
}
