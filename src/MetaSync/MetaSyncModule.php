<?php

declare(strict_types=1);

namespace OliTheme\MetaSync;

use OliTheme\Container;
use OliTheme\Core\ModuleInterface;

/**
 * Module Meta Sync — Phase 1 (P1) : fondations DI uniquement.
 *
 * Phases à venir :
 *  - P1.5 : MetaOAuthController (flux OAuth Meta), TokenRefresher cron.
 *  - P2  : FacebookPublisher (create/edit/delete via Graph API).
 *  - P3  : InstagramPublisher (workflow 2-étapes + delete_recreate).
 *  - P4  : MetaSyncDispatcher (hooks publish/update/delete) + ContentHash.
 *  - P5  : MetaSyncReconciler cron quotidien + admin (tableau, logs, actions).
 *  - P6  : Events Facebook + fallback post standard.
 *
 * Voir l'issue #15 pour la spec complète.
 *
 * @package OliTheme\MetaSync
 *
 * @since 1.3.0
 */
final class MetaSyncModule implements ModuleInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        $c = $this->container;

        if (!$c->has(TokenStore::class)) {
            $c->factory(TokenStore::class, static function (): TokenStore {
                // En prod, la constante AUTH_KEY est définie dans wp-config.php.
                // En tests / WP-CLI hors web, on retombe sur une clé déterministe
                // (sans valeur de sécurité, juste pour ne pas crasher).
                $secret = \defined('AUTH_KEY') && \AUTH_KEY !== ''
                    ? (string) \AUTH_KEY
                    : 'oli-meta-sync-fallback-' . php_uname('n');

                return new TokenStore($secret);
            });
        }
    }
}
