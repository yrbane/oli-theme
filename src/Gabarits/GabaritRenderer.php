<?php

declare(strict_types=1);

namespace OliTheme\Gabarits;

/**
 * Rend un gabarit zonal en HTML.
 *
 * Chaque {@see ZoneContent} est traduit en HTML sûr selon son type
 * (texte HTML-sanitized, image avec alt/srcset, galerie scrollable),
 * puis injecté dans le template PHP du gabarit (`template.html.tpl`).
 *
 * Le template est inclus avec une variable `$zones` (zoneId → string HTML)
 * et `$post` (objet WP_Post). Les templates sont versionnés en Git → ils
 * sont du code de confiance et peuvent contenir du PHP.
 *
 * @package OliTheme\Gabarits
 *
 * @since 1.5.0
 */
final class GabaritRenderer
{
    /**
     * Rend l'HTML du contenu du gabarit (zone block) — à insérer dans le
     * template parent (page.html.tpl) à la place de `bodyHtml`.
     *
     * @param array<string, ZoneContent> $contents
     */
    public function render(Gabarit $gabarit, array $contents, ?\WP_Post $post = null): string
    {
        if (!$gabarit->hasCustomTemplate()) {
            return '';
        }
        $zones = [];
        foreach ($gabarit->zones as $zone) {
            $content = $contents[$zone->id] ?? null;
            $zones[$zone->id] = $content === null || $content->isEmpty()
                ? ''
                : $this->renderZone($content);
        }

        ob_start();
        $templatePath = (string) $gabarit->templateFsPath;
        include $templatePath;
        return (string) ob_get_clean();
    }

    private function renderZone(ZoneContent $content): string
    {
        return match ($content->type) {
            ZoneType::Text    => $this->renderText($content->text),
            ZoneType::Image   => $this->renderImage($content->imageId),
            ZoneType::Gallery => $this->renderGallery($content->imageIds),
        };
    }

    private function renderText(string $html): string
    {
        // Le contenu est déjà sanitized via wp_kses_post au save.
        // On re-passe par wp_kses_post au rendu pour être robuste à d'éventuelles
        // injections via update_post_meta hors metabox.
        if (\function_exists('wp_kses_post')) {
            return (string) wp_kses_post($html);
        }
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }

    private function renderImage(int $imageId): string
    {
        if ($imageId <= 0 || !\function_exists('wp_get_attachment_image')) {
            return '';
        }
        // wp_get_attachment_image génère img avec srcset + alt si défini.
        return (string) wp_get_attachment_image($imageId, 'large', false, ['loading' => 'lazy', 'decoding' => 'async']);
    }

    /**
     * @param list<int> $imageIds
     */
    private function renderGallery(array $imageIds): string
    {
        if (empty($imageIds) || !\function_exists('wp_get_attachment_image')) {
            return '';
        }
        $items = [];
        foreach ($imageIds as $id) {
            $img = (string) wp_get_attachment_image($id, 'large', false, ['loading' => 'lazy', 'decoding' => 'async']);
            if ($img !== '') {
                $items[] = '<figure class="oli-zone-gallery__item">' . $img . '</figure>';
            }
        }
        if (empty($items)) {
            return '';
        }
        return '<div class="oli-zone-gallery">' . implode('', $items) . '</div>';
    }
}
