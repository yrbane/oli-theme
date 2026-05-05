<?php

declare(strict_types=1);

namespace OliTheme\I18n;

/**
 * Construit le ViewModel du switcher de langue.
 *
 * Pour chaque langue activée, cherche la traduction du contenu courant via
 * {@see TranslationModel}; en l'absence de traduction, l'URL de l'item pointe
 * vers la home de la langue cible.
 *
 * @package OliTheme\I18n
 *
 * @since 1.0.0
 */
final class LanguageSwitcherController
{
    public function __construct(
        private readonly LanguageRegistry $registry,
        private readonly LanguageResolver $resolver,
        private readonly TranslationModel $translations,
    ) {
    }

    /**
     * Construit le ViewModel pour le post courant (0 = pas de post, ex. archive).
     */
    public function build(int $currentPostId): LanguageSwitcherViewModel
    {
        $current = $this->resolver->current();
        $translations = $currentPostId > 0
            ? $this->translations->getTranslations($currentPostId)
            : [];

        $items = [];
        foreach ($this->registry->all() as $language) {
            $hasTranslation = isset($translations[$language->code]);
            $url = $hasTranslation
                ? (string) get_permalink($translations[$language->code])
                : home_url('/' . $language->code . '/');

            $items[] = new LanguageSwitcherItem(
                code: $language->code,
                label: $language->label,
                nativeLabel: $language->nativeLabel,
                flag: $language->flag,
                url: $url,
                isCurrent: $language->equals($current),
                hasTranslation: $hasTranslation,
            );
        }

        return new LanguageSwitcherViewModel($current, $items);
    }
}
