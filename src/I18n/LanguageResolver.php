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
final class LanguageResolver
{
    public const COOKIE_NAME = 'oli_lang';
    public const QUERY_VAR = 'oli_lang';

    private ?Language $memo = null;

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

    private function resolveOnce(): Language
    {
        $candidate = $this->request->queryVar(self::QUERY_VAR)
            ?? $this->request->cookie(self::COOKIE_NAME)
            ?? $this->parseAcceptLanguage();

        if ($candidate !== null) {
            $language = $this->registry->get($candidate);
            if ($language !== null) {
                return $language;
            }
        }

        return $this->registry->default();
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
