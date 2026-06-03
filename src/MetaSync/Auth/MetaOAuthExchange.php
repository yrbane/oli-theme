<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Auth;

use OliTheme\MetaSync\Http\GraphApiClient;
use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\MetaSyncCredentials;

/**
 * Échange un code OAuth contre des identifiants Meta exploitables.
 *
 * Workflow (Facebook OAuth + Graph API v19) :
 *  1. Code → short-lived user token (`/oauth/access_token`).
 *  2. Short → long-lived user token (`/oauth/access_token?grant_type=fb_exchange_token`).
 *  3. `/me/accounts` → trouve la Page + son page-access-token (long-lived).
 *  4. `/{page-id}?fields=instagram_business_account` → trouve l'IG Business.
 *
 * @package OliTheme\MetaSync\Auth
 *
 * @since 1.3.0
 */
final class MetaOAuthExchange
{
    public function __construct(private readonly GraphApiClient $client)
    {
    }

    /**
     * Accesseur pour les call sites qui ont besoin d'appeler l'API avec
     * un token déjà existant (test connexion, fetch d'info, etc.).
     */
    public function client(): GraphApiClient
    {
        return $this->client;
    }

    /**
     * @return MetaSyncCredentials|GraphApiError
     */
    public function exchange(string $appId, string $appSecret, string $code, string $redirectUri): MetaSyncCredentials|GraphApiError
    {
        if ($appId === '' || $appSecret === '' || $code === '') {
            return new GraphApiError(400, 'invalid_input', 'app_id, app_secret et code sont obligatoires.');
        }

        // Étape 1 : code → short-lived user token.
        $short = $this->client->get('/oauth/access_token', [
            'client_id'     => $appId,
            'client_secret' => $appSecret,
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ]);
        if ($short instanceof GraphApiError) {
            return $short;
        }
        $shortToken = (string) ($short['access_token'] ?? '');
        if ($shortToken === '') {
            return new GraphApiError(400, 'invalid_response', 'No access_token in /oauth/access_token response.');
        }

        // Étape 2 : long-lived user token.
        $long = $this->client->get('/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $appId,
            'client_secret'     => $appSecret,
            'fb_exchange_token' => $shortToken,
        ]);
        if ($long instanceof GraphApiError) {
            return $long;
        }
        $userToken = (string) ($long['access_token'] ?? $shortToken);
        $expiresIn = (int) ($long['expires_in'] ?? 0);

        // Étape 3 : trouve la Page + page-access-token.
        $accounts = $this->client->get('/me/accounts', ['fields' => 'id,name,access_token'], $userToken);
        if ($accounts instanceof GraphApiError) {
            return $accounts;
        }
        $pages = (array) ($accounts['data'] ?? []);
        if (empty($pages)) {
            return new GraphApiError(400, 'no_pages', 'Aucune Page Facebook trouvée pour ce compte.');
        }
        // Pour l'instant on prend la première Page. (Phase admin : permettre à
        // Olivier de choisir si plusieurs Pages.)
        $page         = $pages[0];
        $pageId       = (string) ($page['id'] ?? '');
        $pageToken    = (string) ($page['access_token'] ?? '');

        // Étape 4 : trouve l'IG Business lié à la Page.
        $igUserId = '';
        $igInfo   = $this->client->get('/' . $pageId, ['fields' => 'instagram_business_account'], $pageToken);
        if (!$igInfo instanceof GraphApiError) {
            $igUserId = (string) ($igInfo['instagram_business_account']['id'] ?? '');
        }

        return new MetaSyncCredentials(
            appId: $appId,
            appSecret: $appSecret,
            pageId: $pageId,
            igUserId: $igUserId,
            accessToken: $pageToken,
            expiresAt: $expiresIn > 0 ? (time() + $expiresIn) : 0,
        );
    }

    /**
     * Renouvelle un long-lived user token via fb_exchange_token.
     *
     * @return MetaSyncCredentials|GraphApiError
     */
    public function refresh(MetaSyncCredentials $current): MetaSyncCredentials|GraphApiError
    {
        if (!$current->isConnected()) {
            return new GraphApiError(400, 'not_connected', 'Pas de credentials existants à renouveler.');
        }
        $response = $this->client->get('/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $current->appId,
            'client_secret'     => $current->appSecret,
            'fb_exchange_token' => $current->accessToken,
        ]);
        if ($response instanceof GraphApiError) {
            return $response;
        }
        $newToken  = (string) ($response['access_token'] ?? $current->accessToken);
        $expiresIn = (int) ($response['expires_in'] ?? 0);

        return new MetaSyncCredentials(
            appId:       $current->appId,
            appSecret:   $current->appSecret,
            pageId:      $current->pageId,
            igUserId:    $current->igUserId,
            accessToken: $newToken,
            expiresAt:   $expiresIn > 0 ? (time() + $expiresIn) : $current->expiresAt,
        );
    }
}
