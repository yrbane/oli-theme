<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Rewrite rules custom pour préfixer les URLs par la langue.
 *
 * Ajoute une règle "top" par langue activée :
 *   ^fr/?$         -> index.php?oli_lang=fr
 *   ^fr/(.+)/?$    -> index.php?oli_lang=fr&pagename=$matches[1]
 *
 * Et déclare la query var 'oli_lang' afin que WordPress la conserve à travers
 * la résolution de la requête.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final class RewriteRules
{
    public function __construct(private readonly LanguageRegistry $registry)
    {
    }

    /**
     * Enregistre les rewrite rules.
     * À brancher sur le hook 'init' (priorité par défaut).
     */
    public function register(): void
    {
        foreach ($this->registry->all() as $language) {
            $code = preg_quote($language->code, '~');

            add_rewrite_rule(
                '^' . $code . '/?$',
                'index.php?oli_lang=' . $language->code,
                'top',
            );

            add_rewrite_rule(
                '^' . $code . '/(.+)/?$',
                'index.php?oli_lang=' . $language->code . '&pagename=$matches[1]',
                'top',
            );
        }
    }

    /**
     * Filtre 'query_vars' : ajoute notre variable.
     *
     * @param array<int, string> $vars
     *
     * @return array<int, string>
     */
    public function addQueryVar(array $vars): array
    {
        $vars[] = 'oli_lang';

        return $vars;
    }
}
