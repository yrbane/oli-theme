<?php

declare(strict_types=1);

namespace OliTheme\Tests\Unit\MediaFolders;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OliTheme\MediaFolders\MediaFoldersGallerySettings;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de MediaFoldersGallerySettings — page admin sous Médias
 * permettant de choisir les dossiers de la médiathèque exposés sur la page
 * publique « Galerie photos » (option `oli_gallery_folders`).
 *
 * @package OliTheme\Tests\Unit\MediaFolders
 *
 * @since 1.6.0
 */
final class MediaFoldersGallerySettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('sanitize_key')->returnArg(1);
    }

    protected function tearDown(): void
    {
        $_POST = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Sans option enregistrée, la liste des dossiers configurés est vide.
     */
    public function testGetConfiguredFoldersReturnsEmptyArrayWhenOptionUnset(): void
    {
        Functions\when('get_option')->justReturn(false);

        $settings = new MediaFoldersGallerySettings();

        self::assertSame([], $settings->getConfiguredFolders());
    }

    /**
     * Avec une option contenant des slugs, on récupère la liste.
     */
    public function testGetConfiguredFoldersReturnsStoredSlugs(): void
    {
        Functions\when('get_option')->justReturn(['voyage-inde', 'stages-2026']);

        $settings = new MediaFoldersGallerySettings();

        self::assertSame(['voyage-inde', 'stages-2026'], $settings->getConfiguredFolders());
    }

    /**
     * Si l'option contient autre chose qu'un tableau (anciennes valeurs,
     * corruption), on retombe sur un tableau vide sans planter.
     */
    public function testGetConfiguredFoldersFallsBackToEmptyWhenOptionMalformed(): void
    {
        Functions\when('get_option')->justReturn('garbage-string');

        $settings = new MediaFoldersGallerySettings();

        self::assertSame([], $settings->getConfiguredFolders());
    }

    /**
     * Sans la capacité `manage_options`, handleSave doit refuser.
     */
    public function testHandleSaveRefusesWithoutCapability(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        Functions\when('wp_die')->alias(static function (string $msg = '', string $title = '', array $args = []): void {
            throw new \RuntimeException('wp_die:' . ((int) ($args['response'] ?? 0)));
        });

        $settings = new MediaFoldersGallerySettings();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('wp_die:403');

        $settings->handleSave();
    }

    /**
     * Avec capacité + nonce valide + payload sain, on doit appeler update_option
     * avec la liste sanitizée (slugs uniques, vides retirés).
     */
    public function testHandleSaveSanitizesAndPersistsSlugs(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_admin_referer')->justReturn(true);
        Functions\when('wp_safe_redirect')->justReturn(true);
        Functions\when('add_query_arg')->alias(static fn (array $args, string $url): string => $url);
        Functions\when('admin_url')->returnArg(1);
        $_POST['folders'] = ['voyage-inde', '', 'stages-2026', 'voyage-inde'];

        $captured = null;
        Functions\when('update_option')->alias(static function (string $key, $value) use (&$captured): bool {
            $captured = ['key' => $key, 'value' => $value];

            return true;
        });

        $settings = new MediaFoldersGallerySettings();
        $settings->handleSave();

        self::assertNotNull($captured);
        self::assertSame(MediaFoldersGallerySettings::OPTION, $captured['key']);
        self::assertSame(['voyage-inde', 'stages-2026'], $captured['value']);
    }

    /**
     * Si le POST n'envoie pas de tableau `folders` (toutes les cases
     * décochées), on persiste un tableau vide — pas un undefined-index.
     */
    public function testHandleSavePersistsEmptyArrayWhenNoFolderSelected(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_admin_referer')->justReturn(true);
        Functions\when('wp_safe_redirect')->justReturn(true);
        Functions\when('add_query_arg')->alias(static fn (array $args, string $url): string => $url);
        Functions\when('admin_url')->returnArg(1);
        unset($_POST['folders']);

        $captured = null;
        Functions\when('update_option')->alias(static function (string $key, $value) use (&$captured): bool {
            $captured = $value;

            return true;
        });

        $settings = new MediaFoldersGallerySettings();
        $settings->handleSave();

        self::assertSame([], $captured);
    }
}
