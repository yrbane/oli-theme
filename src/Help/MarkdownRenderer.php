<?php

declare(strict_types=1);

namespace OliTheme\Help;

/**
 * Convertisseur Markdown → HTML minimal, sans dépendance externe.
 *
 * Couvre le sous-ensemble nécessaire à la documentation in-admin :
 * titres `#`/`##`/`###`, paragraphes, listes (`-` ou `1.`), gras `**`,
 * italique `_`, code inline `` ` ``, blocs `` ``` ``, et liens `[t](u)`.
 *
 * Sécurité : le contenu utilisateur est échappé via {@see htmlspecialchars}
 * avant injection des balises ; seules les balises générées par le rendu
 * sont produites.
 *
 * @package OliTheme\Help
 *
 * @since 1.2.0
 */
final class MarkdownRenderer
{
    /**
     * Rend une chaîne Markdown en HTML.
     */
    public function render(string $markdown): string
    {
        $markdown = str_replace("\r\n", "\n", $markdown);
        if (trim($markdown) === '') {
            return '';
        }

        // Étape 1 : extraire les blocs de code fenced ``` pour les protéger.
        $codeBlocks = [];
        $markdown = preg_replace_callback(
            '/```\n?(.*?)```/s',
            static function (array $m) use (&$codeBlocks): string {
                $placeholder = "\0CODEBLOCK" . \count($codeBlocks) . "\0";
                $codeBlocks[] = '<pre><code>' . htmlspecialchars(rtrim($m[1], "\n"), ENT_QUOTES, 'UTF-8') . '</code></pre>';

                return $placeholder;
            },
            $markdown,
        ) ?? $markdown;

        // Étape 2 : découper en blocs séparés par lignes vides.
        $blocks = preg_split('/\n{2,}/', $markdown) ?: [];
        $htmlBlocks = [];

        foreach ($blocks as $block) {
            $block = trim($block, "\n");
            if ($block === '') {
                continue;
            }

            // Placeholder de bloc de code : rendu déjà calculé.
            if (preg_match('/^\0CODEBLOCK(\d+)\0$/', $block, $m)) {
                $htmlBlocks[] = $codeBlocks[(int) $m[1]];

                continue;
            }

            // Titres.
            if (preg_match('/^(#{1,6})\s+(.+)$/m', $block, $m) && substr_count($block, "\n") === 0) {
                $level = \strlen($m[1]);
                $htmlBlocks[] = '<h' . $level . '>' . $this->inline($m[2]) . '</h' . $level . '>';

                continue;
            }

            // Listes.
            if (preg_match('/^\s*(-|\d+\.)\s+/', $block)) {
                $htmlBlocks[] = $this->renderList($block);

                continue;
            }

            // Paragraphe par défaut.
            $htmlBlocks[] = '<p>' . $this->inline($block) . '</p>';
        }

        return implode("\n", $htmlBlocks);
    }

    /**
     * Allow-list des schémas autorisés pour les liens.
     * Renvoie '#' si le schéma est interdit (mailto/javascript/data/etc.).
     */
    private static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '#';
        }
        // Liens relatifs, ancres, paramètres internes : OK.
        if (str_starts_with($url, '/') || str_starts_with($url, '#') || str_starts_with($url, '?')) {
            return $url;
        }
        // Schémas autorisés.
        if (preg_match('#^(https?|mailto):#i', $url) === 1) {
            return $url;
        }

        return '#';
    }

    /**
     * Rend une liste ordonnée ou non en HTML.
     */
    private function renderList(string $block): string
    {
        $lines = explode("\n", $block);
        $ordered = (bool) preg_match('/^\s*\d+\.\s+/', $lines[0] ?? '');
        $tag = $ordered ? 'ol' : 'ul';

        $items = [];
        foreach ($lines as $line) {
            $line = preg_replace('/^\s*(?:-|\d+\.)\s+/', '', $line);
            if ($line === null || $line === '') {
                continue;
            }
            $items[] = '<li>' . $this->inline($line) . '</li>';
        }

        return '<' . $tag . '>' . implode('', $items) . '</' . $tag . '>';
    }

    /**
     * Échappe puis applique les transformations inline (liens, gras, italique, code).
     */
    private function inline(string $text): string
    {
        // Capture les liens AVANT échappement pour préserver l'URL brute,
        // puis remplace par un placeholder qu'on réinjecte après échappement.
        $links = [];
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            static function (array $m) use (&$links): string {
                $label = $m[1];
                $url   = self::safeUrl($m[2]);
                $placeholder = "\0LINK" . \count($links) . "\0";
                $links[] = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';

                return $placeholder;
            },
            $text,
        ) ?? $text;

        // Capture le code inline avant échappement (le contenu sera échappé séparément).
        $codes = [];
        $text = preg_replace_callback(
            '/`([^`]+)`/',
            static function (array $m) use (&$codes): string {
                $placeholder = "\0CODE" . \count($codes) . "\0";
                $codes[] = '<code>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</code>';

                return $placeholder;
            },
            $text,
        ) ?? $text;

        // Échappe le reste.
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Applique gras et italique sur le texte échappé.
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<![A-Za-z0-9])_([^_]+)_(?![A-Za-z0-9])/', '<em>$1</em>', $text) ?? $text;

        // Réinjecte liens et codes.
        foreach ($links as $i => $html) {
            $text = str_replace("\0LINK{$i}\0", $html, $text);
        }
        foreach ($codes as $i => $html) {
            $text = str_replace("\0CODE{$i}\0", $html, $text);
        }

        return $text;
    }
}
