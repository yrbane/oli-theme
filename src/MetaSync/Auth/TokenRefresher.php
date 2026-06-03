<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Auth;

use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\TokenStore;

/**
 * Renouvelle quotidiennement le long-lived token si l'expiration approche.
 *
 * À brancher sur le cron `oli_meta_sync_refresh_token` (intervalle `daily`).
 *
 * @package OliTheme\MetaSync\Auth
 *
 * @since 1.3.0
 */
final class TokenRefresher
{
    public const CRON_HOOK = 'oli_meta_sync_refresh_token';

    public function __construct(
        private readonly MetaOAuthExchange $exchange,
        private readonly TokenStore $tokens,
    ) {
    }

    public function run(int $now): bool
    {
        $current = $this->tokens->load();
        if (!$current->isConnected()) {
            return false;
        }
        if (!$current->isExpiringSoon($now)) {
            return false; // pas besoin de renouveler.
        }
        $refreshed = $this->exchange->refresh($current);
        if ($refreshed instanceof GraphApiError) {
            return false;
        }
        $this->tokens->save($refreshed);
        return true;
    }
}
