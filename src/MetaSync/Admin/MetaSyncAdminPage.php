<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Admin;

use OliTheme\Admin\AdminTabInterface;
use OliTheme\MetaSync\Auth\MetaOAuthController;
use OliTheme\MetaSync\TokenStore;

/**
 * Sous-onglet « Synchronisation Meta » dans le groupe Contact.
 *
 * Affiche l'état de la connexion + bandeau d'aide permanent vers les
 * 3 guides clés (setup, expiration, app désactivée), notice colorée selon
 * l'état de connexion (vert = OK, orange = expire bientôt, rouge = erreur).
 *
 * @package OliTheme\MetaSync\Admin
 *
 * @since 1.3.0
 */
final class MetaSyncAdminPage implements AdminTabInterface
{
    public function __construct(private readonly TokenStore $tokens)
    {
    }

    public function id(): string
    {
        return 'meta-sync';
    }

    public function group(): string
    {
        return 'contact';
    }

    public function label(): string
    {
        return __('Synchro Meta', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    public function renderPanel(): void
    {
        $creds = $this->tokens->load();
        $now   = time();
        $admin = admin_url('admin-post.php');
        $back  = add_query_arg(['page' => 'oli-theme-settings', 'tab' => 'contact', 'sub' => 'meta-sync'], admin_url('themes.php'));

        $this->renderHelpBanner();
        $this->renderStatusFlash();

        if ($creds->isConnected()) {
            ?>
            <div class="notice notice-success inline" style="margin:1rem 0;padding:0.75rem 1rem;">
                <p><strong>✓ <?php esc_html_e('Connecté à Meta', 'oli-theme'); ?></strong></p>
                <ul style="margin:0.25rem 0 0 1.25rem;list-style:disc;">
                    <li><?php printf(esc_html__('Page : %s', 'oli-theme'), '<code>' . esc_html($creds->pageId) . '</code>'); ?></li>
                    <?php if ($creds->igUserId !== ''): ?>
                        <li><?php printf(esc_html__('Compte Instagram lié : %s', 'oli-theme'), '<code>' . esc_html($creds->igUserId) . '</code>'); ?></li>
                    <?php else: ?>
                        <li><em><?php esc_html_e('Aucun compte Instagram Business lié à cette Page.', 'oli-theme'); ?></em></li>
                    <?php endif; ?>
                    <li><?php
                        if ($creds->expiresAt > 0) {
                            printf(esc_html__('Token expire le : %s', 'oli-theme'), '<strong>' . esc_html(date('d/m/Y', $creds->expiresAt)) . '</strong>');
                        } else {
                            esc_html_e('Expiration du token : inconnue (sera affiné au prochain rafraîchissement)', 'oli-theme');
                        }
                    ?></li>
                </ul>
                <?php if ($creds->isExpiringSoon($now)): ?>
                    <p style="color:#b85d1f;margin-top:0.5rem;">⚠ <?php esc_html_e('Le token expire dans moins de 7 jours.', 'oli-theme'); ?></p>
                <?php endif; ?>
            </div>

            <p style="margin:1rem 0;display:flex;gap:0.5rem;flex-wrap:wrap;">
                <?php $this->renderForm($admin, MetaOAuthController::ACTION_TEST, [], __('Tester la connexion', 'oli-theme'), 'button-secondary'); ?>
                <?php $this->renderForm($admin, MetaOAuthController::ACTION_DISCONNECT, [], __('Déconnecter', 'oli-theme'), 'button-link-delete', __('Êtes-vous sûr·e de déconnecter Meta ?', 'oli-theme')); ?>
            </p>
            <?php
        } else {
            ?>
            <div class="notice notice-info inline" style="margin:1rem 0;padding:0.75rem 1rem;">
                <p><?php esc_html_e('Pas de connexion Meta active. Renseigner App ID + App Secret puis « Connecter à Facebook ».', 'oli-theme'); ?></p>
            </div>

            <form method="post" action="<?php echo esc_url($admin); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(MetaOAuthController::ACTION_START); ?>">
                <?php wp_nonce_field(MetaOAuthController::ACTION_START); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="meta-app-id"><?php esc_html_e('App ID Meta', 'oli-theme'); ?></label></th>
                        <td>
                            <input type="text" id="meta-app-id" name="app_id" class="regular-text" required value="<?php echo esc_attr($creds->appId); ?>">
                            <p class="description"><?php esc_html_e('Visible sur developers.facebook.com → votre App → Paramètres → Général.', 'oli-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="meta-app-secret"><?php esc_html_e('App Secret', 'oli-theme'); ?></label></th>
                        <td>
                            <input type="password" id="meta-app-secret" name="app_secret" class="regular-text" required autocomplete="new-password">
                            <p class="description"><?php esc_html_e('Cliquez sur « Afficher » dans Paramètres → Général pour récupérer la clé secrète.', 'oli-theme'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Connecter à Facebook', 'oli-theme')); ?>
            </form>
            <?php
        }
    }

    private function renderHelpBanner(): void
    {
        $helpBase = add_query_arg(['page' => 'oli-theme-settings', 'tab' => 'aide', 'guide' => ''], admin_url('themes.php'));
        ?>
        <div style="background:#fef9f1;border:1px solid #f0c36d;padding:0.75rem 1rem;border-radius:4px;margin:0 0 1rem;">
            <strong><?php esc_html_e('Besoin d\'aide ?', 'oli-theme'); ?></strong>
            <a href="<?php echo esc_url(add_query_arg('guide', 'meta-sync-setup', $helpBase)); ?>"><?php esc_html_e('Première installation', 'oli-theme'); ?></a> ·
            <a href="<?php echo esc_url(add_query_arg('guide', 'meta-sync-token-expired', $helpBase)); ?>"><?php esc_html_e('Mon token a expiré', 'oli-theme'); ?></a> ·
            <a href="<?php echo esc_url(add_query_arg('guide', 'meta-sync-app-disabled', $helpBase)); ?>"><?php esc_html_e('Mon App est désactivée', 'oli-theme'); ?></a>
        </div>
        <?php
    }

    private function renderStatusFlash(): void
    {
        $status = isset($_GET['oli_meta_status']) ? sanitize_key((string) $_GET['oli_meta_status']) : '';
        if ($status === '') {
            return;
        }
        $extra = isset($_GET['oli_meta_extra']) ? sanitize_text_field((string) $_GET['oli_meta_extra']) : '';
        $map = [
            'connected'      => ['notice-success', __('Connexion réussie.', 'oli-theme')],
            'disconnected'   => ['notice-info',    __('Déconnecté.', 'oli-theme')],
            'test_ok'        => ['notice-success', sprintf(__('Test OK : %s', 'oli-theme'), $extra)],
            'test_failed'    => ['notice-error',   sprintf(__('Test échoué : %s', 'oli-theme'), $extra)],
            'test_no_token'  => ['notice-warning', __('Pas de token actif à tester.', 'oli-theme')],
            'error'          => ['notice-error',   sprintf(__('Erreur OAuth : %s', 'oli-theme'), $extra)],
        ];
        if (!isset($map[$status])) {
            return;
        }
        [$class, $msg] = $map[$status];
        echo '<div class="notice ' . esc_attr($class) . ' inline"><p>' . esc_html($msg) . '</p></div>';
    }

    /**
     * @param array<string, string> $hidden
     */
    private function renderForm(string $action, string $value, array $hidden, string $label, string $btnClass, string $confirm = ''): void
    {
        echo '<form method="post" action="' . esc_url($action) . '" style="display:inline;"';
        if ($confirm !== '') {
            echo ' onsubmit="return confirm(\'' . esc_js($confirm) . '\');"';
        }
        echo '>';
        echo '<input type="hidden" name="action" value="' . esc_attr($value) . '">';
        wp_nonce_field($value);
        foreach ($hidden as $k => $v) {
            echo '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($v) . '">';
        }
        echo '<button type="submit" class="button ' . esc_attr($btnClass) . '">' . esc_html($label) . '</button>';
        echo '</form>';
    }
}
