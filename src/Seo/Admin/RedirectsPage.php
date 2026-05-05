<?php

declare(strict_types=1);

namespace OliTheme\Seo\Admin;

use OliTheme\Core\RendererInterface;
use OliTheme\Seo\RedirectModelInterface;

/**
 * Page d'administration des redirections HTTP (MVP).
 *
 * Enregistre un sous-menu sous Outils (`tools.php`) et affiche
 * la liste des redirections enregistrées dans la table `oli_redirects`.
 *
 * @package OliTheme\Seo\Admin
 *
 * @since 1.0.0
 */
final class RedirectsPage
{
    public function __construct(
        private readonly RedirectModelInterface $redirects,
        private readonly RendererInterface $renderer,
    ) {
    }

    public function register(): void
    {
        add_management_page(
            __('Redirections', 'oli-theme'),
            __('Redirections', 'oli-theme'),
            'manage_options',
            'oli-seo-redirects',
            [$this, 'render'],
        );
    }

    public function render(): void
    {
        echo $this->renderer->render('admin/redirects.html', [
            'redirects' => $this->redirects->findAll(),
        ]);
    }
}
