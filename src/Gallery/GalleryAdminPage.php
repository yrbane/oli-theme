<?php

declare(strict_types=1);

namespace OliTheme\Gallery;

use OliTheme\Admin\AdminTabInterface;

/**
 * Onglet « Vidéos » de la page d'administration unifiée du thème.
 *
 * Gère la chaîne YouTube de référence + les vidéos manuelles (ID/URL +
 * légende). La galerie photos est désormais gérée depuis Médias → Galerie
 * photos (cases à cocher des dossiers de la médiathèque, ADR 0015).
 *
 * @package OliTheme\Gallery
 *
 * @since 1.0.0
 */
final class GalleryAdminPage implements AdminTabInterface
{
    public function __construct(
        private readonly GalleryRepository $repo,
        private readonly GalleryPagesInstaller $pages,
    ) {
    }

    public function id(): string
    {
        return 'galerie';
    }

    public function group(): string
    {
        return 'contenu';
    }

    public function label(): string
    {
        return __('Vidéos', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    public function renderPanel(): void
    {
        // Sauvegarde via $_POST (nonce + capability).
        if (!empty($_POST['oli_gallery_save'])) {
            $this->handleSave();
        }

        // Création des pages galerie manquantes (nonce + capability).
        if (!empty($_POST['oli_gallery_create_pages'])) {
            $this->handleCreatePages();
        }

        $channel = $this->repo->getYoutubeChannel();
        $videos  = $this->repo->getVideos();
        ?>
        <div class="notice notice-info inline" style="margin:1rem 0;padding:0.75rem 1rem;">
            <p style="margin:0 0 0.5rem;"><strong><?php esc_html_e('Comment afficher les galeries sur le site', 'oli-theme'); ?></strong></p>
            <ul style="margin:0 0 0 1.25rem;list-style:disc;line-height:1.6;">
                <li>
                    <?php
                    printf(
                        /* translators: %1$s, %2$s, %3$s, %4$s: slugs des pages */
                        esc_html__('Les galeries sont rendues automatiquement sur les pages WordPress dont le slug est %1$s ou %2$s (ainsi que leurs équivalents EN %3$s et %4$s).', 'oli-theme'),
                        '<code>photos</code>',
                        '<code>videos</code>',
                        '<code>photos-en</code>',
                        '<code>videos-en</code>',
                    );
                    ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Photos :', 'oli-theme'); ?></strong>
                    <?php
                    printf(
                        /* translators: %s: lien vers la page Médias → Galerie photos */
                        esc_html__('le choix des dossiers exposés sur la page galerie photos se fait depuis %s.', 'oli-theme'),
                        sprintf(
                            '<a href="%s">%s</a>',
                            esc_url(admin_url('upload.php?page=oli-media-folders-gallery')),
                            esc_html__('Médias → Galerie photos', 'oli-theme'),
                        ),
                    );
                    ?>
                    <?php esc_html_e('Le shortcode', 'oli-theme'); ?>
                    <code>[oli_folder_gallery folder="<?php esc_attr_e('mon-dossier', 'oli-theme'); ?>"]</code>
                    <?php esc_html_e('permet d\'insérer une galerie d\'un dossier dans n\'importe quelle page ou article.', 'oli-theme'); ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Vidéos :', 'oli-theme'); ?></strong>
                    <?php esc_html_e('si aucune vidéo n\'est ajoutée manuellement ci-dessous, les 15 dernières vidéos publiées de la chaîne YouTube sont récupérées automatiquement (cache 1 h). Pour forcer l\'ordre ou personnaliser les titres, ajoutez vos propres entrées — elles remplacent l\'auto-fetch.', 'oli-theme'); ?>
                </li>
            </ul>
        </div>

        <details class="card" style="max-width:none;margin:1rem 0;padding:0.5rem 1.25rem 1rem;">
            <summary style="cursor:pointer;font-weight:600;padding:0.5rem 0;font-size:1.05em;">
                <?php esc_html_e('Insérer une vidéo ailleurs avec un shortcode', 'oli-theme'); ?>
            </summary>

            <p>
                <?php esc_html_e('Pour insérer une vidéo YouTube responsive dans n\'importe quel article, page ou événement, deux options équivalentes :', 'oli-theme'); ?>
            </p>

            <p><strong><?php esc_html_e('1. Bloc Gutenberg', 'oli-theme'); ?></strong></p>
            <p>
                <?php esc_html_e('Dans l\'éditeur de bloc, clique sur « + », cherche « Vidéo Oli » et saisis l\'ID ou l\'URL YouTube dans les réglages du bloc.', 'oli-theme'); ?>
            </p>

            <p><strong><?php esc_html_e('2. Shortcode (éditeur classique ou bloc « Shortcode »)', 'oli-theme'); ?></strong></p>
            <p>
                <?php esc_html_e('Colle le shortcode dans le contenu :', 'oli-theme'); ?>
            </p>
            <pre style="background:#f6f7f7;padding:0.6rem 0.8rem;border:1px solid #dcdcde;border-radius:4px;overflow-x:auto;"><code>[oli_video id="<?php echo esc_html(__('M85A64fB4Yo', 'oli-theme')); ?>"]</code></pre>

            <p><strong><?php esc_html_e('Attributs disponibles', 'oli-theme'); ?></strong></p>
            <table class="widefat striped" style="max-width:760px;margin:0.5rem 0 1rem;">
                <thead>
                    <tr>
                        <th style="width:120px;"><?php esc_html_e('Attribut', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Rôle', 'oli-theme'); ?></th>
                        <th><?php esc_html_e('Exemple', 'oli-theme'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>id</code></td>
                        <td><?php esc_html_e('ID YouTube (11 caractères) OU URL YouTube. Obligatoire.', 'oli-theme'); ?></td>
                        <td><code>id="https://youtu.be/dYQUEd9Em38"</code></td>
                    </tr>
                    <tr>
                        <td><code>caption</code></td>
                        <td><?php esc_html_e('Légende affichée sous la vidéo (vide par défaut).', 'oli-theme'); ?></td>
                        <td><code>caption="Démo Kalari"</code></td>
                    </tr>
                    <tr>
                        <td><code>autoplay</code></td>
                        <td><?php esc_html_e('« true » pour démarrer automatiquement (lecture silencieuse imposée par le navigateur).', 'oli-theme'); ?></td>
                        <td><code>autoplay="true"</code></td>
                    </tr>
                    <tr>
                        <td><code>aspect</code></td>
                        <td><?php esc_html_e('Ratio largeur/hauteur (« 16/9 » par défaut). Pour vidéos verticales : « 9/16 ».', 'oli-theme'); ?></td>
                        <td><code>aspect="9/16"</code></td>
                    </tr>
                </tbody>
            </table>

            <p>
                <em><?php esc_html_e('Exemple complet :', 'oli-theme'); ?></em>
            </p>
            <pre style="background:#f6f7f7;padding:0.6rem 0.8rem;border:1px solid #dcdcde;border-radius:4px;overflow-x:auto;"><code>[oli_video id="M85A64fB4Yo" caption="Kalari Body Forms" autoplay="false" aspect="16/9"]</code></pre>

            <p>
                <strong><?php esc_html_e('Notes :', 'oli-theme'); ?></strong>
            </p>
            <ul style="line-height:1.7;margin:0 0 0 1.25rem;list-style:disc;">
                <li><?php esc_html_e('Le shortcode utilise youtube-nocookie.com — pas de cookie YouTube tant que la vidéo n\'est pas lue.', 'oli-theme'); ?></li>
                <li><?php esc_html_e('L\'iframe est chargé en différé (loading="lazy") pour ne pas pénaliser le temps de chargement.', 'oli-theme'); ?></li>
                <li><?php esc_html_e('Le shortcode est indépendant de la liste de vidéos ci-dessous — il accepte n\'importe quel ID YouTube.', 'oli-theme'); ?></li>
            </ul>
        </details>

        <?php $this->renderPagesStatus(); ?>

        <form method="post" action="">
            <?php wp_nonce_field('oli_gallery_save', '_oli_gallery_nonce'); ?>
            <input type="hidden" name="oli_gallery_save" value="1">

            <h2 class="title"><?php esc_html_e('Vidéos', 'oli-theme'); ?></h2>

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
                    <?php foreach ($videos as $video) : ?>
                        <?php $this->renderVideoRow((string) $video['video_id'], (string) $video['caption'], (string) $video['thumb']); ?>
                    <?php endforeach; ?>
                </div>
                <template id="oli-gallery-video-template">
                    <?php $this->renderVideoRow('', '', ''); ?>
                </template>
            </div>

            <?php submit_button(); ?>
        </form>

        <?php $this->renderScript(); ?>
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
            <input type="text" class="oli-gallery-video__id" name="oli_gallery_videos[<?php echo esc_attr($key); ?>][video_id]" value="<?php echo esc_attr($videoId); ?>" placeholder="<?php esc_attr_e('URL ou ID YouTube', 'oli-theme'); ?>">
            <input type="text" class="oli-gallery-video__caption" name="oli_gallery_videos[<?php echo esc_attr($key); ?>][caption]" value="<?php echo esc_attr($caption); ?>" placeholder="<?php esc_attr_e('Légende…', 'oli-theme'); ?>">
            <button type="button" class="button-link oli-gallery-video__remove" style="color:#b32d2e;">
                × <?php esc_html_e('Retirer', 'oli-theme'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Affiche l'état des pages galerie attendues et, le cas échéant, un bouton
     * pour créer automatiquement les pages manquantes.
     */
    private function renderPagesStatus(): void
    {
        $status  = $this->pages->status();
        if ($status === []) {
            return;
        }
        $missing = array_filter($status, static fn (array $row): bool => !$row['exists']);
        ?>
        <div class="card" style="max-width:none;margin:1rem 0;padding:0.5rem 1rem 1rem;">
            <h2 class="title" style="margin-top:0.5rem;"><?php esc_html_e('État des pages de galerie', 'oli-theme'); ?></h2>
            <p class="description"><?php esc_html_e('Le thème rend automatiquement le layout galerie sur les pages dont le slug correspond. Voici leur état :', 'oli-theme'); ?></p>
            <table class="widefat striped" style="max-width:560px;margin:0.5rem 0 1rem;">
                <thead><tr>
                    <th><?php esc_html_e('Page', 'oli-theme'); ?></th>
                    <th><?php esc_html_e('Slug', 'oli-theme'); ?></th>
                    <th><?php esc_html_e('Langue', 'oli-theme'); ?></th>
                    <th><?php esc_html_e('État', 'oli-theme'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($status as $row) : ?>
                    <tr>
                        <td><?php echo esc_html('photos' === $row['kind'] ? __('Photos', 'oli-theme') : __('Vidéos', 'oli-theme')); ?></td>
                        <td><code><?php echo esc_html($row['slug']); ?></code></td>
                        <td><?php echo esc_html(strtoupper($row['lang'])); ?></td>
                        <td>
                            <?php if ($row['exists']) : ?>
                                <span style="color:#007017;font-weight:600;">&#10004; <?php esc_html_e('Publiée', 'oli-theme'); ?></span>
                            <?php else : ?>
                                <span style="color:#b32d2e;font-weight:600;">&#10008; <?php esc_html_e('Manquante', 'oli-theme'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($missing !== []) : ?>
                <form method="post" action="" style="margin:0;">
                    <?php wp_nonce_field('oli_gallery_create_pages', '_oli_gallery_pages_nonce'); ?>
                    <input type="hidden" name="oli_gallery_create_pages" value="1">
                    <button type="submit" class="button button-primary">
                        <?php
                        printf(
                            /* translators: %d: nombre de pages manquantes */
                            esc_html(_n('Créer la page manquante (%d)', 'Créer les pages manquantes (%d)', \count($missing), 'oli-theme')),
                            (int) \count($missing),
                        );
                        ?>
                    </button>
                    <span class="description" style="margin-left:0.5rem;">
                        <?php esc_html_e('Crée les pages publiées, avec la bonne langue et la liaison de traduction FR↔EN.', 'oli-theme'); ?>
                    </span>
                </form>
            <?php else : ?>
                <p style="color:#007017;font-weight:600;margin:0;">&#10004; <?php esc_html_e('Toutes les pages de galerie sont en place.', 'oli-theme'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function handleCreatePages(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('oli_gallery_create_pages', '_oli_gallery_pages_nonce');

        $created = $this->pages->installMissing();

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(\sprintf(
                /* translators: %d: nombre de pages créées */
                _n('%d page de galerie créée.', '%d pages de galerie créées.', $created, 'oli-theme'),
                $created,
            )),
        );
    }

    private function handleSave(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('oli_gallery_save', '_oli_gallery_nonce');

        // Chaîne YouTube
        $channel = '';
        if (isset($_POST['oli_gallery_channel']) && \is_string($_POST['oli_gallery_channel'])) {
            $channel = $_POST['oli_gallery_channel'];
        }
        $this->repo->setYoutubeChannel($channel);

        // Vidéos
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

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Vidéos enregistrées.', 'oli-theme') . '</p></div>';
    }

    private function renderScript(): void
    {
        ?>
        <script>
        (function () {
            const videosList = document.getElementById('oli-gallery-videos-list');
            const videosAdd  = document.getElementById('oli-gallery-videos-add');
            const videoTpl   = document.getElementById('oli-gallery-video-template');

            // Délégation : retirer une vidéo
            document.addEventListener('click', function (e) {
                if (e.target.matches('.oli-gallery-video__remove')) {
                    e.target.closest('.oli-gallery-video').remove();
                }
            });

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
