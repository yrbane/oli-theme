<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\Gallery;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\Gallery\GalleryRepository;
use PHPUnit\Framework\TestCase;

/**
 * @package OliTheme\Tests\Unit\Gallery
 *
 * @since 1.0.0
 */
final class GalleryRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('esc_url_raw')->returnArg(1);
        Functions\when('wp_json_encode')->alias(static fn ($v) => json_encode($v));
        Functions\when('wp_get_attachment_image_url')->alias(
            static fn (int $id, string $size) => "https://example.test/wp-content/uploads/img-{$id}-{$size}.jpg",
        );
        Functions\when('get_post_meta')->alias(
            static fn (int $id, string $key, bool $single) => "alt-{$id}",
        );
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testSanitizeVideoIdAcceptsRawId(): void
    {
        self::assertSame('dQw4w9WgXcQ', (new GalleryRepository())->sanitizeVideoId('dQw4w9WgXcQ'));
    }

    public function testSanitizeVideoIdExtractsFromWatchUrl(): void
    {
        $r = new GalleryRepository();
        self::assertSame('dQw4w9WgXcQ', $r->sanitizeVideoId('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        self::assertSame('dQw4w9WgXcQ', $r->sanitizeVideoId('https://www.youtube.com/watch?feature=share&v=dQw4w9WgXcQ'));
    }

    public function testSanitizeVideoIdExtractsFromShortUrl(): void
    {
        self::assertSame('dQw4w9WgXcQ', (new GalleryRepository())->sanitizeVideoId('https://youtu.be/dQw4w9WgXcQ'));
    }

    public function testSanitizeVideoIdExtractsFromEmbedUrl(): void
    {
        self::assertSame('abc12345_-X', (new GalleryRepository())->sanitizeVideoId('https://www.youtube.com/embed/abc12345_-X'));
    }

    public function testSanitizeVideoIdExtractsFromShortsUrl(): void
    {
        self::assertSame('abcDEFghi12', (new GalleryRepository())->sanitizeVideoId('https://www.youtube.com/shorts/abcDEFghi12'));
    }

    public function testSanitizeVideoIdReturnsEmptyForGarbage(): void
    {
        self::assertSame('', (new GalleryRepository())->sanitizeVideoId('not a video'));
        self::assertSame('', (new GalleryRepository())->sanitizeVideoId(''));
    }

    public function testGetVideosBuildsEmbedAndThumbUrls(): void
    {
        Functions\when('get_option')->alias(static function (string $k) {
            if ($k === GalleryRepository::OPTION_VIDEOS) {
                return json_encode([
                    ['video_id' => 'dQw4w9WgXcQ', 'caption' => 'Demo'],
                ]);
            }
            return '';
        });

        $videos = (new GalleryRepository())->getVideos();

        self::assertCount(1, $videos);
        self::assertSame('dQw4w9WgXcQ', $videos[0]['video_id']);
        self::assertSame('Demo', $videos[0]['caption']);
        self::assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0', $videos[0]['embed_url']);
        self::assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $videos[0]['thumb']);
        self::assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $videos[0]['watch_url']);
    }

    public function testGetPhotosResolvesAttachmentUrlsAndAlt(): void
    {
        Functions\when('get_option')->alias(static function (string $k) {
            if ($k === GalleryRepository::OPTION_PHOTOS) {
                return json_encode([
                    ['attachment_id' => 42, 'caption' => 'A photo'],
                    ['attachment_id' => 43, 'caption' => ''],
                ]);
            }
            return '';
        });

        $photos = (new GalleryRepository())->getPhotos();

        self::assertCount(2, $photos);
        self::assertSame(42, $photos[0]['id']);
        self::assertSame('A photo', $photos[0]['caption']);
        self::assertSame('https://example.test/wp-content/uploads/img-42-large.jpg', $photos[0]['url']);
        self::assertSame('https://example.test/wp-content/uploads/img-42-medium.jpg', $photos[0]['thumb']);
        self::assertSame('alt-42', $photos[0]['alt']);
    }

    public function testGetYoutubeChannelReturnsDefaultWhenEmpty(): void
    {
        Functions\when('get_option')->justReturn('');
        self::assertSame(GalleryRepository::DEFAULT_CHANNEL, (new GalleryRepository())->getYoutubeChannel());
    }

    public function testGetYoutubeChannelReturnsStoredValue(): void
    {
        Functions\when('get_option')->justReturn('https://youtube.com/@AnotherChannel');
        self::assertSame('https://youtube.com/@AnotherChannel', (new GalleryRepository())->getYoutubeChannel());
    }

    public function testSetPhotosFiltersInvalidEntries(): void
    {
        /** @var list<string> $writes */
        $writes = [];
        Functions\when('update_option')->alias(static function (string $k, $v) use (&$writes): bool {
            $writes[$k] = $v;
            return true;
        });

        (new GalleryRepository())->setPhotos([
            ['attachment_id' => 1, 'caption' => 'OK'],
            ['attachment_id' => 0, 'caption' => 'invalid'],
            ['caption' => 'no id'],
            ['attachment_id' => 5, 'caption' => 'OK 2'],
        ]);

        self::assertArrayHasKey(GalleryRepository::OPTION_PHOTOS, $writes);
        $decoded = json_decode((string) $writes[GalleryRepository::OPTION_PHOTOS], true);
        self::assertCount(2, $decoded);
        self::assertSame(1, $decoded[0]['attachment_id']);
        self::assertSame(5, $decoded[1]['attachment_id']);
    }
}
