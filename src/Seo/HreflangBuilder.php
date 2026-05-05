<?php

declare(strict_types=1);

namespace OliTheme\Seo;

use OliTheme\I18n\LanguageRegistryInterface;
use OliTheme\I18n\TranslationModelInterface;

/**
 * Construit les balises hreflang pour le SEO multilingue.
 *
 * Retourne un tableau ordonné d'entrées `['code' => ..., 'url' => ...]`
 * incluant toutes les traductions disponibles et l'entrée `x-default`.
 *
 * @package OliTheme\Seo
 *
 * @since 1.0.0
 */
final class HreflangBuilder
{
    /**
     * @param LanguageRegistryInterface $registry Registre des langues activées.
     * @param TranslationModelInterface $translation Modèle des groupes de traduction.
     */
    public function __construct(
        private readonly LanguageRegistryInterface $registry,
        private readonly TranslationModelInterface $translation,
    ) {
    }

    /**
     * Construit la liste des entrées hreflang pour le post donné.
     *
     * @param int $postId Identifiant WordPress du post.
     *
     * @return array<int, array{code: string, url: string}>
     */
    public function build(int $postId): array
    {
        $translations = $this->translation->getTranslations($postId);
        $entries = [];
        $defaultUrl = null;
        $defaultCode = $this->registry->default()->code;

        foreach ($this->registry->all() as $language) {
            if (!\array_key_exists($language->code, $translations)) {
                continue;
            }

            $translatedPostId = $translations[$language->code];
            $permalink = get_permalink($translatedPostId);
            $url = \is_string($permalink) ? $permalink : '';

            $entries[] = ['code' => $language->code, 'url' => $url];

            if ($language->code === $defaultCode) {
                $defaultUrl = $url;
            }
        }

        if ($defaultUrl !== null) {
            $entries[] = ['code' => 'x-default', 'url' => $defaultUrl];
        }

        return $entries;
    }
}
