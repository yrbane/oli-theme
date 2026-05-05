<?php

declare(strict_types=1);

namespace OliTheme\Seo\Admin;

use OliTheme\Core\RendererInterface;

/**
 * Page d'administration SEO Dashboard (MVP).
 *
 * Enregistre un sous-menu sous Outils (`tools.php`) et affiche
 * un tableau de bord minimal des scores SEO des contenus.
 *
 * @package OliTheme\Seo\Admin
 *
 * @since 1.0.0
 */
final class SeoOverviewPage
{
    public function __construct(private readonly RendererInterface $renderer)
    {
    }

    public function register(): void
    {
        add_management_page(
            __('SEO Dashboard', 'oli-theme'),
            __('SEO Dashboard', 'oli-theme'),
            'manage_options',
            'oli-seo-dashboard',
            [$this, 'render'],
        );
    }

    public function render(): void
    {
        echo $this->renderer->render('admin/seo-overview.html', [
            'title' => __('SEO Dashboard', 'oli-theme'),
        ]);
    }
}
