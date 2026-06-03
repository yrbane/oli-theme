<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Rewrite rules custom pour préfixer les URLs par la langue.
 *
 * Ajoute une règle "top" par langue NON-défaut activée :
 *   ^en/?$         -> index.php?oli_lang=en   (racine de la langue)
 *
 * La résolution des sous-paths `/en/<rest>` (permalinks date, taxonomies,
 * pages, etc.) est faite par {@see LanguagePathRouter} qui retire le
 * préfixe `/en/` de REQUEST_URI avant que WP_Rewrite ne matche, puis
 * réinjecte `oli_lang=en` dans les query_vars. Cela évite de devoir
 * dupliquer chaque rule WP standard (post-date, archives, etc.) par
 * langue et empêche la capture aveugle `^en/(.+)/?$ → pagename=$1` qui
 * 404ait sur tout permalink ≠ page.
 *
 * Déclare aussi la query var 'oli_lang' afin que WordPress la conserve à
 * travers la résolution de la requête.
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
        $default = $this->registry->default();
        foreach ($this->registry->all() as $language) {
            if ($language->equals($default)) {
                continue;
            }
            $code = preg_quote($language->code, '~');

            // Seule la racine de langue est explicitement gérée. Les sous-paths
            // sont strippés en amont par LanguagePathRouter, puis WP_Rewrite
            // applique ses rules standard sur le path résultant.
            add_rewrite_rule(
                '^' . $code . '/?$',
                'index.php?oli_lang=' . $language->code,
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
            $ours['^' . $code . '/?$'] = 'index.php?oli_lang=' . $code;
        }

        // Supprime les rules existantes ayant les MÊMES patterns que les nôtres
        // (verbose page rules conflictuelles), puis fusionne en mettant les
        // nôtres en tête : la première rule qui match dans WP_Rewrite::match()
        // l'emporte.
        foreach (array_keys($ours) as $pattern) {
            unset($rules[$pattern]);
        }

        // Purge aussi toute rule héritée `^<code>/(.+)/?$ → pagename=…` (ancienne
        // capture aveugle qui 404ait sur les permalinks date). C'est désormais
        // {@see LanguagePathRouter} qui gère ces sous-paths.
        foreach ($this->registry->all() as $language) {
            if ($language->equals($default)) {
                continue;
            }
            unset($rules['^' . $language->code . '/(.+)/?$']);
        }

        return $ours + $rules;
    }
}
