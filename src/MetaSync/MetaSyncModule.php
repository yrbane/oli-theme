<?php

declare(strict_types=1);

namespace OliTheme\MetaSync;

use OliTheme\Admin\AdminTabRegistry;
use OliTheme\Container;
use OliTheme\Core\ModuleInterface;
use OliTheme\MetaSync\Admin\MetaSyncAdminPage;
use OliTheme\MetaSync\Auth\MetaOAuthController;
use OliTheme\MetaSync\Auth\MetaOAuthExchange;
use OliTheme\MetaSync\Auth\TokenRefresher;
use OliTheme\MetaSync\Http\GraphApiClient;

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

        // P1.5 : Graph API client + OAuth exchange + cron refresh + admin page.
        if (!$c->has(GraphApiClient::class)) {
            $c->factory(GraphApiClient::class, static fn (): GraphApiClient => new GraphApiClient());
        }
        if (!$c->has(MetaOAuthExchange::class)) {
            $c->factory(
                MetaOAuthExchange::class,
                static fn (Container $cc): MetaOAuthExchange => new MetaOAuthExchange($cc->get(GraphApiClient::class)),
            );
        }
        if (!$c->has(MetaOAuthController::class)) {
            $c->factory(
                MetaOAuthController::class,
                static fn (Container $cc): MetaOAuthController => new MetaOAuthController(
                    $cc->get(MetaOAuthExchange::class),
                    $cc->get(TokenStore::class),
                ),
            );
        }
        if (!$c->has(TokenRefresher::class)) {
            $c->factory(
                TokenRefresher::class,
                static fn (Container $cc): TokenRefresher => new TokenRefresher(
                    $cc->get(MetaOAuthExchange::class),
                    $cc->get(TokenStore::class),
                ),
            );
        }
        if (!$c->has(MetaSyncAdminPage::class)) {
            $c->factory(
                MetaSyncAdminPage::class,
                static fn (Container $cc): MetaSyncAdminPage => new MetaSyncAdminPage($cc->get(TokenStore::class)),
            );
        }

        // Branche les handlers admin-post (start/callback/disconnect/test).
        add_action('init', static function () use ($c): void {
            if (!\function_exists('add_action')) {
                return;
            }
            $oauth = $c->get(MetaOAuthController::class);
            \assert($oauth instanceof MetaOAuthController);
            $oauth->register();
        });

        // Sous-onglet admin "Synchro Meta".
        add_action('admin_menu', static function () use ($c): void {
            $registry = $c->get(AdminTabRegistry::class);
            \assert($registry instanceof AdminTabRegistry);
            $registry->add($c->get(MetaSyncAdminPage::class));
        });

        // Cron quotidien de refresh du token.
        add_action(TokenRefresher::CRON_HOOK, static function () use ($c): void {
            $refresher = $c->get(TokenRefresher::class);
            \assert($refresher instanceof TokenRefresher);
            $refresher->run(time());
        });
        add_action('init', static function (): void {
            if (\function_exists('wp_next_scheduled') && \function_exists('wp_schedule_event')) {
                if (!wp_next_scheduled(TokenRefresher::CRON_HOOK)) {
                    wp_schedule_event(time() + 60, 'daily', TokenRefresher::CRON_HOOK);
                }
            }
        });
    }
}
