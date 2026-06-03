<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Aiguille les URLs de home par langue vers la traduction de page_on_front.
 *
 * Le rewrite rule `^<code>/?$ → index.php?oli_lang=<code>` ne précise pas
 * de page. WordPress retombe alors sur `is_home()` (= archive blog) et le
 * template `home.php`/`index.php` au lieu de `front-page.php`. Conséquence :
 * la home anglophone n'affichait pas le contenu de la page d'accueil EN.
 *
 * Ce routeur fait, en hook `parse_request`, le pont entre langue active et
 * page_on_front : si on est sur `/<code>/` sans page demandée, on injecte
 * `page_id=<trad>` dans la requête WP afin que `is_front_page()` redevienne
 * vrai et que le pontage `front-page.php` (et donc `FrontPageController`)
 * soit appelé.
 *
 * @package OliTheme\I18n
 *
 * @since 1.2.0
 */
final class LanguageHomeRouter
{
    public function __construct(
        private readonly LanguageRegistry $registry,
        private readonly TranslationModelInterface $translations,
    ) {
    }

    /**
     * À brancher sur `parse_request` (passe `\WP` par référence).
     *
     * @param object $wp Instance \WP avec property publique `query_vars`.
     */
    public function route(object $wp): void
    {
        /** @var array<string, mixed> $queryVars */
        $queryVars = $wp->query_vars ?? [];

        $langCode = isset($queryVars['oli_lang']) && \is_string($queryVars['oli_lang'])
            ? $queryVars['oli_lang']
            : '';
        if ($langCode === '' || $langCode === $this->registry->default()->code) {
            return;
        }

        // Si une cible est déjà fixée (page spécifique, post, archive CPT), on
        // ne touche à rien.
        foreach (['pagename', 'page_id', 'name', 'post_type', 'p', 'category_name', 'tag'] as $targetVar) {
            if (!empty($queryVars[$targetVar])) {
                return;
            }
        }

        if ((string) get_option('show_on_front') !== 'page') {
            return;
        }

        $frontId = (int) get_option('page_on_front', 0);
        if ($frontId <= 0) {
            return;
        }

        $translations = $this->translations->getTranslations($frontId);
        if (!isset($translations[$langCode])) {
            return;
        }

        $wp->query_vars['page_id'] = $translations[$langCode];
    }
}
