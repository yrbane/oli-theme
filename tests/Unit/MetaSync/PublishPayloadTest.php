<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MetaSync;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\MetaSync\Publisher\PublishPayload;
use PHPUnit\Framework\TestCase;

final class PublishPayloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg(1);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_content_hash_is_stable(): void
    {
        $p1 = new PublishPayload(1, 'T', 'E', 'C', 'https://x/1');
        $p2 = new PublishPayload(1, 'T', 'E', 'C', 'https://x/1');
        self::assertSame($p1->contentHash(), $p2->contentHash());
    }

    public function test_content_hash_differs_on_content_change(): void
    {
        $p1 = new PublishPayload(1, 'T', 'E', 'C1', 'u');
        $p2 = new PublishPayload(1, 'T', 'E', 'C2', 'u');
        self::assertNotSame($p1->contentHash(), $p2->contentHash());
    }

    public function test_facebook_message_includes_link_and_hashtags(): void
    {
        $p = new PublishPayload(1, 'Titre', 'Extrait', 'Corps', 'https://oli/post', '', ['kalari', 'yoga']);
        $msg = $p->facebookMessage();
        self::assertStringContainsString('Titre', $msg);
        self::assertStringContainsString('Extrait', $msg);
        self::assertStringContainsString('https://oli/post', $msg);
        self::assertStringContainsString('#kalari', $msg);
        self::assertStringContainsString('#yoga', $msg);
    }

    public function test_instagram_caption_within_limit(): void
    {
        $long = str_repeat('a', 3000);
        $p = new PublishPayload(1, 'T', '', $long, 'https://x');
        $caption = $p->instagramCaption();
        self::assertLessThanOrEqual(2200, mb_strlen($caption));
        // La troncature du corps insère un ellipsis ; le caption final se termine
        // par « Lien en bio » mais le marqueur d'élision est présent à l'intérieur.
        self::assertStringContainsString('…', $caption);
    }
}
