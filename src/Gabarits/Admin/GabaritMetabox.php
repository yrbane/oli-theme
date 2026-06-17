<?php

declare(strict_types=1);

namespace OliTheme\Gabarits\Admin;

use OliTheme\Gabarits\Gabarit;
use OliTheme\Gabarits\GabaritRegistryInterface;
use OliTheme\Gabarits\GabaritResolver;
use OliTheme\Gabarits\Zone;
use OliTheme\Gabarits\ZoneContent;
use OliTheme\Gabarits\ZoneContentRepository;
use OliTheme\Gabarits\ZoneType;

/**
 * Metabox du sélecteur de gabarit + édition des zones si le gabarit en
 * déclare. Chaque zone est rendue avec un contrôle adapté à son type :
 * - text    → `wp_editor` (TinyMCE light) si chargé, sinon textarea.
 * - image   → wp.media picker (1 attachment).
 * - gallery → wp.media picker multi (liste d'attachment ids).
 *
 * @package OliTheme\Gabarits\Admin
 *
 * @since 1.5.0
 */
final class GabaritMetabox
{
    public const NONCE = 'oli_gabarit_metabox';

    public function __construct(
        private readonly GabaritRegistryInterface $registry,
        private readonly ZoneContentRepository $zones,
    ) {
    }

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetabox']);
        add_action('save_post',       [$this, 'handleSave'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueueMedia']);
        add_action('edit_form_after_title', [$this, 'renderZoneForm']);
    }

    public function addMetabox(): void
    {
        foreach (['post', 'page', 'oli_event'] as $type) {
            // `side` (sidebar de droite) plutôt que `normal` (bas du contenu)
            // pour que la metabox soit immédiatement visible dans Gutenberg
            // sans avoir à scroller en dessous de l'éditeur de blocs.
            add_meta_box(
                'oli-gabarit',
                __('Gabarit & zones', 'oli-theme'),
                [$this, 'render'],
                $type,
                'side',
                'high',
            );
        }
    }

    public function enqueueMedia(string $hook): void
    {
        if (\in_array($hook, ['post.php', 'post-new.php'], true)) {
            wp_enqueue_media();
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

        echo '<p style="margin:0 0 0.75rem;">' . esc_html__('Style de présentation (gabarit) appliqué à ce contenu :', 'oli-theme') . '</p>';
        echo '<select name="oli_gabarit" id="oli-gabarit-select" style="min-width:280px;">';
        echo '<option value="">' . esc_html__('— Défaut du thème —', 'oli-theme') . '</option>';
        foreach ($available as $g) {
            \assert($g instanceof Gabarit);
            $selectedAttr = $current === $g->id ? ' selected' : '';
            $zonalAttr    = $g->isZonal() ? ' data-zonal="1"' : '';
            $marker       = $g->isZonal() ? ' ◆' : '';
            printf(
                '<option value="%s"%s%s>%s%s</option>',
                esc_attr($g->id),
                $selectedAttr,
                $zonalAttr,
                esc_html($g->name),
                esc_html($marker),
            );
        }
        echo '</select>';
        echo ' <em style="color:#50575e;font-size:0.85em;">◆ = gabarit zonal (avec champs spécifiques ci-dessous)</em>';

        $selected = $current !== '' ? $this->registry->byId($current) : null;
        if ($selected !== null && $selected->description !== '') {
            echo '<p style="margin-top:0.5rem;color:#50575e;">' . esc_html($selected->description) . '</p>';
        }

        // Hint UX : affiché par le JS quand l'utilisateur sélectionne un
        // gabarit zonal différent du courant (donc les zones du nouveau
        // gabarit ne sont pas encore rendues côté serveur — il faut
        // sauvegarder pour les voir).
        echo '<div id="oli-gabarit-save-hint" style="display:none;margin:1rem 0;padding:0.75rem 1rem;background:#fff8e1;border-left:4px solid #ffba00;">';
        echo '<strong>' . esc_html__('Ce gabarit a des zones à renseigner.', 'oli-theme') . '</strong> ';
        echo esc_html__('Enregistrez la page pour faire apparaître les champs des zones (Introduction, Image héros, etc.) ci-dessous.', 'oli-theme');
        echo '</div>';

        // Le formulaire d'édition des zones est rendu dans la colonne
        // principale (hook `edit_form_after_title`), pas ici en sidebar :
        // cf. renderZoneForm().

        $this->printSelectChangeHintScriptOnce($current);

        echo '<p style="margin-top:1rem;font-size:0.85em;"><a href="' . esc_url(add_query_arg(['page' => 'oli-theme-settings', 'tab' => 'apparence', 'sub' => 'gabarits'], admin_url('themes.php'))) . '">' . esc_html__('Voir la galerie complète des gabarits →', 'oli-theme') . '</a></p>';
    }

    /**
     * Rend le formulaire d'édition des zones dans la colonne principale
     * (hook `edit_form_after_title`). N'émet rien si le gabarit du post
     * n'est pas zonal. Masque le champ de contenu natif : pour un gabarit
     * zonal, ce sont les zones qui constituent la surface d'édition.
     */
    public function renderZoneForm(\WP_Post $post): void
    {
        $current  = (string) get_post_meta($post->ID, GabaritResolver::POSTMETA, true);
        $selected = $current !== '' ? $this->registry->byId($current) : null;
        if ($selected === null || !$selected->isZonal()) {
            return;
        }

        echo '<style>#postdivrich{display:none;}</style>';
        $contents = $this->zones->load($post->ID);
        echo '<div class="oli-zone-form" style="margin:1.5rem 0;">';
        echo '<h2 style="margin:0 0 0.25rem;">' . esc_html($selected->name) . '</h2>';
        echo '<p style="color:#50575e;margin:0 0 1rem;">' . esc_html__('Renseignez chaque zone. Les zones vides ne seront pas affichées.', 'oli-theme') . '</p>';
        foreach ($selected->zones as $zone) {
            $content = $contents[$zone->id] ?? new ZoneContent($zone->type);
            $this->renderZone($zone, $content);
        }
        echo '</div>';
    }

    private function renderZone(Zone $zone, ZoneContent $content): void
    {
        $name = 'oli_gabarit_zone[' . $zone->id . ']';
        echo '<div class="oli-zone" style="margin-bottom:1rem;padding:0.75rem 1rem;border:1px solid #dcdcde;border-radius:4px;background:#fafafa;">';
        echo '<label style="display:block;font-weight:600;margin-bottom:0.5rem;">';
        echo esc_html($zone->label);
        echo ' <code style="font-weight:400;color:#757575;font-size:0.85em;">' . esc_html($zone->id) . '</code>';
        echo ' <span style="font-weight:400;color:#757575;font-size:0.85em;">[' . esc_html($zone->type->label()) . ']</span>';
        echo '</label>';
        if ($zone->help !== '') {
            echo '<p style="margin:0 0 0.5rem;font-size:0.85em;color:#50575e;">' . esc_html($zone->help) . '</p>';
        }
        switch ($zone->type) {
            case ZoneType::Text:
                // `oli_gabarit_zone[{id}]` → identifiant TinyMCE valide.
                $editorId = 'oli_zone_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($zone->id));
                wp_editor($content->text, $editorId, [
                    'textarea_name' => $name . '[text]',
                    'media_buttons' => false,
                    'quicktags'     => false,
                    'textarea_rows' => 8,
                    'tinymce'       => [
                        'toolbar1' => 'bold,italic,bullist,numlist,link,unlink',
                        'toolbar2' => '',
                    ],
                ]);
                break;
            case ZoneType::Image:
                $this->renderImagePicker($name, $content->imageId);
                break;
            case ZoneType::Gallery:
                $this->renderGalleryPicker($name, $content->imageIds);
                break;
        }
        echo '</div>';
    }

    private function renderImagePicker(string $name, int $imageId): void
    {
        $idAttr = 'oli-zone-' . md5($name);
        $preview = '';
        if ($imageId > 0 && \function_exists('wp_get_attachment_image')) {
            $preview = (string) wp_get_attachment_image($imageId, 'thumbnail');
        }
        printf(
            '<div class="oli-zone-image" data-oli-zone-image>'
            . '<input type="hidden" name="%1$s[imageId]" id="%2$s" value="%3$d" data-oli-zone-image-input>'
            . '<div class="oli-zone-image__preview" data-oli-zone-image-preview style="min-height:50px;margin-bottom:0.4rem;">%4$s</div>'
            . '<button type="button" class="button" data-oli-zone-image-pick>%5$s</button> '
            . '<button type="button" class="button button-link-delete" data-oli-zone-image-clear>%6$s</button>'
            . '</div>',
            esc_attr($name),
            esc_attr($idAttr),
            $imageId,
            $preview, // déjà escapé (sortie WP)
            esc_html__('Choisir une image', 'oli-theme'),
            esc_html__('Retirer', 'oli-theme'),
        );
        $this->printPickerScriptOnce();
    }

    /**
     * @param list<int> $imageIds
     */
    private function renderGalleryPicker(string $name, array $imageIds): void
    {
        $idAttr  = 'oli-gallery-' . md5($name);
        $idsCsv  = implode(',', $imageIds);
        $thumbs  = '';
        if (\function_exists('wp_get_attachment_image')) {
            foreach ($imageIds as $id) {
                $thumbs .= '<span style="display:inline-block;margin:0 0.25rem 0.25rem 0;border:1px solid #ddd;">' . (string) wp_get_attachment_image($id, 'thumbnail') . '</span>';
            }
        }
        printf(
            '<div class="oli-zone-gallery" data-oli-zone-gallery>'
            . '<input type="hidden" name="%1$s[imageIdsCsv]" id="%2$s" value="%3$s" data-oli-zone-gallery-input>'
            . '<div class="oli-zone-gallery__preview" data-oli-zone-gallery-preview style="margin-bottom:0.4rem;">%4$s</div>'
            . '<button type="button" class="button" data-oli-zone-gallery-pick>%5$s</button> '
            . '<button type="button" class="button button-link-delete" data-oli-zone-gallery-clear>%6$s</button>'
            . '</div>',
            esc_attr($name),
            esc_attr($idAttr),
            esc_attr($idsCsv),
            $thumbs,
            esc_html__('Choisir / modifier la galerie', 'oli-theme'),
            esc_html__('Vider', 'oli-theme'),
        );
        $this->printPickerScriptOnce();
    }

    /**
     * Affiche une notice « Enregistre pour configurer les zones » dès que
     * l'utilisateur sélectionne un gabarit zonal différent du courant.
     *
     * Sans rechargement / AJAX, on ne peut pas faire apparaître les champs
     * de zones dynamiquement (ils dépendent du manifest serveur). Cette
     * notice rend le comportement explicite.
     */
    private function printSelectChangeHintScriptOnce(string $currentId): void
    {
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;
        ?>
        <script>
        (function () {
            const select = document.getElementById('oli-gabarit-select');
            const hint   = document.getElementById('oli-gabarit-save-hint');
            if (!select || !hint) return;
            const initial = <?php echo wp_json_encode($currentId); ?>;
            function refresh() {
                const opt = select.options[select.selectedIndex];
                const isZonal = opt && opt.dataset && opt.dataset.zonal === '1';
                const changedFromCurrent = select.value !== initial;
                hint.style.display = (isZonal && changedFromCurrent) ? '' : 'none';
            }
            select.addEventListener('change', refresh);
            refresh();
        })();
        </script>
        <?php
    }

    private function printPickerScriptOnce(): void
    {
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;
        ?>
        <script>
        (function () {
            const i18nImage   = <?php echo wp_json_encode(__('Choisir une image', 'oli-theme')); ?>;
            const i18nGallery = <?php echo wp_json_encode(__('Choisir des images', 'oli-theme')); ?>;
            const i18nUse     = <?php echo wp_json_encode(__('Utiliser', 'oli-theme')); ?>;

            function bindImagePickers() {
                document.querySelectorAll('[data-oli-zone-image]').forEach((wrap) => {
                    if (wrap.dataset.oliBound) return;
                    wrap.dataset.oliBound = '1';
                    const input   = wrap.querySelector('[data-oli-zone-image-input]');
                    const preview = wrap.querySelector('[data-oli-zone-image-preview]');
                    wrap.querySelector('[data-oli-zone-image-pick]').addEventListener('click', (e) => {
                        e.preventDefault();
                        const frame = wp.media({ title: i18nImage, button: { text: i18nUse }, multiple: false, library: { type: 'image' } });
                        frame.on('select', () => {
                            const att = frame.state().get('selection').first().toJSON();
                            input.value = att.id;
                            preview.innerHTML = att.sizes && att.sizes.thumbnail
                                ? `<img src="${att.sizes.thumbnail.url}" alt="">`
                                : `<code>#${att.id}</code>`;
                        });
                        frame.open();
                    });
                    wrap.querySelector('[data-oli-zone-image-clear]').addEventListener('click', (e) => {
                        e.preventDefault();
                        input.value = 0;
                        preview.innerHTML = '';
                    });
                });
            }
            function bindGalleryPickers() {
                document.querySelectorAll('[data-oli-zone-gallery]').forEach((wrap) => {
                    if (wrap.dataset.oliBound) return;
                    wrap.dataset.oliBound = '1';
                    const input   = wrap.querySelector('[data-oli-zone-gallery-input]');
                    const preview = wrap.querySelector('[data-oli-zone-gallery-preview]');
                    wrap.querySelector('[data-oli-zone-gallery-pick]').addEventListener('click', (e) => {
                        e.preventDefault();
                        const frame = wp.media({ title: i18nGallery, button: { text: i18nUse }, multiple: true, library: { type: 'image' } });
                        const initial = (input.value || '').split(',').map((v) => parseInt(v, 10)).filter(Boolean);
                        frame.on('open', () => {
                            const sel = frame.state().get('selection');
                            initial.forEach((id) => {
                                const a = wp.media.attachment(id);
                                a.fetch();
                                sel.add(a);
                            });
                        });
                        frame.on('select', () => {
                            const atts = frame.state().get('selection').toJSON();
                            input.value = atts.map((a) => a.id).join(',');
                            preview.innerHTML = atts.map((a) =>
                                `<span style="display:inline-block;margin:0 .25rem .25rem 0;border:1px solid #ddd;"><img src="${(a.sizes && a.sizes.thumbnail && a.sizes.thumbnail.url) || a.url}" alt=""></span>`
                            ).join('');
                        });
                        frame.open();
                    });
                    wrap.querySelector('[data-oli-zone-gallery-clear]').addEventListener('click', (e) => {
                        e.preventDefault();
                        input.value = '';
                        preview.innerHTML = '';
                    });
                });
            }
            function init() {
                if (typeof wp === 'undefined' || !wp.media) return;
                bindImagePickers();
                bindGalleryPickers();
            }
            document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
        })();
        </script>
        <?php
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

        // 1. Gabarit lui-même.
        $value = isset($_POST['oli_gabarit']) ? sanitize_key((string) $_POST['oli_gabarit']) : '';
        $selected = $value !== '' ? $this->registry->byId($value) : null;
        if ($selected === null) {
            delete_post_meta($postId, GabaritResolver::POSTMETA);
            $this->zones->save($postId, []); // nettoie les zones
            return;
        }
        update_post_meta($postId, GabaritResolver::POSTMETA, $selected->id);

        // 2. Zones (uniquement pour les zones déclarées par le gabarit).
        $rawZones = $_POST['oli_gabarit_zone'] ?? [];
        if (!\is_array($rawZones)) {
            $rawZones = [];
        }
        $contents = [];
        foreach ($selected->zones as $zone) {
            $payload = $rawZones[$zone->id] ?? [];
            if (!\is_array($payload)) {
                continue;
            }
            switch ($zone->type) {
                case ZoneType::Text:
                    $contents[$zone->id] = new ZoneContent($zone->type, text: wp_kses_post((string) ($payload['text'] ?? '')));
                    break;
                case ZoneType::Image:
                    $contents[$zone->id] = new ZoneContent($zone->type, imageId: (int) ($payload['imageId'] ?? 0));
                    break;
                case ZoneType::Gallery:
                    $csv = (string) ($payload['imageIdsCsv'] ?? '');
                    $ids = array_values(array_filter(
                        array_map('intval', explode(',', $csv)),
                        static fn (int $i): bool => $i > 0,
                    ));
                    $contents[$zone->id] = new ZoneContent($zone->type, imageIds: $ids);
                    break;
            }
        }
        $this->zones->save($postId, $contents);
    }
}
