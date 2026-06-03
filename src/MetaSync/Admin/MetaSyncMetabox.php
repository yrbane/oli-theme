<?php

declare(strict_types=1);

namespace OliTheme\MetaSync\Admin;

use OliTheme\MetaSync\Lifecycle\MetaPostState;
use OliTheme\MetaSync\TokenStore;

/**
 * Metabox latérale sur l'éditeur de post/page/event pour activer la
 * synchronisation Meta sur ce contenu spécifique.
 *
 * @package OliTheme\MetaSync\Admin
 *
 * @since 1.3.0
 */
final class MetaSyncMetabox
{
    public const NONCE = 'oli_meta_sync_metabox';

    public function __construct(
        private readonly MetaPostState $state,
        private readonly TokenStore $tokens,
    ) {
    }

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetabox']);
        add_action('save_post',       [$this, 'handleSave'], 10, 2);
    }

    public function addMetabox(): void
    {
        foreach (['post', 'page', 'oli_event'] as $type) {
            add_meta_box(
                'oli-meta-sync',
                __('Synchro Facebook / Instagram', 'oli-theme'),
                [$this, 'render'],
                $type,
                'side',
                'default',
            );
        }
    }

    public function render(\WP_Post $post): void
    {
        wp_nonce_field(self::NONCE, '_oli_meta_sync_nonce');
        $enabled = $this->state->isEnabled($post->ID);
        $targets = $this->state->targets($post->ID);
        $fbId    = $this->state->fbPostId($post->ID);
        $igId    = $this->state->igMediaId($post->ID);
        $creds   = $this->tokens->load();
        $connected = $creds->isConnected();

        if (!$connected) {
            echo '<p>' . esc_html__('Pas encore connecté à Meta — onglet « Synchro Meta » dans Apparence du thème.', 'oli-theme') . '</p>';
            return;
        }

        ?>
        <p>
            <label>
                <input type="checkbox" name="oli_meta_sync_enabled" value="1" <?php checked($enabled); ?>>
                <strong><?php esc_html_e('Activer la sync sur ce post', 'oli-theme'); ?></strong>
            </label>
        </p>
        <p style="margin:0.25rem 0 0.75rem;">
            <label style="display:block;">
                <input type="checkbox" name="oli_meta_sync_targets[]" value="facebook" <?php checked(\in_array('facebook', $targets, true)); ?>>
                Facebook
            </label>
            <label style="display:block;">
                <input type="checkbox" name="oli_meta_sync_targets[]" value="instagram" <?php checked(\in_array('instagram', $targets, true)); ?>>
                Instagram
                <?php if ($creds->igUserId === ''): ?>
                    <em style="color:#b85d1f;font-size:0.85em;"><?php esc_html_e('(non lié)', 'oli-theme'); ?></em>
                <?php endif; ?>
            </label>
        </p>
        <hr>
        <p style="margin:0;font-size:0.85em;">
            <strong><?php esc_html_e('Statut :', 'oli-theme'); ?></strong>
            <?php
            $status = (string) get_post_meta($post->ID, MetaPostState::META_LAST_SYNC_STATUS, true);
            $labels = [
                ''        => __('Non synchronisé', 'oli-theme'),
                'synced'  => __('Synchronisé', 'oli-theme'),
                'partial' => __('Synchronisé partiellement', 'oli-theme'),
                'error'   => __('Erreur', 'oli-theme'),
                'pending' => __('En attente', 'oli-theme'),
            ];
            echo esc_html($labels[$status] ?? $status);
            ?>
        </p>
        <?php if ($fbId !== ''): ?>
            <p style="margin:0.25rem 0 0;font-size:0.85em;"><strong>FB :</strong> <code><?php echo esc_html($fbId); ?></code></p>
        <?php endif; ?>
        <?php if ($igId !== ''): ?>
            <p style="margin:0.25rem 0 0;font-size:0.85em;"><strong>IG :</strong> <code><?php echo esc_html($igId); ?></code></p>
        <?php endif; ?>
        <?php
        $err = (string) get_post_meta($post->ID, MetaPostState::META_LAST_SYNC_ERROR, true);
        if ($err !== ''): ?>
            <p style="margin:0.25rem 0 0;color:#c0392b;font-size:0.85em;"><?php echo esc_html($err); ?></p>
        <?php endif;
    }

    public function handleSave(int $postId, \WP_Post $post): void
    {
        if (!isset($_POST['_oli_meta_sync_nonce']) || !wp_verify_nonce((string) $_POST['_oli_meta_sync_nonce'], self::NONCE)) {
            return;
        }
        if (!current_user_can('edit_post', $postId)) {
            return;
        }
        if (\defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $enabled = !empty($_POST['oli_meta_sync_enabled']);
        update_post_meta($postId, MetaPostState::META_ENABLED, $enabled);

        $targets = isset($_POST['oli_meta_sync_targets']) && \is_array($_POST['oli_meta_sync_targets'])
            ? array_values(array_intersect(['facebook', 'instagram'], array_map('strval', $_POST['oli_meta_sync_targets'])))
            : [];
        update_post_meta($postId, MetaPostState::META_TARGETS, $targets);
    }
}
