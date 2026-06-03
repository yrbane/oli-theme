<?php

declare(strict_types=1);

namespace OliTheme\Gabarits\Admin;

use OliTheme\Gabarits\Gabarit;
use OliTheme\Gabarits\GabaritRegistryInterface;
use OliTheme\Gabarits\GabaritResolver;

/**
 * Metabox latérale qui permet à Olivier de choisir un gabarit
 * (style de présentation) pour le post/page/event en cours d'édition.
 *
 * @package OliTheme\Gabarits\Admin
 *
 * @since 1.4.0
 */
final class GabaritMetabox
{
    public const NONCE = 'oli_gabarit_metabox';

    public function __construct(private readonly GabaritRegistryInterface $registry)
    {
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
                'oli-gabarit',
                __('Gabarit (style de présentation)', 'oli-theme'),
                [$this, 'render'],
                $type,
                'side',
                'default',
            );
        }
    }

    public function render(\WP_Post $post): void
    {
        wp_nonce_field(self::NONCE, '_oli_gabarit_nonce');
        $current   = (string) get_post_meta($post->ID, GabaritResolver::POSTMETA, true);
        $available = $this->registry->forType((string) $post->post_type);

        if (empty($available)) {
            echo '<p>' . esc_html__('Aucun gabarit ne supporte ce type de contenu.', 'oli-theme') . '</p>';
            return;
        }

        echo '<p style="margin:0 0 0.5rem;">' . esc_html__('Choisissez un style de présentation pour ce contenu :', 'oli-theme') . '</p>';
        echo '<select name="oli_gabarit" id="oli-gabarit-select" style="width:100%;">';
        echo '<option value="">' . esc_html__('— Défaut du thème —', 'oli-theme') . '</option>';
        foreach ($available as $g) {
            \assert($g instanceof Gabarit);
            $selected = $current === $g->id ? ' selected' : '';
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($g->id),
                $selected,
                esc_html($g->name),
            );
        }
        echo '</select>';

        // Description du gabarit sélectionné (fallback).
        $selected = $current !== '' ? $this->registry->byId($current) : null;
        if ($selected !== null && $selected->description !== '') {
            echo '<p style="margin-top:0.5rem;color:#50575e;font-size:0.85em;">' . esc_html($selected->description) . '</p>';
        }
        echo '<p style="margin-top:0.5rem;font-size:0.85em;"><a href="' . esc_url(add_query_arg(['page' => 'oli-theme-settings', 'tab' => 'apparence', 'sub' => 'gabarits'], admin_url('themes.php'))) . '">' . esc_html__('Voir la galerie complète des gabarits →', 'oli-theme') . '</a></p>';
    }

    public function handleSave(int $postId, \WP_Post $post): void
    {
        if (!isset($_POST['_oli_gabarit_nonce']) || !wp_verify_nonce((string) $_POST['_oli_gabarit_nonce'], self::NONCE)) {
            return;
        }
        if (!current_user_can('edit_post', $postId)) {
            return;
        }
        if (\defined('DOING_AUTOSAVE') && \DOING_AUTOSAVE) {
            return;
        }
        $value = isset($_POST['oli_gabarit']) ? sanitize_key((string) $_POST['oli_gabarit']) : '';
        if ($value === '' || $this->registry->byId($value) === null) {
            delete_post_meta($postId, GabaritResolver::POSTMETA);
            return;
        }
        update_post_meta($postId, GabaritResolver::POSTMETA, $value);
    }
}
