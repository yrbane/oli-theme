<?php

declare(strict_types=1);

namespace OliTheme\Gallery;

/**
 * Page d'administration « Apparence > Galerie ».
 *
 * Une seule page pour gérer photos (upload via media library + caption) et
 * vidéos (URL chaîne YouTube + IDs/URLs vidéos avec captions).
 *
 * @package OliTheme\Gallery
 *
 * @since 1.0.0
 */
final class GalleryAdminPage
{
    public const PAGE_SLUG = 'oli-gallery';

    public function __construct(private readonly GalleryRepository $repo)
    {
    }

    public function register(): void
    {
        add_theme_page(
            __('Galerie', 'oli-theme'),
            __('Galerie', 'oli-theme'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
        );
    }

    public function render(): void
    {
        if (\function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        // Sauvegarde via $_POST (nonce + capability).
        if (!empty($_POST['oli_gallery_save'])) {
            $this->handleSave();
        }

        $photos  = $this->repo->getPhotos();
        $channel = $this->repo->getYoutubeChannel();
        $videos  = $this->repo->getVideos();

        ?>
        <div class="wrap oli-gallery-admin">
            <h1><?php esc_html_e('Galerie', 'oli-theme'); ?></h1>

            <div class="notice notice-info inline" style="margin:1rem 0;padding:0.75rem 1rem;">
                <p style="margin:0 0 0.5rem;"><strong><?php esc_html_e('Comment afficher les galeries sur le site', 'oli-theme'); ?></strong></p>
                <ul style="margin:0 0 0 1.25rem;list-style:disc;line-height:1.6;">
                    <li>
                        <?php
                        printf(
                            /* translators: %1$s, %2$s: URL des pages galerie */
                            esc_html__('Les galeries sont rendues automatiquement sur les pages WordPress dont le slug est %1$s ou %2$s (ainsi que leurs équivalents EN %3$s et %4$s).', 'oli-theme'),
                            '<code>photos</code>',
                            '<code>videos</code>',
                            '<code>photos-en</code>',
                            '<code>videos-en</code>',
                        );
                        ?>
                    </li>
                    <li>
                        <?php esc_html_e('Si ces pages n\'existent pas, créez-les dans Pages > Ajouter (le contenu est libre, le thème injectera le layout galerie automatiquement). Ajoutez-les ensuite à votre menu via Apparence > Menus.', 'oli-theme'); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Photos :', 'oli-theme'); ?></strong>
                        <?php esc_html_e('le bouton « Ajouter des photos » ouvre la médiathèque WP en sélection multiple. La légende est facultative.', 'oli-theme'); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Vidéos :', 'oli-theme'); ?></strong>
                        <?php esc_html_e('si aucune vidéo n\'est ajoutée manuellement ci-dessous, les 15 dernières vidéos publiées de la chaîne YouTube sont récupérées automatiquement (cache 1 h). Pour forcer l\'ordre ou personnaliser les titres, ajoutez vos propres entrées — elles remplacent l\'auto-fetch.', 'oli-theme'); ?>
                    </li>
                </ul>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('oli_gallery_save', '_oli_gallery_nonce'); ?>
                <input type="hidden" name="oli_gallery_save" value="1">

                <h2 class="title"><?php esc_html_e('Photos', 'oli-theme'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Choisissez plusieurs images depuis la médiathèque, puis ajoutez une légende à chacune.', 'oli-theme'); ?>
                </p>

                <div class="oli-gallery-photos">
                    <button type="button" class="button button-primary" id="oli-gallery-photos-pick">
                        <?php esc_html_e('Ajouter des photos', 'oli-theme'); ?>
                    </button>
                    <button type="button" class="button" id="oli-gallery-photos-clear" style="margin-left:0.5rem;">
                        <?php esc_html_e('Tout vider', 'oli-theme'); ?>
                    </button>

                    <div id="oli-gallery-photos-list" style="margin-top:1rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:0.75rem;">
                        <?php foreach ($photos as $i => $photo): ?>
                            <?php $this->renderPhotoRow((int) $photo['id'], (string) $photo['caption'], (string) $photo['thumb']); ?>
                        <?php endforeach; ?>
                    </div>
                    <template id="oli-gallery-photo-template">
                        <?php $this->renderPhotoRow(0, '', ''); ?>
                    </template>
                </div>

                <h2 class="title" style="margin-top:2.5rem;"><?php esc_html_e('Vidéos', 'oli-theme'); ?></h2>

                <table class="form-table" role="presentation"><tbody>
                    <tr>
                        <th scope="row"><label for="oli-gallery-channel"><?php esc_html_e('Chaîne YouTube', 'oli-theme'); ?></label></th>
                        <td>
                            <input type="url" id="oli-gallery-channel" name="oli_gallery_channel" value="<?php echo esc_attr($channel); ?>" class="regular-text code" placeholder="<?php echo esc_attr(GalleryRepository::DEFAULT_CHANNEL); ?>">
                            <p class="description"><?php esc_html_e('URL de votre chaîne YouTube (lien affiché sur la page Vidéos).', 'oli-theme'); ?></p>
                        </td>
                    </tr>
                </tbody></table>

                <p class="description">
                    <?php esc_html_e('Ajoutez des vidéos individuelles : collez l\'URL YouTube ou l\'ID de la vidéo, puis une légende.', 'oli-theme'); ?>
                </p>

                <div class="oli-gallery-videos">
                    <button type="button" class="button button-primary" id="oli-gallery-videos-add">
                        <?php esc_html_e('Ajouter une vidéo', 'oli-theme'); ?>
                    </button>

                    <div id="oli-gallery-videos-list" style="margin-top:1rem;display:flex;flex-direction:column;gap:0.5rem;">
                        <?php foreach ($videos as $video): ?>
                            <?php $this->renderVideoRow((string) $video['video_id'], (string) $video['caption'], (string) $video['thumb']); ?>
                        <?php endforeach; ?>
                    </div>
                    <template id="oli-gallery-video-template">
                        <?php $this->renderVideoRow('', '', ''); ?>
                    </template>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <?php $this->renderScript(); ?>
        <?php
    }

    private function renderPhotoRow(int $id, string $caption, string $thumb): void
    {
        ?>
        <div class="oli-gallery-photo" style="border:1px solid #dcdcde;border-radius:4px;padding:0.5rem;background:#fff;">
            <div style="aspect-ratio:1;background:#f0f0f1;border-radius:3px;overflow:hidden;margin-bottom:0.5rem;">
                <img class="oli-gallery-photo__thumb" src="<?php echo esc_attr($thumb); ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:<?php echo $thumb !== '' ? 'block' : 'none'; ?>;">
            </div>
            <input type="hidden" class="oli-gallery-photo__id" name="oli_gallery_photos[<?php echo $id; ?>][attachment_id]" value="<?php echo (int) $id; ?>">
            <input type="text" class="oli-gallery-photo__caption" name="oli_gallery_photos[<?php echo $id; ?>][caption]" value="<?php echo esc_attr($caption); ?>" placeholder="<?php esc_attr_e('Légende…', 'oli-theme'); ?>" style="width:100%;font-size:0.85em;">
            <button type="button" class="button-link oli-gallery-photo__remove" style="color:#b32d2e;font-size:0.8em;margin-top:0.25rem;">
                × <?php esc_html_e('Retirer', 'oli-theme'); ?>
            </button>
        </div>
        <?php
    }

    private function renderVideoRow(string $videoId, string $caption, string $thumb): void
    {
        $key = $videoId !== '' ? $videoId : '__new_' . uniqid();
        ?>
        <div class="oli-gallery-video" style="display:grid;grid-template-columns:120px 1fr 1fr auto;gap:0.5rem;align-items:center;border:1px solid #dcdcde;border-radius:4px;padding:0.5rem;background:#fff;">
            <div style="background:#000;aspect-ratio:16/9;border-radius:3px;overflow:hidden;">
                <img class="oli-gallery-video__thumb" src="<?php echo esc_attr($thumb); ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:<?php echo $thumb !== '' ? 'block' : 'none'; ?>;">
            </div>
            <input type="text" class="oli-gallery-video__id" name="oli_gallery_videos[<?php echo esc_attr($key); ?>][video_id]" value="<?php echo esc_attr($videoId); ?>" placeholder="<?php esc_attr_e('URL ou ID YouTube', 'oli-theme'); ?>" class="regular-text code">
            <input type="text" class="oli-gallery-video__caption" name="oli_gallery_videos[<?php echo esc_attr($key); ?>][caption]" value="<?php echo esc_attr($caption); ?>" placeholder="<?php esc_attr_e('Légende…', 'oli-theme'); ?>">
            <button type="button" class="button-link oli-gallery-video__remove" style="color:#b32d2e;">
                × <?php esc_html_e('Retirer', 'oli-theme'); ?>
            </button>
        </div>
        <?php
    }

    private function handleSave(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('oli_gallery_save', '_oli_gallery_nonce');

        // Photos
        /** @var array<int|string, mixed> $rawPhotos */
        $rawPhotos = \is_array($_POST['oli_gallery_photos'] ?? null) ? $_POST['oli_gallery_photos'] : [];
        $photos = [];
        foreach ($rawPhotos as $entry) {
            if (!\is_array($entry)) {
                continue;
            }
            $caption = '';
            if (isset($entry['caption']) && \is_string($entry['caption'])) {
                $caption = $entry['caption'];
            }
            $photos[] = [
                'attachment_id' => isset($entry['attachment_id']) ? (int) $entry['attachment_id'] : 0,
                'caption'       => $caption,
            ];
        }
        $this->repo->setPhotos($photos);

        // Channel
        $channel = '';
        if (isset($_POST['oli_gallery_channel']) && \is_string($_POST['oli_gallery_channel'])) {
            $channel = $_POST['oli_gallery_channel'];
        }
        $this->repo->setYoutubeChannel($channel);

        // Videos
        /** @var array<int|string, mixed> $rawVideos */
        $rawVideos = \is_array($_POST['oli_gallery_videos'] ?? null) ? $_POST['oli_gallery_videos'] : [];
        $videos = [];
        foreach ($rawVideos as $entry) {
            if (!\is_array($entry)) {
                continue;
            }
            $videoId = '';
            if (isset($entry['video_id']) && \is_string($entry['video_id'])) {
                $videoId = $entry['video_id'];
            }
            $caption = '';
            if (isset($entry['caption']) && \is_string($entry['caption'])) {
                $caption = $entry['caption'];
            }
            $videos[] = [
                'video_id' => $videoId,
                'caption'  => $caption,
            ];
        }
        $this->repo->setVideos($videos);

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Galerie enregistrée.', 'oli-theme') . '</p></div>';
    }

    private function renderScript(): void
    {
        ?>
        <script>
        (function () {
            const photosList   = document.getElementById('oli-gallery-photos-list');
            const photosPick   = document.getElementById('oli-gallery-photos-pick');
            const photosClear  = document.getElementById('oli-gallery-photos-clear');
            const videosList   = document.getElementById('oli-gallery-videos-list');
            const videosAdd    = document.getElementById('oli-gallery-videos-add');
            const photoTpl     = document.getElementById('oli-gallery-photo-template');
            const videoTpl     = document.getElementById('oli-gallery-video-template');

            // --- Photos : ajout via media uploader (multi) ---
            let frame;
            if (photosPick) {
                photosPick.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (typeof wp === 'undefined' || !wp.media) {
                        alert('<?php echo esc_js(__('Médiathèque indisponible. Rechargez la page.', 'oli-theme')); ?>');
                        return;
                    }
                    if (!frame) {
                        frame = wp.media({
                            title: '<?php echo esc_js(__('Choisir des photos', 'oli-theme')); ?>',
                            button: { text: '<?php echo esc_js(__('Ajouter à la galerie', 'oli-theme')); ?>' },
                            library: { type: 'image' },
                            multiple: true,
                        });
                        frame.on('select', function () {
                            frame.state().get('selection').each(function (att) {
                                const a = att.toJSON();
                                appendPhoto(a.id, a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url, a.caption || '');
                            });
                        });
                    }
                    frame.open();
                });
            }

            function appendPhoto(id, thumbUrl, caption) {
                if (!photoTpl) return;
                const node = photoTpl.content.firstElementChild.cloneNode(true);
                node.querySelector('.oli-gallery-photo__id').value = id;
                node.querySelector('.oli-gallery-photo__id').name = 'oli_gallery_photos[' + id + '][attachment_id]';
                node.querySelector('.oli-gallery-photo__caption').name = 'oli_gallery_photos[' + id + '][caption]';
                node.querySelector('.oli-gallery-photo__caption').value = caption;
                const img = node.querySelector('.oli-gallery-photo__thumb');
                img.src = thumbUrl;
                img.style.display = thumbUrl ? 'block' : 'none';
                photosList.appendChild(node);
            }

            if (photosClear) {
                photosClear.addEventListener('click', function () {
                    if (!confirm('<?php echo esc_js(__('Vider toutes les photos ?', 'oli-theme')); ?>')) return;
                    photosList.innerHTML = '';
                });
            }

            // Délégation : retirer
            document.addEventListener('click', function (e) {
                if (e.target.matches('.oli-gallery-photo__remove')) {
                    e.target.closest('.oli-gallery-photo').remove();
                } else if (e.target.matches('.oli-gallery-video__remove')) {
                    e.target.closest('.oli-gallery-video').remove();
                }
            });

            // --- Vidéos ---
            if (videosAdd) {
                videosAdd.addEventListener('click', function () {
                    if (!videoTpl) return;
                    const node = videoTpl.content.firstElementChild.cloneNode(true);
                    const key = '__new_' + Date.now();
                    node.querySelector('.oli-gallery-video__id').name = 'oli_gallery_videos[' + key + '][video_id]';
                    node.querySelector('.oli-gallery-video__caption').name = 'oli_gallery_videos[' + key + '][caption]';
                    videosList.appendChild(node);
                });
            }

            // Auto-thumbnail YouTube quand on saisit l'ID/URL
            document.addEventListener('input', function (e) {
                if (!e.target.matches('.oli-gallery-video__id')) return;
                const v = e.target.value || '';
                const m = v.match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/) || v.match(/^([A-Za-z0-9_-]{11})$/);
                if (!m) return;
                const id = m[1];
                const img = e.target.closest('.oli-gallery-video').querySelector('.oli-gallery-video__thumb');
                img.src = 'https://i.ytimg.com/vi/' + id + '/hqdefault.jpg';
                img.style.display = 'block';
            });
        })();
        </script>
        <?php
    }
}
