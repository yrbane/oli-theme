<?php

declare(strict_types=1);

namespace OliTheme\Events;

use OliTheme\Core\RendererInterface;

/**
 * Metabox 'Détails de l'événement' affichée sur l'écran d'édition des événements.
 *
 * Gère l'affichage et la sauvegarde des métadonnées spécifiques aux événements
 * (dates, lieu, adresse, flyer, inscription, tarif) avec protection nonce.
 *
 * @package OliTheme\Events
 *
 * @since 1.0.0
 */
final class EventMetabox
{
    /**
     * @param RendererInterface $renderer Moteur de rendu de templates.
     */
    public function __construct(private readonly RendererInterface $renderer)
    {
    }

    /**
     * Enregistre la metabox sur l'écran d'édition des événements.
     * À brancher sur 'add_meta_boxes'.
     */
    public function register(): void
    {
        add_meta_box(
            'oli_event_meta',
            __('Détails de l\'événement', 'oli-theme'),
            [$this, 'render'],
            'oli_event',
            'normal',
            'high',
        );
    }

    /**
     * Rend le contenu de la metabox.
     *
     * @param \WP_Post $post Post WordPress en cours d'édition.
     */
    public function render(\WP_Post $post): void
    {
        wp_nonce_field('oli_event_meta', 'oli_event_meta_nonce');
        echo $this->renderer->render('admin/event-metabox.html', [
            'startDate' => (string) get_post_meta($post->ID, '_oli_event_start_date', true),
            'endDate' => (string) get_post_meta($post->ID, '_oli_event_end_date', true),
            'location' => (string) get_post_meta($post->ID, '_oli_event_location', true),
            'address' => (string) get_post_meta($post->ID, '_oli_event_address', true),
            'flyerUrl' => (string) get_post_meta($post->ID, '_oli_event_flyer_url', true),
            'registrationUrl' => (string) get_post_meta($post->ID, '_oli_event_registration_url', true),
            'price' => (string) get_post_meta($post->ID, '_oli_event_price', true),
        ]);
    }

    /**
     * Sauvegarde les métadonnées de l'événement depuis les données POST.
     * À brancher sur 'save_post_oli_event'.
     *
     * @param int $postId Identifiant du post à sauvegarder.
     * @param array<string, mixed> $postData Données du formulaire ($_POST).
     */
    public function save(int $postId, array $postData): void
    {
        if (! isset($postData['oli_event_meta_nonce'])
            || ! wp_verify_nonce((string) $postData['oli_event_meta_nonce'], 'oli_event_meta')) {
            return;
        }

        $fields = [
            '_oli_event_start_date' => 'startDate',
            '_oli_event_end_date' => 'endDate',
            '_oli_event_location' => 'location',
            '_oli_event_address' => 'address',
            '_oli_event_flyer_url' => 'flyerUrl',
            '_oli_event_registration_url' => 'registrationUrl',
            '_oli_event_price' => 'price',
        ];

        foreach ($fields as $metaKey => $postKey) {
            $value = isset($postData[$postKey]) ? sanitize_text_field((string) $postData[$postKey]) : '';
            update_post_meta($postId, $metaKey, $value);
        }
    }
}
