<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Enregistre la taxonomie 'language' utilisée pour étiqueter pages, posts et CPT.
 *
 * Crée également un terme par langue activée (idempotent : utilise term_exists()
 * pour ne pas recréer un terme existant).
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final class LanguageTaxonomy
{
    public const NAME = 'language';

    public function __construct(private readonly LanguageRegistry $registry)
    {
    }

    /**
     * Enregistre la taxonomie sur les types de contenu de base et sème les termes.
     * À brancher sur le hook 'init'.
     */
    public function register(): void
    {
        register_taxonomy(
            self::NAME,
            ['post', 'page'],
            [
                'label' => 'Langues',
                'public' => false,
                'show_ui' => true,
                'show_admin_column' => true,
                'hierarchical' => false,
                'rewrite' => false,
                'show_in_rest' => true,
                'query_var' => false,
            ],
        );

        $this->seedTerms();
    }

    /**
     * Crée un terme pour chaque langue activée si absent.
     */
    private function seedTerms(): void
    {
        foreach ($this->registry->all() as $language) {
            $exists = term_exists($language->code, self::NAME);
            if (!$exists) {
                wp_insert_term($language->nativeLabel, self::NAME, [
                    'slug' => $language->code,
                ]);
            }
        }
    }
}
