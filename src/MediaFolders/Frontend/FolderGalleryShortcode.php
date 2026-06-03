<?php

declare(strict_types=1);

namespace OliTheme\MediaFolders\Frontend;

use OliTheme\MediaFolders\MediaFolderQuery;

/**
 * Shortcode + bloc Gutenberg pour insérer une galerie de dossier
 * n'importe où dans le contenu d'un post, d'une page ou d'un event.
 *
 * Usage shortcode :
 *
 *   [oli_folder_gallery folder="stages-2026"]
 *   [oli_folder_gallery folder="stages-2026" children="false" limit="20"]
 *
 * Usage bloc Gutenberg : `oli/folder-gallery` (server-render — l'éditeur
 * voit un placeholder, le front voit la galerie complète).
 *
 * @package OliTheme\MediaFolders\Frontend
 *
 * @since 1.5.0
 */
final class FolderGalleryShortcode
{
    public const BLOCK_NAME = 'oli/folder-gallery';
    public const SHORTCODE  = 'oli_folder_gallery';

    public function __construct(private readonly MediaFolderQuery $query)
    {
    }

    public function register(): void
    {
        if (\function_exists('add_shortcode')) {
            add_shortcode(self::SHORTCODE, [$this, 'renderShortcode']);
        }
        if (\function_exists('register_block_type')) {
            register_block_type(self::BLOCK_NAME, [
                'render_callback' => [$this, 'renderBlock'],
                'attributes'      => [
                    'folder'          => ['type' => 'string',  'default' => ''],
                    'includeChildren' => ['type' => 'boolean', 'default' => true],
                    'limit'           => ['type' => 'number',  'default' => -1],
                    'title'           => ['type' => 'string',  'default' => ''],
                ],
            ]);
        }
    }

    /**
     * @param array<string, mixed>|string $atts
     */
    public function renderShortcode(array|string $atts = []): string
    {
        $atts = \is_array($atts) ? $atts : [];
        $folder         = (string)  ($atts['folder']   ?? '');
        $children       = !\in_array(strtolower((string) ($atts['children'] ?? 'true')), ['false', '0', 'no'], true);
        $limit          = (int)     ($atts['limit']    ?? -1);
        $title          = (string)  ($atts['title']    ?? '');

        return $this->render($folder, $children, $limit, $title);
    }

    /**
     * @param array<string, mixed>|null $attrs
     */
    public function renderBlock(?array $attrs = null): string
    {
        $attrs ??= [];
        return $this->render(
            (string) ($attrs['folder']          ?? ''),
            (bool)   ($attrs['includeChildren'] ?? true),
            (int)    ($attrs['limit']           ?? -1),
            (string) ($attrs['title']           ?? ''),
        );
    }

    private function render(string $folderSlug, bool $includeChildren, int $limit, string $title): string
    {
        if ($folderSlug === '') {
            return '<p class="oli-folder-gallery oli-folder-gallery--empty"><em>'
                . esc_html__('Aucun dossier spécifié pour la galerie.', 'oli-theme')
                . '</em></p>';
        }
        $photos = $this->query->photosInFolder($folderSlug, $includeChildren, $limit);
        if (empty($photos)) {
            return '<p class="oli-folder-gallery oli-folder-gallery--empty"><em>'
                . esc_html(sprintf(
                    /* translators: %s = folder slug */
                    __('Le dossier « %s » est vide.', 'oli-theme'),
                    $folderSlug,
                ))
                . '</em></p>';
        }

        $domId = 'oli-folder-' . substr(md5($folderSlug . spl_object_id($this)), 0, 6);

        ob_start();
        ?>
        <section class="oli-folder-gallery" id="<?php echo esc_attr($domId); ?>" data-oli-folder="<?php echo esc_attr($folderSlug); ?>">
            <?php if ($title !== ''): ?>
                <h3 class="oli-folder-gallery__title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <ul class="oli-folder-gallery__list" data-oli-folder-list>
                <?php foreach ($photos as $photo): ?>
                    <li class="oli-folder-gallery__item">
                        <a class="oli-folder-gallery__link"
                           href="<?php echo esc_url($photo['url']); ?>"
                           data-oli-lightbox="<?php echo esc_attr($domId); ?>"
                           data-caption="<?php echo esc_attr($photo['caption']); ?>">
                            <img class="oli-folder-gallery__img"
                                 src="<?php echo esc_url($photo['thumb']); ?>"
                                 <?php if ($photo['srcset'] !== ''): ?>srcset="<?php echo esc_attr($photo['srcset']); ?>"
                                 sizes="(max-width: 480px) 100vw, (max-width: 900px) 50vw, 33vw"<?php endif; ?>
                                 alt="<?php echo esc_attr($photo['alt'] !== '' ? $photo['alt'] : $photo['title']); ?>"
                                 loading="lazy" decoding="async">
                        </a>
                        <?php if ($photo['caption'] !== ''): ?>
                            <figcaption class="oli-folder-gallery__caption"><?php echo esc_html($photo['caption']); ?></figcaption>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}
