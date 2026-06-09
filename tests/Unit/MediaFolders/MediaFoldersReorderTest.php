<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MediaFolders;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\MediaFolders\MediaFoldersReorder;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de MediaFoldersReorder — endpoint AJAX de réordonnancement
 * de la galerie d'un dossier (mise à jour `menu_order` des attachments).
 *
 * @package OliTheme\Tests\Unit\MediaFolders
 *
 * @since 1.6.0
 */
final class MediaFoldersReorderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        // Stubs WP communs à tous les tests.
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('sanitize_key')->returnArg(1);
        Functions\when('wp_send_json_error')->alias(static function ($data = null, int $status = 0): void {
            throw new \RuntimeException('json_error:' . $status);
        });
        Functions\when('wp_send_json_success')->alias(static function ($data = null): void {
            throw new \RuntimeException('json_success');
        });
    }

    protected function tearDown(): void
    {
        $_POST = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Sans la capacité `upload_files`, handleSave doit refuser (json_error 403).
     */
    public function testSaveRefuseSansCapability(): void
    {
        Functions\when('current_user_can')->justReturn(false);

        $reorder = new MediaFoldersReorder();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('json_error:403');

        $reorder->handleSave();
    }

    /**
     * Avec la capacité mais sans nonce valide, handleSave doit refuser (403).
     */
    public function testSaveRefuseSansNonceValide(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_ajax_referer')->justReturn(false);

        $reorder = new MediaFoldersReorder();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('json_error:403');

        $reorder->handleSave();
    }

    /**
     * Sans slug de dossier (ou slug vide), refus 422.
     */
    public function testSaveRefuseSansFolder(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_ajax_referer')->justReturn(1);
        $_POST['folder'] = '';
        $_POST['order']  = ['42'];

        $reorder = new MediaFoldersReorder();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('json_error:422');

        $reorder->handleSave();
    }

    /**
     * Sans liste d'IDs (ou liste vide), refus 422.
     */
    public function testSaveRefuseSansOrder(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_ajax_referer')->justReturn(1);
        $_POST['folder'] = 'oli-galerie-2024';
        $_POST['order']  = [];

        $reorder = new MediaFoldersReorder();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('json_error:422');

        $reorder->handleSave();
    }

    /**
     * Met à jour `menu_order` selon l'ordre fourni puis renvoie un JSON succès.
     *
     * L'ordre fourni est [42, 17, 99] → menu_order doit valoir respectivement
     * 0, 1, 2. Seuls les attachments du dossier ciblé doivent être touchés.
     */
    public function testSaveMetAJourMenuOrderDansLOrdreEtRetourneSucces(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_ajax_referer')->justReturn(1);
        $_POST['folder'] = 'oli-galerie-2024';
        $_POST['order']  = ['42', '17', '99'];

        // Les attachments listés sont bien tagués sur le dossier ciblé.
        Functions\when('has_term')->alias(static fn (string $slug, string $tax, int $id): bool => true);

        $calls = [];
        Functions\when('wp_update_post')->alias(static function (array $data) use (&$calls): int {
            $calls[] = $data;

            return (int) ($data['ID'] ?? 0);
        });

        $reorder = new MediaFoldersReorder();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('json_success');

        try {
            $reorder->handleSave();
        } finally {
            self::assertCount(3, $calls);
            self::assertSame(['ID' => 42, 'menu_order' => 0], $calls[0]);
            self::assertSame(['ID' => 17, 'menu_order' => 1], $calls[1]);
            self::assertSame(['ID' => 99, 'menu_order' => 2], $calls[2]);
        }
    }

    /**
     * Un ID qui n'appartient pas au dossier ciblé ne doit pas être réordonné
     * (protection contre la falsification du payload).
     */
    public function testSaveIgnoreLesIdsExterieursAuDossier(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_ajax_referer')->justReturn(1);
        $_POST['folder'] = 'oli-galerie-2024';
        $_POST['order']  = ['42', '999']; // 999 n'est pas dans le dossier.

        Functions\when('has_term')->alias(
            static fn (string $slug, string $tax, int $id): bool => $id === 42,
        );

        $calls = [];
        Functions\when('wp_update_post')->alias(static function (array $data) use (&$calls): int {
            $calls[] = $data;

            return (int) ($data['ID'] ?? 0);
        });

        $reorder = new MediaFoldersReorder();

        try {
            $reorder->handleSave();
        } catch (\RuntimeException) {
            // wp_send_json_success/error termine la requête en prod.
        }

        self::assertCount(1, $calls);
        self::assertSame(42, $calls[0]['ID']);
    }
}
