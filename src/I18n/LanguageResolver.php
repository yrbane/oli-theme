<?php

declare(strict_types=1);

namespace OliTheme\I18n;

use OliTheme\Core\RequestContext;

/**
 * Résout la langue courante depuis les signaux disponibles.
 *
 * Ordre de priorité : query var 'oli_lang' (injectée par les rewrite rules),
 * puis cookie 'oli_lang', puis en-tête Accept-Language, puis langue par défaut.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final class LanguageResolver implements LanguageResolverInterface
{
    public const COOKIE_NAME = 'oli_lang';
    public const QUERY_VAR = 'oli_lang';

    private ?Language $memo = null;

    /** @var 'path'|'query_var'|'cookie'|'accept_language'|'default' */
    private string $lastSource = 'default';

    public function __construct(
        private readonly LanguageRegistry $registry,
        private readonly RequestContext $request,
    ) {
    }

    /**
     * Résout la langue selon l'ordre de priorité défini.
     */
    public function resolve(): Language
    {
        return $this->memo ??= $this->resolveOnce();
    }

    /**
     * Alias mémoïsé de resolve(). Conservé pour la lisibilité côté appelant.
     */
    public function current(): Language
    {
        return $this->resolve();
    }

    /**
     * Source de la dernière résolution. Utile pour décider de poser un cookie
     * de persistance quand la langue vient de l'URL.
     *
     * @return 'path'|'query_var'|'cookie'|'accept_language'|'default'
     */
    public function source(): string
    {
        $this->resolve();

        return $this->lastSource;
    }

    private function resolveOnce(): Language
    {
        // Le path est la source de vérité côté front : `/en/about/` → en.
        // WordPress route ces URL via les rewrite rules vers index.php?oli_lang=en
        // mais ce paramètre n'est pas dans $_GET, il est injecté dans WP_Query.
        // On parse donc directement REQUEST_URI pour rester indépendant de WP.
        $fromPath = $this->resolveFromPath();
        if ($fromPath !== null) {
            $this->lastSource = 'path';
            return $fromPath;
        }

        $fromQuery = $this->request->queryVar(self::QUERY_VAR);
        if ($fromQuery !== null) {
            $language = $this->registry->get($fromQuery);
            if ($language !== null) {
                $this->lastSource = 'query_var';
                return $language;
            }
        }

        $fromCookie = $this->request->cookie(self::COOKIE_NAME);
        if ($fromCookie !== null) {
            $language = $this->registry->get($fromCookie);
            if ($language !== null) {
                $this->lastSource = 'cookie';
                return $language;
            }
        }

        $fromHeader = $this->parseAcceptLanguage();
        if ($fromHeader !== null) {
            $language = $this->registry->get($fromHeader);
            if ($language !== null) {
                $this->lastSource = 'accept_language';
                return $language;
            }
        }

        $this->lastSource = 'default';
        return $this->registry->default();
    }

    /**
     * Détecte un préfixe de langue dans le path de REQUEST_URI : `/en/...` ou `/en`.
     */
    private function resolveFromPath(): ?Language
    {
        $uri = $this->request->server('REQUEST_URI');
        if ($uri === null) {
            return null;
        }

        $path = (string) parse_url($uri, \PHP_URL_PATH);
        if (preg_match('~^/([a-z]{2})(?:/|$)~', $path, $matches) !== 1) {
            return null;
        }

        return $this->registry->get($matches[1]);
    }

    /**
     * Extrait la première langue de l'en-tête Accept-Language qui correspond
     * à une langue activée. Ignore les facteurs de qualité (q=...) pour rester simple ;
     * pris dans l'ordre du header.
     */
    private function parseAcceptLanguage(): ?string
    {
        $header = $this->request->header('Accept-Language');
        if ($header === null) {
            return null;
        }

        foreach (explode(',', $header) as $entry) {
            $code = strtolower(substr(trim(explode(';', $entry, 2)[0]), 0, 2));
            if ($this->registry->isEnabled($code)) {
                return $code;
            }
        }

        return null;
    }
}
