<?php

declare(strict_types=1);

namespace OliTheme\Help;

use OliTheme\Admin\AdminTabInterface;

/**
 * Onglet « Aide » de la page d'administration unifiée du thème.
 *
 * Rend soit l'index des guides (sans paramètre `guide`), soit le contenu
 * du guide demandé (paramètre `guide=<id>`). Le contenu est lu depuis
 * `docs/admin/{file}.md` et converti via {@see MarkdownRenderer}.
 *
 * @package OliTheme\Help
 *
 * @since 1.2.0
 */
final class HelpAdminPage implements AdminTabInterface
{
    private const DOCS_RELATIVE_DIR = 'docs/admin';

    public function __construct(
        private readonly HelpRegistry $registry,
        private readonly MarkdownRenderer $renderer,
        private readonly string $themePath,
    ) {
    }

    public function id(): string
    {
        return 'aide';
    }

    public function group(): string
    {
        return 'aide';
    }

    public function label(): string
    {
        return __('Aide', 'oli-theme');
    }

    public function capability(): string
    {
        return 'manage_options';
    }

    public function renderPanel(): void
    {
        $guideId = isset($_GET['guide']) && \is_string($_GET['guide'])
            ? sanitize_key((string) $_GET['guide'])
            : '';

        if ($guideId === '') {
            $this->renderIndex();

            return;
        }

        $guide = $this->registry->byId($guideId);
        if ($guide === null) {
            $this->renderNotFound($guideId);

            return;
        }

        $this->renderGuide($guide);
    }

    /**
     * Index : liste des guides disponibles avec leur résumé.
     */
    private function renderIndex(): void
    {
        $intro = __("Bienvenue Olivier. Toute la documentation du thème est ici, à un clic.", 'oli-theme');

        echo '<div class="oli-help-wrap">';
        echo '<p class="oli-help-intro">' . esc_html($intro) . '</p>';
        echo '<ul class="oli-help-index">';
        foreach ($this->registry->all() as $guide) {
            $href = esc_url(add_query_arg(['guide' => $guide->id]));
            echo '<li>';
            echo '<a class="oli-help-card" href="' . $href . '">';
            echo '<strong>' . esc_html($guide->title) . '</strong>';
            echo '<span>' . esc_html($guide->summary) . '</span>';
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }

    /**
     * Rendu d'un guide : titre + contenu Markdown converti.
     */
    private function renderGuide(HelpGuide $guide): void
    {
        $path = $this->themePath . '/' . self::DOCS_RELATIVE_DIR . '/' . $guide->file;
        $markdown = is_readable($path) ? (string) file_get_contents($path) : '';

        $backUrl = esc_url(remove_query_arg('guide'));

        echo '<div class="oli-help-wrap oli-help-guide">';
        echo '<p class="oli-help-back"><a href="' . $backUrl . '">&larr; ' . esc_html__('Retour à l\'index de l\'aide', 'oli-theme') . '</a></p>';

        if ($markdown === '') {
            echo '<div class="notice notice-warning inline"><p>';
            echo esc_html(sprintf(
                /* translators: %s: chemin relatif du fichier manquant. */
                __('Le fichier de guide est introuvable : %s', 'oli-theme'),
                self::DOCS_RELATIVE_DIR . '/' . $guide->file,
            ));
            echo '</p></div>';
        } else {
            // Le renderer Markdown échappe lui-même les entrées utilisateur.
            echo '<article class="oli-help-content">';
            echo $this->renderer->render($markdown); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</article>';
        }

        echo '</div>';
    }

    /**
     * Guide demandé inconnu.
     */
    private function renderNotFound(string $guideId): void
    {
        $backUrl = esc_url(remove_query_arg('guide'));
        echo '<div class="oli-help-wrap">';
        echo '<div class="notice notice-error inline"><p>';
        echo esc_html(sprintf(
            /* translators: %s: id du guide inconnu. */
            __('Guide inconnu : %s', 'oli-theme'),
            $guideId,
        ));
        echo '</p></div>';
        echo '<p><a href="' . $backUrl . '">&larr; ' . esc_html__('Retour à l\'index de l\'aide', 'oli-theme') . '</a></p>';
        echo '</div>';
    }
}
