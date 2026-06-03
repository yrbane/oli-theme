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

    /**
     * Filtre `rewrite_rules_array` : garantit que nos rules de langue
     * `^<code>/?$ → oli_lang=<code>` et `^<code>/(.+)/?$ → oli_lang=...&pagename=...`
     * restent en tête et ne sont pas écrasées par les verbose page rules
     * que WordPress génère pour chaque slug de page (ex. une page slugée
     * « en » ou « fr » introduit `^en/?$ → pagename=en`).
     *
     * @param array<string, string> $rules Rules WP collectées avant écriture.
     *
     * @return array<string, string> Rules avec nos préfixes de langue garantis en tête.
     */
    public function filter(array $rules): array
    {
        $default = $this->registry->default();
        $ours    = [];

        foreach ($this->registry->all() as $language) {
            // La langue par défaut ne préfixe pas l'URL : on ne crée pas de
            // rule `^<code>/?$` pour elle (la racine `/` reste canonique).
            if ($language->equals($default)) {
                continue;
            }

            $code = $language->code;
            $ours['^' . $code . '/?$']       = 'index.php?oli_lang=' . $code;
            $ours['^' . $code . '/(.+)/?$']  = 'index.php?oli_lang=' . $code . '&pagename=$matches[1]';
        }

        // Supprime les rules existantes ayant les MÊMES patterns que les nôtres
        // (verbose page rules conflictuelles), puis fusionne en mettant les
        // nôtres en tête : la première rule qui match dans WP_Rewrite::match()
        // l'emporte.
        foreach (array_keys($ours) as $pattern) {
            unset($rules[$pattern]);
        }

        return $ours + $rules;
    }
}
