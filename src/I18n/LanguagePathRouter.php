<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Routeur universel : retire le préfixe `/<lang>/` de `REQUEST_URI` avant
 * que `WP_Rewrite` ne résolve l'URL, et réinjecte `oli_lang` dans les
 * query_vars après que WordPress a fait sa résolution naturelle.
 *
 * Pourquoi : la rewrite rule `^<code>/(.+)/?$ → oli_lang=<code>&pagename=$matches[1]`
 * capturait tout ce qui suivait `/en/`, y compris les permalinks date
 * (`/en/2026/06/03/slug/`), et tentait de les traiter comme une page WP →
 * 404. Le routeur ici retire le préfixe en amont pour que WP applique
 * ensuite ses rules standard (post-date, taxonomy, attachment, etc.) sans
 * collision et sans devoir dupliquer chaque pattern par langue.
 *
 * Pipeline :
 *   1. Hook `do_parse_request` (priorité 0) :
 *      - lit `$_SERVER['REQUEST_URI']`
 *      - si commence par `/<lang>/<rest>` (lang activée, ≠ défaut, et
 *        `<rest>` non vide), strippe le préfixe et mémorise la langue.
 *   2. Hook `request` (priorité 1, après WP::parse_request) :
 *      - si une langue a été détectée à l'étape 1, ajoute
 *        `oli_lang => <code>` aux query_vars produits par la résolution
 *        standard de WordPress.
 *
 * Le cas racine `/en/` n'est PAS strippé : il est géré par
 * {@see LanguageHomeRouter} qui injecte `page_id` quand `show_on_front=page`,
 * ou laisse WP servir l'archive blog quand `show_on_front=posts`. Stripper
 * `/en/` ferait disparaître la requête entière.
 *
 * @package OliTheme\I18n
 *
 * @since 1.3.0
 */
final class LanguagePathRouter
{
    private ?string $detectedLanguage = null;
    private ?string $originalRequestUri = null;

    public function __construct(private readonly LanguageRegistry $registry)
    {
    }

    /**
     * Hook `do_parse_request` : strippe le préfixe de langue dans
     * `$_SERVER['REQUEST_URI']` si applicable, et mémorise la langue
     * détectée pour réinjection ultérieure.
     *
     * @param bool $continue Valeur passée par le filtre (true = laisser WP parser).
     *
     * @return bool La même valeur — on n'arrête jamais le parsing, on le
     *              laisse simplement opérer sur une URL strippée.
     */
    public function interceptParseRequest(bool $continue): bool
    {
        if (!$continue) {
            return false;
        }

        $rawUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        [$uriPath, $uriQuery] = array_pad(explode('?', $rawUri, 2), 2, null);

        $homePath = (string) parse_url((string) get_option('home', ''), PHP_URL_PATH);
        $homePath = trim($homePath, '/');

        $relative = trim((string) $uriPath, '/');
        if ($homePath !== '' && str_starts_with($relative . '/', $homePath . '/')) {
            $relative = ltrim(substr($relative, \strlen($homePath)), '/');
        }

        $default = $this->registry->default();

        foreach ($this->registry->all() as $language) {
            if ($language->equals($default)) {
                continue;
            }

            $code = $language->code;

            // Cas /en/ (racine de langue) : laissé tel quel — c'est
            // LanguageHomeRouter qui prend le relais.
            if ($relative === $code) {
                return $continue;
            }

            if (!str_starts_with($relative, $code . '/')) {
                continue;
            }

            $strippedRelative = substr($relative, \strlen($code) + 1);
            $rebuiltPath = '/' . ($homePath !== '' ? $homePath . '/' : '') . $strippedRelative;
            // Préserve l'éventuel slash terminal de l'URL d'origine.
            if (str_ends_with((string) $uriPath, '/') && !str_ends_with($rebuiltPath, '/')) {
                $rebuiltPath .= '/';
            }

            $this->originalRequestUri = $rawUri;
            $_SERVER['REQUEST_URI'] = $rebuiltPath . ($uriQuery !== null ? '?' . $uriQuery : '');
            $this->detectedLanguage = $code;
            break;
        }

        return $continue;
    }

    /**
     * Hook `request` : réinjecte `oli_lang` dans les query_vars résolus
     * par WordPress, si une langue a été strippée à l'étape précédente.
     *
     * @param array<string, mixed> $vars
     *
     * @return array<string, mixed>
     */
    public function injectLanguageQueryVar(array $vars): array
    {
        if ($this->detectedLanguage !== null) {
            $vars['oli_lang'] = $this->detectedLanguage;
        }

        return $vars;
    }

    /**
     * Langue détectée au strip (utile pour debug/tests).
     */
    public function detectedLanguage(): ?string
    {
        return $this->detectedLanguage;
    }

    /**
     * Hook action `parse_request` (priorité tardive) : restaure
     * `$_SERVER['REQUEST_URI']` à sa valeur d'origine, pour que
     * {@see LanguageResolver} et tout autre composant qui lit
     * REQUEST_URI puissent encore voir le préfixe de langue.
     *
     * Sans cette restauration, le strip casserait la détection de
     * langue par le path (LanguageResolver lit l'URI brute, pas les
     * query_vars de WP).
     */
    public function restoreRequestUri(): void
    {
        if ($this->originalRequestUri !== null) {
            $_SERVER['REQUEST_URI'] = $this->originalRequestUri;
            $this->originalRequestUri = null;
        }
    }
}
