<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Auth;

use OliTheme\MetaSync\Http\GraphApiError;
use OliTheme\MetaSync\MetaSyncCredentials;
use OliTheme\MetaSync\TokenStore;

/**
 * Pontage WordPress du flux OAuth Meta.
 *
 * Routes (admin uniquement, capability `manage_options`) :
 *  - `?action=oli_meta_oauth_start`      → redirige vers Facebook OAuth dialog
 *  - `?action=oli_meta_oauth_callback`   → reçoit `code`, échange, persiste
 *  - `?action=oli_meta_oauth_disconnect` → efface les credentials
 *  - `?action=oli_meta_oauth_test`       → call /me pour valider la connexion
 *
 * @package OliTheme\MetaSync\Auth
 *
 * @since 1.3.0
 */
final class MetaOAuthController
{
    public const ACTION_START      = 'oli_meta_oauth_start';
    public const ACTION_CALLBACK   = 'oli_meta_oauth_callback';
    public const ACTION_DISCONNECT = 'oli_meta_oauth_disconnect';
    public const ACTION_TEST       = 'oli_meta_oauth_test';

    private const SCOPES = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_posts',
        'instagram_basic',
        'instagram_content_publish',
    ];

    public function __construct(
        private readonly MetaOAuthExchange $exchange,
        private readonly TokenStore $tokens,
    ) {
    }

    public function register(): void
    {
        add_action('admin_post_' . self::ACTION_START,      [$this, 'handleStart']);
        add_action('admin_post_' . self::ACTION_CALLBACK,   [$this, 'handleCallback']);
        add_action('admin_post_' . self::ACTION_DISCONNECT, [$this, 'handleDisconnect']);
        add_action('admin_post_' . self::ACTION_TEST,       [$this, 'handleTest']);
    }

    public function handleStart(): void
    {
        $this->ensureCapability();
        check_admin_referer(self::ACTION_START);

        $appId     = (string) ($_POST['app_id']     ?? '');
        $appSecret = (string) ($_POST['app_secret'] ?? '');
        if ($appId === '' || $appSecret === '') {
            wp_die(esc_html__('App ID et App Secret sont requis.', 'oli-theme'), '', ['response' => 400]);
        }

        // Pré-enregistre app_id + secret (sans token) pour les retrouver au callback.
        $this->tokens->save(new MetaSyncCredentials(appId: $appId, appSecret: $appSecret));

        $state    = wp_generate_password(20, false);
        set_transient('oli_meta_oauth_state', $state, 600);

        $redirect = $this->callbackUrl();
        $params   = [
            'client_id'     => $appId,
            'redirect_uri'  => $redirect,
            'state'         => $state,
            'response_type' => 'code',
            'scope'         => implode(',', self::SCOPES),
        ];
        wp_redirect('https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query($params));
        exit;
    }

    public function handleCallback(): void
    {
        $this->ensureCapability();

        $expectedState = (string) get_transient('oli_meta_oauth_state');
        $state         = (string) ($_GET['state'] ?? '');
        if ($expectedState === '' || !hash_equals($expectedState, $state)) {
            wp_die(esc_html__('Paramètre OAuth state invalide.', 'oli-theme'), '', ['response' => 400]);
        }
        delete_transient('oli_meta_oauth_state');

        $code = (string) ($_GET['code'] ?? '');
        if ($code === '') {
            $err = (string) ($_GET['error_description'] ?? $_GET['error'] ?? 'unknown');
            $this->redirectWithStatus('error', $err);
        }

        $current = $this->tokens->load();
        if ($current->appId === '' || $current->appSecret === '') {
            wp_die(esc_html__('Identifiants application introuvables, recommencez la connexion.', 'oli-theme'), '', ['response' => 400]);
        }

        $result = $this->exchange->exchange($current->appId, $current->appSecret, $code, $this->callbackUrl());
        if ($result instanceof GraphApiError) {
            $this->redirectWithStatus('error', $result->message);
        }
        $this->tokens->save($result);
        $this->redirectWithStatus('connected');
    }

    public function handleDisconnect(): void
    {
        $this->ensureCapability();
        check_admin_referer(self::ACTION_DISCONNECT);
        $this->tokens->clear();
        $this->redirectWithStatus('disconnected');
    }

    public function handleTest(): void
    {
        $this->ensureCapability();
        check_admin_referer(self::ACTION_TEST);
        $creds = $this->tokens->load();
        if (!$creds->isConnected()) {
            $this->redirectWithStatus('test_no_token');
        }
        $result = $this->exchange->client()->get('/me', ['fields' => 'id,name'], $creds->accessToken);
        if ($result instanceof GraphApiError) {
            $this->redirectWithStatus('test_failed', $result->message);
        }
        $this->redirectWithStatus('test_ok', (string) ($result['name'] ?? ''));
    }

    private function callbackUrl(): string
    {
        return add_query_arg(['action' => self::ACTION_CALLBACK], admin_url('admin-post.php'));
    }

    private function ensureCapability(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Action non autorisée.', 'oli-theme'), '', ['response' => 403]);
        }
    }

    private function redirectWithStatus(string $status, string $extra = ''): void
    {
        $back = add_query_arg([
            'page' => 'oli-theme-settings',
            'tab'  => 'contact',
            'sub'  => 'meta-sync',
            'oli_meta_status' => $status,
            'oli_meta_extra'  => $extra,
        ], admin_url('themes.php'));
        wp_safe_redirect($back);
        exit;
    }
}
